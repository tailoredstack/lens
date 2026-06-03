# SPEC-LARAVEL — Scramble (Laravel) Clean‑Room Architecture

> **Purpose:** Framework‑agnostic reconstruction of the `dedoc/scramble` OpenAPI generator architecture.
> **Target:** OpenAPI 3.1.0
> **Constraint:** No code copied from `dedoc/scramble`. Only interfaces, contracts, and architecture documented.
> **Scope:** All 5 subsystems (~135 files) that form the core inference + generation pipeline.

---

## 1. Overview

Scramble generates OpenAPI 3.1 specs for Laravel applications by statically analysing PHP source code (AST + reflection). It never executes application code.

### 1.1 Pipeline Stages

```
Source Code (PHP files)
    │
    ▼
[1] Route Discovery ─── collect controllers, routes, middleware
    │
    ▼
[2] Operation Transformation ─── enrich each operation with request/response metadata
    │
    ▼
[3] Type Inference ─── static analysis of method bodies, return types, property types
    │
    ▼
[4] Type → Schema Mapping ─── PHP types → OAS 3.1 Schema objects
    │
    ▼
[5] Document Assembly ─── build final OpenApi document, apply config
    │
    ▼
OpenAPI 3.1 Spec (JSON/YAML)
```

### 1.2 Five Subsystems

| # | Subsystem | Role | File Count |
|---|-----------|------|------------|
| A | Route Discovery & Transform | Collect routes, apply operation transformers | ~10 |
| B | Infer Engine | Static AST analysis, type resolution | ~35 |
| C | PHP Type System | Type interface tree for inferred types | ~48 |
| D | OpenAPI 3.1 Builder | OAS schema/parameter/response builders | ~30 |
| E | Extension Contracts | Pluggable extension points | ~12 |

---

## 2. Subsystem A: Route Discovery & Transform Pipeline

### 2.1 Route Collector

Collects all registered routes from the framework and converts them to an internal representation.

**Contract (`RouteCollector`):**
```php
interface RouteCollector
{
    /** @return Route[] */
    public function collect(): array;
}

interface Route
{
    public function getMethod(): string;
    public function getUri(): string;
    public function getHandler(): MethodReflector;   // controller method
    public function getMiddleware(): array;
    public function getName(): ?string;
}
```

**Laravel implementation:**
- Reads `Route::getRoutes()` from the Laravel router
- Extracts URI, methods, controller action, middleware, name
- Resolves controller method via Reflection

### 2.2 Operation Transformer Chain

Each `Route` is fed through a chain of `OperationExtension` instances that mutate an `Operation` builder object.

**Contract:**
```php
abstract class OperationExtension
{
    /**
     * Modify the operation before it is added to the spec.
     * Route context includes the controller method, middleware, etc.
     */
    abstract public function handle(Operation $operation, Route $route): void;
}
```

**Built‑in transformers (Laravel‑specific):**

| Extension | Responsibility |
|-----------|---------------|
| `RequestEssentialsExtension` | Map FormRequest → requestBody + query parameters |
| `ResponseExtension` | Map controller return type → response schemas |
| `ModelBindingsExtension` | Map route {model} binding → path parameter |
| `ValidationRulesExtension` | Map FormRequest rules() → parameter constraints |
| `PaginationExtension` | Detect paginated responses, wrap in page schema |
| `StatelessFilterExtension` | Filter routes that return HTML/views |

### 2.3 Document Transformer

Post‑processing hook after all operations are assembled.

```php
interface DocumentTransformer
{
    public function __invoke(OpenApi $openApi, RouteCollector $collector): void;
}
```

Used for: adding global security schemes, servers, tags, external docs.

---

## 3. Subsystem B: Infer Engine (Static Analysis)

The Infer engine performs type inference at the AST level, resolving PHP types that cannot be determined from signatures alone (e.g., return types of `query()` builder chains, magic `__get` properties, `map()` helpers).

### 3.1 Core Architecture

```
TypeInferer (NodeVisitor)
    │
    ├── Scope ─── per-file scope tracking (variables, `$this`, function calls)
    │   └── Index ─── lazy class/function definition index
    │
    ├── MethodReturnTypeExtension ─── resolve method return types
    ├── FunctionReturnTypeExtension ─── resolve function return types
    ├── PropertyTypeExtension ─── resolve property types
    ├── AfterClassDefinitionCreatedExtension ─── post-class-definition hooks
    └── CustomTypeInferer ─── fallback user extensions
```

**Entry point:**
```php
final class TypeInferer
{
    public function __construct(
        private readonly Scope             $scope,
        private readonly Index             $index,
        /** @var MethodReturnTypeExtension[] */
        private readonly array             $methodReturnTypeExtensions,
        /** @var FunctionReturnTypeExtension[] */
        private readonly array             $functionReturnTypeExtensions,
        /** @var PropertyTypeExtension[] */
        private readonly array             $propertyTypeExtensions,
        // ...
    ) {}

    public function analyse(string $filePath): void { /* walks AST */ }
}
```

### 3.2 Extension Points

#### 3.2.1 `MethodReturnTypeExtension`

Resolves the return type of a method call when the declared return type is insufficient.

```php
interface MethodReturnTypeExtension
{
    /**
     * @return Type|null The inferred type, or null to fall through
     */
    public function resolveReturnType(MethodReflector $method, Scope $scope): ?Type;
}
```

**Example usage:** `query(\App\Models\Book::class)->find(...)` returns `Book`, not `QueryBuilder`.

#### 3.2.2 `FunctionReturnTypeExtension`

Same as above but for global functions / `use function` imports.

```php
interface FunctionReturnTypeExtension
{
    public function resolveReturnType(FunctionReflector $function, Scope $scope): ?Type;
}
```

#### 3.2.3 `PropertyTypeExtension`

Resolves the type of a property, including magic properties and dynamic relationships.

```php
interface PropertyTypeExtension
{
    public function resolvePropertyType(PropertyReflector $property, Scope $scope): ?Type;
}
```

#### 3.2.4 Event Hooks

Extensions can register for lifecycle events:

| Event | Extension | When Fired |
|-------|-----------|------------|
| `AfterClassDefinitionCreated` | `AfterClassDefinitionCreatedExtension` | After a class definition node is fully resolved |
| `AfterMethodAnalysed` | (internal) | After a method body is analysed |
| `AfterPropertyAnalysed` | (internal) | After a property definition is resolved |

### 3.3 Scope & Index

**`Scope`:** Tracks the context of the current file being analysed:
- Current class, method, function
- Variable table (`$var → Type`)
- `$this` type resolution
- Import resolution (`use` statements)

**`Index`:** Lazy‑loading registry of all classes and functions in the project:
- `Index::getClass(string $name): ?ClassReflector`
- `Index::getFunction(string $name): ?FunctionReflector`
- `Index::getClassesByAttribute(string $attribute): ClassReflector[]`
- All accessors trigger lazy loading from the project source tree.

---

## 4. Subsystem C: OpenAPI 3.1 Type System

A pure‑PHP type representation that bridges inferred PHP types → OAS 3.1 schemas.

### 4.1 Type Interface

```php
interface Type
{
    public function isEqual(Type $other): bool;
    public function traverse(callable $callback): void;
    public function __toString(): string;
}
```

### 4.2 Type Tree

```
Type (interface)
├── ArrayType
│   ├── value: Type (element type)
│   └── isList: bool
├── ObjectType
│   ├── className: string
│   └── properties: PropertyType[] (name + Type)
├── NamedObjectType   (extends ObjectType)
│   └── resolved from a class name
├── UnionType
│   └── types: Type[]
├── IntersectionType
│   └── types: Type[]
├── GenericType
│   ├── name: string (class name)
│   └── generics: Type[]
├── EnumType
│   ├── className: string
│   └── values: string[] | int[]
├── ScalarType
│   └── name: 'string' | 'int' | 'float' | 'bool'
├── NeverType / VoidType / MixedType / NullType
├── LiteralType
│   ├── LiteralString (value: string)
│   └── LiteralInteger (value: int)
├── IterableType
│   └── value: Type
└── Nullable (wraps any Type, adds nullable flag)
```

### 4.3 Type Resolution Rules

| PHP Declared Type | Type Node | OAS Schema |
|-------------------|-----------|------------|
| `int` | `ScalarType('int')` | `{ type: integer }` |
| `float` | `ScalarType('float')` | `{ type: number, format: float }` |
| `string` | `ScalarType('string')` | `{ type: string }` |
| `bool` | `ScalarType('bool')` | `{ type: boolean }` |
| `array` | `ArrayType(MixedType)`, refined via context | `{ type: array }` |
| `ClassName` | `NamedObjectType('ClassName')` | `{ $ref: '#/components/schemas/ClassName' }` |
| `?type` | `Nullable(wraps Type)` | `{ nullable: true, ... }` |
| `UnionType` | `UnionType([Type, Type])` | `{ oneOf: [...] }` or `{ nullable: true }` shortcut |
| `BackedEnum` | `EnumType('Enum', values)` | `{ type: string, enum: [...] }` |
| `iterable<Book>` | `IterableType(ObjectType('Book'))` | `{ type: array, items: { $ref: ... } }` |
| `void` | `VoidType` | (no response body) |
| `never` | `NeverType` | (no response body) |

---

## 5. Subsystem D: OpenAPI 3.1 Builder (Generator)

Builder classes that construct the OAS 3.1 document model. All classes are plain DTO builders with `__toString(): string` that returns JSON.

### 5.1 Structure

```
OpenApi (root)
├── openapi: string ("3.1.0")
├── info: Info
├── servers: Server[]
├── paths: Paths
│   └── PathItem[]
│       ├── summary, description
│       └── operations: Operation[]
│           ├── operationId, summary, description, tags, deprecated
│           ├── parameters: Parameter[]
│           ├── requestBody: RequestBody
│           ├── responses: Response[]
│           └── security: SecurityRequirement[]
├── components: Components
│   ├── schemas: Schema[]
│   ├── responses: Response[]
│   ├── parameters: Parameter[]
│   ├── securitySchemes: SecurityScheme[]
│   └── ...
├── tags: Tag[]
├── externalDocs: ExternalDocs
└── jsonSchemaDialect: string
```

### 5.2 Key Builder Classes

| Class | Purpose |
|-------|---------|
| `OpenApi` | Root document, `addPath()`, `addSchema()`, etc. |
| `Info` | title, version, description, contact, license |
| `PathItem` | summary, description, GET/POST/PUT/etc. operations |
| `Operation` | operationId, parameters, requestBody, responses |
| `Parameter` | name, in (path/query/header/cookie), schema, required |
| `RequestBody` | content (media type → schema), required, description |
| `Response` | description, content, headers |
| `Schema` | Fully compliant OAS 3.1 schema object |
| `Components` | Container for reusable schemas, responses, etc. |
| `SecurityScheme` | http, apiKey, oauth2, openIdConnect |
| `Server` | url, description, variables |
| `Tag` | name, description, externalDocs |
| `ExternalDocs` | url, description |

### 5.3 Schema Object Structure

```php
final class Schema
{
    // Typed properties for every OAS 3.1 schema keyword:
    public ?string $type;             // string | number | integer | boolean | array | object | null
    public ?string $format;           // int32, int64, float, double, byte, binary, date, date-time, etc.
    public ?string $description;
    public ?bool $nullable;
    public ?bool $readOnly;
    public ?bool $writeOnly;
    public ?bool $deprecated;
    public ?Schema $items;
    public ?Schema $additionalProperties;
    /** @var Schema[] */
    public ?array $properties;
    /** @var string[] */
    public ?array $required;
    /** @var mixed */
    public $example;
    public ?mixed $default;
    /** @var string[] */
    public ?array $enum;
    /** @var int|float|null */
    public $minimum;
    /** @var int|float|null */
    public $maximum;
    public ?int $minLength;
    public ?int $maxLength;
    public ?string $pattern;
    /** @var Schema[] */
    public ?array $oneOf;
    /** @var Schema[] */
    public ?array $anyOf;
    /** @var Schema[] */
    public ?array $allOf;
    public ?Schema $not;
    public ?string $ref;   // $ref (OAS 3.1 uses string, not object)
}
```

### 5.4 TypeTransformer

Core transformation engine: `PHP Type → Schema`.

```php
final class TypeTransformer
{
    /** @var TypeToSchemaExtension[] */
    private array $extensions;

    public function transform(Type $type, ?string $name = null): Schema;
    public function addExtension(TypeToSchemaExtension $extension): void;
}
```

Resolution order:
1. Iterate `TypeToSchemaExtension` chain (first match wins)
2. Fall back to built-in type→schema mapping (scalar, array, object, union, etc.)
3. Register unknown named types in `Components::schemas` as `$ref`

---

## 6. Subsystem E: Extension Contracts

All extension points that framework bindings implement.

### 6.1 `TypeToSchemaExtension`

```php
abstract class TypeToSchemaExtension
{
    /**
     * @return Schema|null Return a Schema if this extension handles $type, null to pass to next.
     */
    abstract public function handle(Type $type, TypeTransformer $transformer): ?Schema;
}
```

**Framework binding examples:**
- `ModelToSchemaExtension` — converts a model class to an object schema
- `FormRequestToSchemaExtension` — converts a FormRequest to a requestBody schema
- `EnumToSchemaExtension` — converts a PHP backed enum to enum schema
- `PaginationToSchemaExtension` — wraps array schemas in pagination envelope
- `DateTimeToSchemaExtension` — converts DateTime to string with format

### 6.2 `OperationExtension`

```php
abstract class OperationExtension
{
    abstract public function handle(Operation $operation, Route $route): void;
}
```

**Framework binding examples:**
- `RequestEssentialsExtension` — maps request DTO → parameters + requestBody
- `ResponseExtension` — maps return type → response schemas
- `ModelBindingsExtension` — maps route model binding → path parameter
- `ValidationRulesExtension` — maps validation rules → parameter constraints
- `PaginationExtension` — detects pagination → wraps response schema
- `StatelessFilterExtension` — filters non-API routes

### 6.3 `ExceptionToResponseExtension`

```php
abstract class ExceptionToResponseExtension
{
    abstract public function handle(Throwable $exception, Route $route): ?Operation;
}
```

**Framework binding examples:**
- `ValidationExceptionToResponse` — 422 with field errors
- `ModelNotFoundExceptionToResponse` — 404
- `AuthenticationExceptionToResponse` — 401
- `AuthorizationExceptionToResponse` — 403
- `GenericExceptionToResponse` — 500 fallback

### 6.4 `CustomMediaTypeExtension`

```php
abstract class CustomMediaTypeExtension
{
    abstract public function handle(string $mediaType, TypeTransformer $transformer): ?Schema;
}
```

### 6.5 `AfterOpenApiGenerated`

```php
interface AfterOpenApiGenerated
{
    public function __invoke(OpenApi $openApi): void;
}
```

---

## 7. Configuration

```php
final class OpenApiConfig
{
    public string $title = '';
    public string $version = '1.0.0';
    public string $description = '';
    public string $format = 'json';        // json | yaml
    /** @var Server[] */
    public array $servers = [];
    public bool $debug = false;
    public ?string $cachePath = null;
    public ?string $uiPath = '/openapi';
    public string $uiTheme = 'elements';   // elements | scalar | swagger
}
```

### 7.1 CLI Commands

| Command | Purpose |
|---------|---------|
| `openapi:generate` | Scan routes, infer types, build spec in memory |
| `openapi:export` | Write generated spec to `openapi.json` / `openapi.yaml` |

### 7.2 Caching

- Full spec cache keyed by file modification timestamps
- Stored as serialized `OpenApi` object
- Cleared when any analysed file changes

---

## 8. Extension Point Reference Table

| Interface / Abstract | Method(s) | Returns | When Called |
|---------------------|-----------|---------|-------------|
| `RouteCollector` | `collect(): Route[]` | `Route[]` | Start of generation |
| `OperationExtension` | `handle(Operation, Route): void` | `void` | For each Route, after initial Operation creation |
| `DocumentTransformer` | `__invoke(OpenApi, RouteCollector): void` | `void` | After all operations built |
| `MethodReturnTypeExtension` | `resolveReturnType(MethodReflector, Scope): ?Type` | `?Type` | When analysing method call return |
| `FunctionReturnTypeExtension` | `resolveReturnType(FunctionReflector, Scope): ?Type` | `?Type` | When analysing function call return |
| `PropertyTypeExtension` | `resolvePropertyType(PropertyReflector, Scope): ?Type` | `?Type` | When resolving property type |
| `AfterClassDefinitionCreatedExtension` | `handle(ClassReflector, Scope): void` | `void` | After class definition analysed |
| `TypeToSchemaExtension` | `handle(Type, TypeTransformer): ?Schema` | `?Schema` | When converting Type → Schema |
| `ExceptionToResponseExtension` | `handle(Throwable, Route): ?Operation` | `?Operation` | When building error responses |
| `CustomMediaTypeExtension` | `handle(string, TypeTransformer): ?Schema` | `?Schema` | When resolving custom media types |
| `AfterOpenApiGenerated` | `__invoke(OpenApi): void` | `void` | After full document assembly |

---

## 9. Notes on the Clean‑Room Process

- All contracts above are derived from reading the `dedoc/scramble` source tree, documentation, and test files.
- Every interface name, method signature, and type tree node has been re-expressed in this document without copying original source code.
- The architecture is deliberately framework‑agnostic. `Route` is an interface, not an Eloquent model. `Type` is an interface, not tied to any reflection library.
- Any implementation starting from this spec must strip all `Illuminate\*` dependencies and replace them with framework‑specific adapters (as documented in the companion Tempest binding spec).
