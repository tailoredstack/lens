# SPEC-TEMPEST — Tempest OpenAPI Binding Spec

> **Purpose:** Defines every Tempest‑specific extension that binds the framework‑agnostic OpenAPI core to Tempest PHP.
> **Target:** OpenAPI 3.1.0
> **Framework Version:** Tempest v3.11.3
> **Namespace:** `Tempest\OpenApi\*`
> **Package:** `tempest/openapi`

---

## 1. Overview

This spec covers the framework binding layer only. The framework‑agnostic core (Infer engine, PHP type system, OpenAPI builder) is reused unchanged — see `SPEC-LARAVEL.md` for those contracts.

### 1.1 What Changes vs Core

| Core Component | Reuse Strategy |
|----------------|----------------|
| TypeInferer (AST engine) | Reused as‑is |
| Type interface tree (48 types) | Reused as‑is |
| OpenApi builder classes (30 classes) | Reused as‑is |
| TypeTransformer (Type → Schema) | Reused as‑is, extensions added |
| **Route discovery** | **Replaced** — Tempest Discovery instead of Laravel Router |
| **Operation extensions** | **6 new Tempest‑specific extensions** |
| **Infer extensions** | **5 new Tempest‑specific extensions** |
| **Type → Schema extensions** | **6 new Tempest‑specific extensions** |
| **Exception mappers** | **3 new Tempest‑specific mappers** |
| **Configuration** | **New** — Tempest config object + discovery |
| **CLI Commands** | **New** — `#[ConsoleCommand]` attributes |

### 1.2 Pipeline (Tempest Context)

```
Tempest App Source (src/)
    │
    ▼
[1] Route Discovery (Tempest\OpenApi\RouteDiscovery)
    │   Iterates ClassReflector::getPublicMethods()
    │   Collects #[Get], #[Post], #[Put], #[Patch], #[Delete] etc.
    │   Applies #[Prefix], #[WithMiddleware], #[Stateless] decorators
    │
    ▼
[2] Operation Transformers (6 extensions)
    │   Enrich Operation with request params, response schemas, model bindings
    │
    ▼
[3] Infer Engine Extensions (5 extensions)
    │   Resolve query() return types, View data, map() targets,
    │   model properties, validation rules
    │
    ▼
[4] Type → Schema Mapping (6 extensions)
    │   Convert Tempest models, requests, enums, pagination, dates, collections
    │
    ▼
[5] Exception → Response (3 mappers)
    │   Map ValidationFailed → 422, HttpRequestFailed → dynamic, generic → 500
    │
    ▼
[6] Document Assembly
    │   Apply config (title, version, servers, security)
    │
    ▼
    OpenAPI 3.1 JSON/YAML
```

---

## 2. Route Discovery

### 2.1 Discovery Class

```php
namespace Tempest\OpenApi\Discovery;

use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;
use Tempest\Router\Route;

final class OpenApiRouteDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly OpenApiRouteConfigurator $configurator,
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getPublicMethods() as $method) {
            $routeAttributes = $method->getAttributes(Route::class);

            foreach ($routeAttributes as $routeAttribute) {
                $decorators = [
                    ...$method->getAttributes(RouteDecorator::class),
                    ...$method->getDeclaringClass()->getAttributes(RouteDecorator::class),
                ];

                $route = OpenApiDiscoveredRoute::fromAttribute(
                    $routeAttribute,
                    $decorators,
                    $method,
                );

                $this->discoveryItems->add($location, $route);
            }
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $route) {
            $this->configurator->addRoute($route);
        }
    }
}
```

### 2.2 Internal Route Representation

```php
namespace Tempest\OpenApi\Discovery;

use Tempest\Http\Method;
use Tempest\Reflection\MethodReflector;
use Tempest\Router\Route;

final class OpenApiDiscoveredRoute
{
    public function __construct(
        public readonly string           $uri,
        public readonly Method           $method,
        public readonly MethodReflector  $handler,
        /** @var class-string<HttpMiddleware>[] */
        public readonly array            $middleware,
        /** @var class-string<HttpMiddleware>[] */
        public readonly array            $without,
        public readonly bool             $isStateless,
        /** @var RouteParameter[] */
        public readonly array            $parameters,
        public readonly ?string          $requestClass,
        public readonly ?string          $returnType,
    ) {}
}
```

### 2.3 Route Parameter Extraction

From `DiscoveredRoute::parseUriAndParameters()` (source‑verified):

| Parameter Type | Schema Mapping |
|----------------|----------------|
| `int` / `integer` | `{ type: integer }` |
| `float` / `double` | `{ type: number, format: float }` |
| `bool` / `boolean` | `{ type: boolean }` |
| `string` (implicit) | `{ type: string }` |
| Model implementing `Bindable` | `{ $ref: '#/components/schemas/ModelName' }` |

### 2.4 #[Stateless] Filtering

Any route **not** decorated with `#[Stateless]` is assumed stateful (HTML/session) and **excluded** from the spec, unless `OpenApiConfig::includeStatefulRoutes` is true.

### 2.5 Route Attributes Collected

| Attribute | Method | URI Source |
|-----------|--------|------------|
| `#[Get]` | GET | `$attribute->uri` |
| `#[Post]` | POST | `$attribute->uri` |
| `#[Put]` | PUT | `$attribute->uri` |
| `#[Patch]` | PATCH | `$attribute->uri` |
| `#[Delete]` | DELETE | `$attribute->uri` |
| `#[Head]` | HEAD | `$attribute->uri` |
| `#[Options]` | OPTIONS | `$attribute->uri` |
| `#[Trace]` | TRACE | `$attribute->uri` |
| `#[Connect]` | CONNECT | `$attribute->uri` |

### 2.6 Route Decorators Collected

| Decorator | Effect on Spec |
|-----------|----------------|
| `#[Prefix('/api')]` | Prepends URI prefix |
| `#[WithMiddleware(...)]` | Noted as metadata; no direct OAS effect |
| `#[Stateless]` | Marks as API route (included) |
| `#[WithoutMiddleware(...)]` | Noted as metadata |

---

## 3. Operation Transformers

Six extensions implement the core `OperationExtension` contract.

### 3.1 TempestRequestEssentialsExtension

**File:** `src/Extensions/Operation/TempestRequestEssentialsExtension.php`

**Role:** Detect if the controller action has a Request DTO parameter. If so, map its public properties to the operation's requestBody and parameters.

**Logic:**
```
1. Iterate handler parameters
2. If parameter type is Request (or subclass of Request):
   a. Check if it's a custom Request class (not GenericRequest)
   b. Use ClassReflector to extract public properties
   c. For each property:
      - Check #[MapFrom] for name aliasing
      - Determine if it goes in requestBody (POST/PUT/PATCH) or query (GET/HEAD/DELETE)
      - Apply validation rules as parameter constraints
3. Build Schema for requestBody from properties
4. Attach to Operation::requestBody
```

**Property → Parameter mapping:**

| HTTP Method | Property Source | OAS Location |
|-------------|----------------|--------------|
| GET, HEAD, DELETE | `$request->query` | `parameter (in: query)` |
| POST, PUT, PATCH | `$request->body` | `requestBody (application/json)` |

### 3.2 TempestResponseExtension

**File:** `src/Extensions/Operation/TempestResponseExtension.php`

**Role:** Map the controller's return type to response schemas.

**Logic:**
```
1. Get return type of handler method (via Infer engine)
2. Match return type:
   a. Ok → 200, schema from body type
   b. Created → 201, schema from body type
   c. Json → 200, schema from body array/JsonSerializable
   d. array → 200, application/json (auto-wrapped by GenericRouter::createResponse)
   e. View → 200, text/html (skip API spec unless includeViews=true)
   f. NotFound → 404, no body
   g. ServerError → 500, no body
   h. GenericResponse → infer from constructor args
3. Attach to Operation::responses
```

**Critical source‑verified behavior:**
> `GenericRouter::createResponse()` wraps `array|string|View` in `new Ok(...)`.
> Any controller returning a plain `array` gets `200 application/json` in production.
> The spec must reproduce this inference.

### 3.3 TempestModelBindingsExtension

**File:** `src/Extensions/Operation/TempestModelBindingsExtension.php`

**Role:** Detect route parameters that are model classes (implementing `Bindable` + `IsDatabaseModel`).

**Logic:**
```
1. For each route parameter {id}:
2.   Check if handler param is typed as a model class
3.   If model uses IsDatabaseModel trait:
      - Add path parameter schema (type: integer or type: string for UUID)
      - Add response schema reference ($ref: #/components/schemas/ModelName)
```

### 3.4 TempestValidationExtension

**File:** `src/Extensions/Operation/TempestValidationExtension.php`

**Role:** Read `#[Rule]` attributes from Request DTO properties and translate to OAS parameter constraints.

**Attribute → OAS Constraint Mapping:**

| Tempest Attribute | OAS Schema Keywords |
|-------------------|---------------------|
| `#[HasLength(min: 3, max: 255)]` | `minLength: 3, maxLength: 255` |
| `#[IsEmail]` | `format: email` |
| `#[IsIn(1, 2, 3)]` | `enum: [1, 2, 3]` |
| `#[IsBetween(min: 1, max: 100)]` | `minimum: 1, maximum: 100` |
| `#[IsUrl]` | `format: uri` |
| `#[IsUuid]` | `format: uuid` |
| `#[IsNumeric]` | `type: number` |
| `#[IsInteger]` | `type: integer` |
| `#[IsBoolean]` | `type: boolean` |
| `#[IsString]` | `type: string` |
| `#[IsAlpha]` | `pattern: '^[a-zA-Z]+$'` |
| `#[IsAlphaNumeric]` | `pattern: '^[a-zA-Z0-9]+$'` |
| `#[MatchesRegEx('/pattern/')]` | `pattern` |
| `#[HasCount(min: 1, max: 10)]` | `minItems: 1, maxItems: 10` |
| `#[HasDateTimeFormat('Y-m-d')]` | `format: date` (or custom) |
| `#[IsAfterDate('2024-01-01')]` | `minimum` (date format) |
| `#[IsBeforeDate('2025-01-01')]` | `maximum` (date format) |
| `#[IsNotNull]` | `nullable: false` |
| `#[IsNotEmptyString]` | `minLength: 1` |
| `#[HasDateTimeFormat]` | Implicit from property type `\DateTimeInterface` → `format: date-time` |

### 3.5 TempestPaginationExtension

**File:** `src/Extensions/Operation/TempestPaginationExtension.php`

**Role:** Detect paginated responses and wrap the item schema in a pagination envelope.

**Logic:**
```
1. Check if return type involves QueryBuilder with ->paginate() call
   (Note: Tempest has no built-in paginator class — may be user-land helper)
2. If paginated, wrap response schema:
   {
     type: object,
     properties: {
       data: { type: array, items: { $ref: ... } },
       current_page: { type: integer },
       per_page: { type: integer },
       total: { type: integer },
       last_page: { type: integer },
     }
   }
```

> **Known gap:** Tempest v3.11.3 has no built-in pagination class. This extension may be a no-op until a pagination convention is established.

### 3.6 TempestStatelessFilterExtension

**File:** `src/Extensions/Operation/TempestStatelessFilterExtension.php`

**Role:** Filter out routes that are not decorated with `#[Stateless]` (i.e., session‑based HTML routes).

**Logic:**
```
1. Check route->isStateless flag (from #[Stateless] decorator)
2. If not stateless and config.includeStatefulRoutes is false:
   - Skip this route entirely (do not add to spec)
```

---

## 4. Infer Engine Extensions

Five extensions implement core Infer extension contracts for Tempest‑specific type resolution.

### 4.1 TempestQueryReturnTypeExtension

**File:** `src/Infer/Extensions/TempestQueryReturnTypeExtension.php`

**Purpose:** Resolve `query(Model::class)` → `Model` type.

**Source‑verified behavior:**
```php
// Tempest\Database\functions.php
function query(string|object $model): QueryBuilder
{
    return new QueryBuilder($model);
}
```

**Implementation:**
```
1. Detect call to function `query()` (imported via `use function Tempest\Database\query`)
2. Extract the first argument — it's a class-string or object
3. Resolve the class name
4. Return GenericType('QueryBuilder', [NamedObjectType(className)])
5. Further method calls on QueryBuilder (.find(), .all(), .get()) use generic type param
```

**Chain resolution for builder methods:**

| Method | Resolved Return Type |
|--------|---------------------|
| `query(Model::class)->find(...)` | `Model` |
| `query(Model::class)->all()` | `array<Model>` |
| `query(Model::class)->get($id)` | `?Model` |
| `query(Model::class)->create(...)` | `Model` |
| `query(Model::class)->first()` | `?Model` |
| `Model::all()` | `array<Model>` |
| `Model::find(...)` | `SelectQueryBuilder<Model>` (not resolved yet — need ->get()) |

### 4.2 TempestViewResultExtension

**File:** `src/Infer/Extensions/TempestViewResultExtension.php`

**Purpose:** Resolve `View` return type → extract data array keys.

**Source‑verified:**
```php
interface View {
    public string $path { get; }
    public array $data { get; }
    public function data(mixed ...$params): self;
}
```

**Implementation:**
```
1. Detect return type implementing View interface
2. Look for ->data(...) calls on the View before return
3. Extract key names from data() call arguments
4. Return ObjectType with inferred property names and mixed types
```

### 4.3 TempestMapperReturnTypeExtension

**File:** `src/Infer/Extensions/TempestMapperReturnTypeExtension.php`

**Purpose:** Resolve `map($from)->with(...)->to($class)` → target class type.

**Source‑verified:**
```php
// Tempest\Mapper\functions.php
function map(mixed $from, mixed ...$params): MapperFactory { ... }
// Used as: map($data)->with(ArrayToObjectMapper::class)->to($targetClass)
```

**Implementation:**
```
1. Detect call to function `map()`
2. Trace chain: ->with(string) ->to(string|object)
3. Extract the `$to` class name
4. Return NamedObjectType(className)
```

### 4.4 TempestModelPropertyExtension

**File:** `src/Infer/Extensions/TempestModelPropertyExtension.php`

**Purpose:** Resolve property types for `IsDatabaseModel` classes, including relations.

**Source‑verified:**
```php
// ModelInspector::getValueFields() returns only non-relation, non-virtual properties
// ModelInspector::isRelation() checks BelongsTo, HasMany, HasOne, etc.
```

**Implementation:**
```
1. Check if class uses IsDatabaseModel trait
2. For each public property:
   a. If property has #[Virtual] → skip
   b. If isRelation(property) → resolve relation type (BelongsTo → NamedObjectType, HasMany → ArrayType of NamedObjectType)
   c. Otherwise → use declared type (string, int, PrimaryKey, etc.)
3. Return ObjectType with resolved property types
```

**Relation type resolution:**

| Relation Attribute | Property PHP Type | OAS Schema |
|-------------------|-------------------|------------|
| `#[BelongsTo]` | `?Author` | `{ $ref: '#/components/schemas/Author' }` |
| `#[HasMany]` | `array<Chapter>` or `\Tempest\Support\Arr\ImmutableArray` | `{ type: array, items: { $ref: '#/components/schemas/Chapter' } }` |
| `#[HasOne]` | `?CoverImage` | `{ $ref: '#/components/schemas/CoverImage' }` |
| `BelongsToMany` | `array<Tag>` | `{ type: array, items: { $ref: '#/components/schemas/Tag' } }` |
| `HasOneThrough` | `?Profile` | `{ $ref: '#/components/schemas/Profile' }` |
| `HasManyThrough` | `array<Permission>` | `{ type: array, items: { $ref: '#/components/schemas/Permission' } }` |

> **Edge case:** `ModelInspector` auto‑detects `BelongsTo` for any property whose type is another model class (even without explicit `#[BelongsTo]` attribute). The Infer extension must replicate this detection.

### 4.5 TempestRequestValidationExtension (AfterClassDefinitionCreated)

**File:** `src/Infer/Extensions/TempestRequestValidationExtension.php`

**Purpose:** After a Request DTO class is analysed, attach validation rule metadata to its properties so the TypeTransformer can generate constraint schemas.

**Logic:**
```
1. Fire on AfterClassDefinitionCreated event
2. Check if class implements Request (or is a subtype)
3. For each public property:
   a. Collect #[Rule] attributes (HasLength, IsEmail, IsIn, etc.)
   b. Store them as metadata on the property for later retrieval by TempestValidationExtension
```

---

## 5. Type → Schema Extensions

Six extensions implement the core `TypeToSchemaExtension` contract.

### 5.1 TempestModelToSchemaExtension

**File:** `src/Extensions/TypeToSchema/TempestModelToSchemaExtension.php`

**Purpose:** Convert a model class to an object schema.

**Logic:**
```
1. Check if Type is NamedObjectType whose class uses IsDatabaseModel
2. Use ModelInspector::getValueFields() to get all data fields
3. For each field:
   a. Get declared type (string, int, PrimaryKey, etc.)
   b. Convert PrimaryKey to integer or string schema
   c. Apply #[Hidden] → exclude from schema
   d. Apply #[SerializeAs] → use custom property name in schema
4. Build Schema object with properties
5. Register in Components schemas
6. Return Schema with $ref
```

**Property exclusion rules (source‑verified from `ModelInspector::getSelectFields()`):**
- `#[Virtual]` → excluded
- `#[Hidden]` → excluded (from select)
- Relations (BelongsTo, HasMany, etc.) → excluded (handled separately)
- `PrimaryKey` type → mapped to `integer` or `string` (not an object ref)

**Column name from `#[Table]` attribute:**
```php
#[Table(name: 'books')]
class Book { ... }
// → schema name: "Book", table: "books"
```

### 5.2 TempestRequestToSchemaExtension

**File:** `src/Extensions/TypeToSchema/TempestRequestToSchemaExtension.php`

**Purpose:** Convert a Request DTO to a requestBody schema.

**Logic:**
```
1. Check if Type is NamedObjectType implementing Request
2. Exclude base Request properties (method, uri, body, headers, path, query, raw)
3. For each custom property:
   a. Apply #[MapFrom] name aliasing
   b. Apply validation rules as constraints
   c. Determine required/optional based on nullable type
4. Build Schema with properties + required array
5. Return Schema (not a $ref — inline request body)
```

**Base Request properties to exclude (source‑verified from `IsRequest` trait):**
```
method, uri, raw, body, headers, path, query, files, cookies
```

### 5.3 TempestEnumToSchemaExtension

**File:** `src/Extensions/TypeToSchema/TempestEnumToSchemaExtension.php`

**Purpose:** Convert PHP backed enums to enum schema.

**Logic:**
```
1. Check if Type is EnumType
2. Determine backing type (string or int)
3. Collect all case values
4. Return Schema { type: "string|integer", enum: [...] }
5. Register in Components schemas for re-use
```

### 5.4 TempestPaginationToSchemaExtension

**File:** `src/Extensions/TypeToSchema/TempestPaginationToSchemaExtension.php`

**Purpose:** Wrap an array schema in a paginated envelope.

**Logic:**
```
1. Detect if Type is wrapped in Paginated marker type
2. Transform inner type as items schema
3. Return envelope:
   {
     type: object,
     properties: {
       data: { type: array, items: itemsSchema },
       current_page: { type: integer },
       per_page: { type: integer },
       total: { type: integer },
       last_page: { type: integer },
     }
   }
```

### 5.5 TempestDateTimeToSchemaExtension

**File:** `src/Extensions/TypeToSchema/TempestDateTimeToSchemaExtension.php`

**Purpose:** Convert DateTimeInterface types to string with format.

**Logic:**
```
1. Check if Type references DateTimeInterface, DateTime, DateTimeImmutable, or Carbon
2. Return Schema { type: string, format: date-time }
3. Check for #[HasDateTimeFormat] attribute for custom format:
   "Y-m-d" → format: date
   "H:i:s" → format: time
   else → format: date-time
```

### 5.6 TempestCollectionToSchemaExtension

**File:** `src/Extensions/TypeToSchema/TempestCollectionToSchemaExtension.php`

**Purpose:** Convert `array<T>` or `iterable<T>` to array schema with typed items.

**Logic:**
```
1. Check if Type is ArrayType or IterableType
2. Transform inner element type via TypeTransformer recursively
3. Return Schema { type: array, items: innerSchema }
```

**Edge case:** `ImmutableArray` from `Tempest\Support\Arr` — inspect generic type parameter for element type.

---

## 6. Exception Mappers

Three extensions implement the core `ExceptionToResponseExtension` contract.

### 6.1 TempestValidationExceptionToResponse

**File:** `src/Extensions/Exception/TempestValidationExceptionToResponse.php`

**Purpose:** Map `Tempest\Validation\Exceptions\ValidationFailed` → 422 response.

**Source‑verified response shape (from `JsonExceptionRenderer::renderValidationFailedResponse()`):**
```json
{
    "message": "First validation error message",
    "errors": {
        "field_name": ["error message 1", "error message 2"]
    }
}
```

**Headers:**
```
x-validation: <JSON-encoded errors array>
```

**OAS operation output:**
```yaml
responses:
  422:
    description: Validation failed
    headers:
      x-validation:
        schema: { type: string }
    content:
      application/json:
        schema:
          type: object
          properties:
            message:
              type: string
            errors:
              type: object
              additionalProperties:
                type: array
                items:
                  type: string
```

### 6.2 TempestHttpExceptionToResponse

**File:** `src/Extensions/Exception/TempestHttpExceptionToResponse.php`

**Purpose:** Map `Tempest\Http\HttpRequestFailed` → dynamic status code response.

**Source‑verified:**
```php
// JsonExceptionRenderer::renderHttpRequestFailed()
// Uses $exception->status for HTTP status code
// Falls back to $exception->cause->body if available
```

**OAS operation output:**
```yaml
responses:
  {status}:
    description: HTTP error
    content:
      application/json:
        schema:
          type: object
          properties:
            message:
              type: string
```

### 6.3 TempestGenericExceptionToResponse

**File:** `src/Extensions/Exception/TempestGenericExceptionToResponse.php`

**Purpose:** Fallback 500 response for unhandled exceptions.

**Source‑verified:**
```php
// JsonExceptionRenderer::renderErrorResponse(Status::INTERNAL_SERVER_ERROR)
// Local dev: includes debug info (message, exception class, file, line, trace)
// Production: just message + status description
```

**OAS operation output:**
```yaml
responses:
  500:
    description: Internal server error
    content:
      application/json:
        schema:
          type: object
          properties:
            message:
              type: string
```

---

## 7. Configuration

### 7.1 Config Object

```php
namespace Tempest\OpenApi\Config;

final class OpenApiConfig
{
    public string $title = 'My API';
    public string $version = '1.0.0';
    public string $description = '';
    public string $format = 'json';        // json | yaml
    /** @var \Tempest\OpenApi\Generator\Server[] */
    public array $servers = [];
    public bool $debug = false;
    public bool $includeStatefulRoutes = false;
    public ?string $uiPath = '/openapi';
    public string $uiTheme = 'elements';   // elements | scalar | swagger
}
```

### 7.2 Wire via Tempest Discovery

Config loaded via Tempest's standard config discovery pattern:

```php
// src/openapi.config.php
return new \Tempest\OpenApi\Config\OpenApiConfig(
    title: 'Book API',
    version: '2.0.0',
    servers: [
        new \Tempest\OpenApi\Generator\Server(
            url: 'https://api.example.com/v2',
            description: 'Production',
        ),
    ],
);
```

### 7.3 Auto‑Registration via Package Discovery

```php
// src/OpenApiPackage.php
namespace Tempest\OpenApi;

use Tempest\Discovery\DiscoveryLocation;
use Tempest\Core\Installer;

final class OpenApiPackage implements Installer
{
    public function getInstallers(): array
    {
        return [
            new DiscoveryLocation(
                name: 'openapi',
                handler: OpenApiRouteDiscovery::class,
            ),
            new DiscoveryLocation(
                name: 'openapi-config',
                handler: OpenApiConfigDiscovery::class,
            ),
        ];
    }
}
```

---

## 8. CLI Commands

### 8.1 openapi:generate

```php
namespace Tempest\OpenApi\Commands;

use Tempest\Console\ConsoleCommand;

final readonly class GenerateOpenApiCommand
{
    public function __construct(
        private OpenApiGenerator $generator,
    ) {}

    #[ConsoleCommand('openapi:generate')]
    public function __invoke(): void
    {
        $spec = $this->generator->generate();
        // Output stats to console
    }
}
```

### 8.2 openapi:export

```php
#[ConsoleCommand('openapi:export')]
public function export(?string $path = null): void
{
    $spec = $this->generator->generate();
    $path ??= getcwd() . '/openapi.' . $this->config->format;
    file_put_contents($path, $spec->toString());
}
```

---

## 9. UI Controller

```php
namespace Tempest\OpenApi\Controllers;

use Tempest\Http\Responses\Ok;
use Tempest\Router\Get;
use Tempest\Router\IsResponse;
use Tempest\Router\Route;
use Tempest\View\View;

final readonly class OpenApiUiController
{
    public function __construct(
        private OpenApiConfig $config,
    ) {}

    #[Get('/openapi')]
    public function __invoke(): Ok
    {
        return new Ok(
            // Serve Stoplight Elements or Scalar UI
            // Inline HTML with CDN JS
        );
    }
}
```

---

## 10. Namespace Map

### 10.1 Core → Tempest Renamespace

| Core Namespace | Tempest Namespace |
|----------------|-------------------|
| `Scramble\Infer\TypeInferer` | `Tempest\OpenApi\Infer\TypeInferer` |
| `Scramble\Infer\Scope\Scope` | `Tempest\OpenApi\Infer\Scope\Scope` |
| `Scramble\Infer\Scope\Index` | `Tempest\OpenApi\Infer\Scope\Index` |
| `Scramble\Support\Type\*` | `Tempest\OpenApi\Support\Type\*` |
| `Scramble\Support\Generator\*` | `Tempest\OpenApi\Support\Generator\*` |
| `Scramble\Extensions\TypeToSchemaExtension` | `Tempest\OpenApi\Extensions\TypeToSchema\TypeToSchemaExtension` |
| `Scramble\Extensions\OperationExtension` | `Tempest\OpenApi\Extensions\Operation\OperationExtension` |
| `Scramble\Extensions\ExceptionToResponseExtension` | `Tempest\OpenApi\Extensions\Exception\ExceptionToResponseExtension` |

### 10.2 Tempest‑Only Namespaces

| Component | Namespace |
|-----------|-----------|
| Route Discovery | `Tempest\OpenApi\Discovery\*` |
| Infer Extensions | `Tempest\OpenApi\Infer\Extensions\*` |
| Type→Schema Extensions | `Tempest\OpenApi\Extensions\TypeToSchema\*` |
| Operation Extensions | `Tempest\OpenApi\Extensions\Operation\*` |
| Exception Mappers | `Tempest\OpenApi\Extensions\Exception\*` |
| Config | `Tempest\OpenApi\Config\*` |
| Commands | `Tempest\OpenApi\Commands\*` |
| Controllers | `Tempest\OpenApi\Controllers\*` |

---

## 11. Known Gaps / Omissions

| Feature | Status | Reason |
|---------|--------|--------|
| Built-in pagination | **Gap** | Tempest v3.11.3 has no `Paginated` result class. May need community convention |
| `ModelNotFoundException` | **Gap** | No dedicated exception; `findById` returns `null` |
| Authentication scopes | **Gap** | Tempest\Auth needs separate analysis for OAuth2/scope inference |
| Blade directives | **N/A** | Tempest uses `View` interface, not Blade |
| Route name inference | **N/A** | Tempest routes have no name |
| Policy/Authorization inference | **Gap** | No direct `authorize()` equivalent on requests |
| Soft deletes | **Gap** | No built-in soft delete trait |
| Api resource classes | **N/A** | Tempest has no Laravel-style `JsonResource` |
| Rate limiting | **N/A** | Not a framework concern |
| Media type negotiation | **Partial** | `Request::accepts()` exists but not commonly used for content negotiation |
