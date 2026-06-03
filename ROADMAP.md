# ROADMAP — Tempest OpenAPI Development Plan

> **Project:** `tempest/openapi` — OpenAPI 3.1 spec generator for Tempest PHP
> **Duration estimate:** 8 weeks (part-time)
> **Status:** Pre‑development (all specs written)

---

## Phase 0: Foundation (Week 1)

### 0.1 Scaffold Package Structure
- [ ] Create `composer.json` with namespace `Tempest\OpenApi`
- [ ] Set up `PSR-4` autoloading for `Tempest\OpenApi\*`
- [ ] Create directory tree:
  ```
  src/
    Config/
    Commands/
    Controllers/
    Discovery/
    Extensions/
      Exception/
      Operation/
      TypeToSchema/
    Infer/
      Extensions/
      Scope/
    Support/
      Generator/
      Type/
  ```
- [ ] Require `tempest/framework` as dev dependency in `composer.json`
- [ ] Add `.phpunit.xml`, `phpstan.neon`, `rector.php` configs

### 0.2 Copy Core from Scramble Analysis
- [ ] Re‑implement the 5 subsystems from `SPEC-LARAVEL.md`:
  - [ ] Infer engine (TypeInferer, Scope, Index)
  - [ ] PHP type system (Type interface + 48 types)
  - [ ] OpenAPI builder classes (OpenApi, Operation, Schema, Parameter, Response, etc.)
  - [ ] TypeTransformer (PHP type → Schema)
- [ ] All core files use **no framework dependencies** — pure PHP
- [ ] All core files tested in isolation

### 0.3 Renamespace to Tempest\OpenApi\*
- [ ] Move all core files into `Tempest\OpenApi\Support\*`
- [ ] Move Infer into `Tempest\OpenApi\Infer\*`
- [ ] Move extensions base classes into `Tempest\OpenApi\Extensions\*`
- [ ] Verify no `Illuminate\*` or `Scramble\*` references remain

### 0.4 Create OpenApiConfig
- [ ] Implement `Tempest\OpenApi\Config\OpenApiConfig`
- [ ] Add default values (title, version, format, servers)
- [ ] Create default config file template: `openapi.config.php`

### 0.5 Wire via Tempest Discovery
- [ ] Create `OpenApiPackage` installer class
- [ ] Register `OpenApiRouteDiscovery` with Discovery system
- [ ] Register `OpenApiConfigDiscovery` for config auto-loading
- [ ] Verify package auto-registers when installed via Composer

### 0.6 Verify Phase 0
- [ ] `composer install` succeeds
- [ ] Core unit tests pass (no framework deps)
- [ ] `phpstan` level max passes for core
- [ ] Package discovered by Tempest app

---

## Phase 1: Route Discovery (Week 1–2)

### 1.1 Implement OpenApiRouteDiscovery
- [ ] Build `Discovery` implementation per Section 2.1 of SPEC-TEMPEST
- [ ] Iterate `ClassReflector::getPublicMethods()`
- [ ] Collect `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]` etc.
- [ ] Apply `#[Prefix]`, `#[WithMiddleware]`, `#[Stateless]` decorators

### 1.2 Route Attribute Collector
- [ ] Parse each route attribute → `OpenApiDiscoveredRoute`
- [ ] Extract URI, method, handler, middleware list
- [ ] Handle `#[Prefix]` URI prepending

### 1.3 URI Parameter Type Extraction
- [ ] Replicate `DiscoveredRoute::parseUriAndParameters()` logic
- [ ] Map parameter types: `int → integer`, `float → number`, `bool → boolean`
- [ ] Handle optional parameters (`{?id}` syntax)

### 1.4 #[Stateless] Filter
- [ ] Detect `#[Stateless]` decorator
- [ ] Exclude non‑stateless routes from spec (unless config override)
- [ ] Add config toggle `includeStatefulRoutes`

### 1.5 Verify Phase 1
- [ ] Tempest app with 5+ routes generates correct route list
- [ ] `#[Stateless]` routes correctly filtered
- [ ] URI parameters correctly typed
- [ ] `#[Prefix('/api')]` applied
- [ ] Unit tests: `OpenApiDiscoveredRoute`, `OpenApiRouteDiscovery`

---

## Phase 2: Operation Transformers (Week 2–3)

### 2.1 TempestRequestEssentialsExtension
- [ ] Detect Request DTO in handler parameters
- [ ] Extract public properties via ClassReflector
- [ ] Map to requestBody (POST/PUT/PATCH) or query parameters (GET/DELETE)
- [ ] Apply `#[MapFrom]` name aliasing for parameters
- [ ] Unit test: Request DTO → operation parameters

### 2.2 TempestResponseExtension
- [ ] Map return types: `Ok`, `Created`, `Json`, `array`, `View`, `NotFound`
- [ ] Handle `GenericRouter::createResponse()` auto‑wrap of `array` → `Ok`
- [ ] Extract schema from response body type
- [ ] Unit test: each response type → correct OAS response

### 2.3 TempestModelBindingsExtension
- [ ] Detect `Bindable` model route parameters
- [ ] Add path parameter schema
- [ ] Add `$ref` to model component schema
- [ ] Unit test: `{id}` → path parameter + model ref

### 2.4 TempestValidationExtension
- [ ] Map each `#[Rule]` attribute → OAS constraint
- [ ] Cover all 50 validation rules
- [ ] Handle combinational constraints (e.g., `#[HasLength]` + `#[IsEmail]`)
- [ ] Unit test: each rule → correct schema keyword

### 2.5 TempestPaginationExtension
- [ ] Detect paginated query builder calls
- [ ] Build pagination envelope schema
- [ ] Note: may be a no-op if no pagination convention found
- [ ] Unit test: paginated → envelope schema

### 2.6 TempestStatelessFilterExtension
- [ ] Filter non‑API routes (already done at discovery level)
- [ ] Verify integration with discovery filter

### 2.7 Verify Phase 2
- [ ] All 6 transformers chain correctly
- [ ] Integration test: Tempest controller → full operation spec
- [ ] All edge cases documented in test matrix

---

## Phase 3: Infer Engine Extensions (Week 3–4)

### 3.1 TempestQueryReturnTypeExtension
- [ ] Detect `query(Model::class)` function calls
- [ ] Trace generic type parameter through builder chain
- [ ] Resolve `.find()`, `.all()`, `.get()`, `.first()`, `.create()`
- [ ] Unit test: query chain → resolved types

### 3.2 TempestViewResultExtension
- [ ] Detect `View` interface return types
- [ ] Trace `->data(...)` calls for key extraction
- [ ] Return `ObjectType` with inferred keys
- [ ] Unit test: View with data → typed properties

### 3.3 TempestMapperReturnTypeExtension
- [ ] Detect `map($from)->with(...)->to($class)` chain
- [ ] Extract target class from `->to()`
- [ ] Return `NamedObjectType`
- [ ] Unit test: map chain → resolved target type

### 3.4 TempestModelPropertyExtension
- [ ] Check for `IsDatabaseModel` trait
- [ ] Resolve property types (scalar, PrimaryKey, relations)
- [ ] Auto‑detect BelongsTo via plain class type
- [ ] Exclude `#[Virtual]`, `#[Hidden]` properties
- [ ] Unit test: model → ObjectType with resolved properties

### 3.5 TempestRequestValidationExtension
- [ ] Hook `AfterClassDefinitionCreated` event
- [ ] Detect Request subclasses
- [ ] Collect validation rule attributes per property
- [ ] Store as property metadata
- [ ] Unit test: Request DTO → validation metadata

### 3.6 Verify Phase 3
- [ ] All 5 Infer extensions resolve correctly
- [ ] Integration test: complex controller with query, map, model → full type resolution
- [ ] phpstan level max passes

---

## Phase 4: Type → Schema Extensions (Week 4–5)

### 4.1 TempestModelToSchemaExtension
- [ ] Use `ModelInspector::getValueFields()` for property list
- [ ] Map each field type → Schema property
- [ ] Handle `PrimaryKey` type → integer/string
- [ ] Register in Components schemas
- [ ] Unit test: Book model → object schema with all fields

### 4.2 TempestRequestToSchemaExtension
- [ ] Exclude base `Request` interface properties
- [ ] Build requestBody schema from custom properties
- [ ] Apply `#[MapFrom]` name aliasing
- [ ] Unit test: CreateBookRequest → requestBody schema

### 4.3 TempestEnumToSchemaExtension
- [ ] Detect `EnumType`
- [ ] Extract backing type and case values
- [ ] Register in Components schemas
- [ ] Unit test: StatusEnum → { type: string, enum: [...] }

### 4.4 TempestPaginationToSchemaExtension
- [ ] Detect paginated wrapper
- [ ] Build envelope schema with `data`, `current_page`, etc.
- [ ] Unit test: paginated type → envelope schema

### 4.5 TempestDateTimeToSchemaExtension
- [ ] Detect DateTimeInterface / DateTime / DateTimeImmutable
- [ ] Map to `{ type: string, format: date-time }`
- [ ] Check `#[HasDateTimeFormat]` for custom formats
- [ ] Unit test: DateTime → date-time, `#[HasDateTimeFormat('Y-m-d')]` → date

### 4.6 TempestCollectionToSchemaExtension
- [ ] Detect `ArrayType`, `IterableType`, `ImmutableArray`
- [ ] Transform inner type recursively
- [ ] Unit test: `array<Book>` → `{ type: array, items: { $ref: Book } }`

### 4.7 Verify Phase 4
- [ ] All 6 Type→Schema extensions convert correctly
- [ ] Integration test: model with relations, enum fields, dates → full components schemas
- [ ] Test `$ref` generation and deduplication

---

## Phase 5: Exception Mappers (Week 5)

### 5.1 TempestValidationExceptionToResponse
- [ ] Match `ValidationFailed` exception
- [ ] Build 422 response schema with errors shape
- [ ] Add `x-validation` header
- [ ] Unit test: ValidationFailed → 422 with error body

### 5.2 TempestHttpExceptionToResponse
- [ ] Match `HttpRequestFailed` exception
- [ ] Extract status code from exception
- [ ] Build response schema with message
- [ ] Unit test: HttpRequestFailed → dynamic status response

### 5.3 TempestGenericExceptionToResponse
- [ ] Fallback 500 response
- [ ] Debug mode: include exception details
- [ ] Production mode: just message
- [ ] Unit test: generic → 500

### 5.4 Verify Phase 5
- [ ] Exception mappers respond in correct priority order
- [ ] Integration test with JsonExceptionRenderer behavior
- [ ] Test local vs production mode

---

## Phase 6: CLI & UI (Week 5–6)

### 6.1 openapi:generate Command
- [ ] Implement `#[ConsoleCommand('openapi:generate')]`
- [ ] Run full generation pipeline
- [ ] Output route count, schema count, file size
- [ ] Handle errors gracefully

### 6.2 openapi:export Command
- [ ] Implement `#[ConsoleCommand('openapi:export')]`
- [ ] Write spec to file (JSON or YAML based on config)
- [ ] Accept optional custom file path
- [ ] Pretty‑print JSON output

### 6.3 UI Controller
- [ ] Implement `#[Get('/openapi')]` controller
- [ ] Serve Stoplight Elements UI (CDN)
- [ ] Pass generated spec via embedded `<script>` tag
- [ ] Configurable UI theme (elements | scalar | swagger)

### 6.4 Verify Phase 6
- [ ] Both CLI commands work end‑to‑end
- [ ] UI controller renders in browser
- [ ] Spec download link works

---

## Phase 7: Testing (Week 6–7)

### 7.1 Unit Tests
- [ ] Every extension class has unit tests
- [ ] Every Type node has serialization tests
- [ ] Every Schema builder has valid output tests
- [ ] Exception mappers tested with mock exceptions

### 7.2 Integration Test — Sample App
- [ ] Create a Tempest sample app with:
  - [ ] 5+ controllers (CRUD for Books, Authors)
  - [ ] Request DTOs with validation
  - [ ] IsDatabaseModel models with relations
  - [ ] Query builder usage
  - [ ] View return
  - [ ] Custom response classes
- [ ] Generate full OpenAPI spec
- [ ] Validate spec against OAS 3.1 schema
- [ ] Verify every endpoint documented

### 7.3 Test Matrix Verification
- [ ] Run through `SPEC-TEST-MATRIX.md`
- [ ] Mark each item as ✅ / ⚠️ / ❌
- [ ] Document any new edge cases found during testing

### 7.4 Edge Case Analysis
- [ ] Test empty controllers (no routes)
- [ ] Test controllers with no return type
- [ ] Test deeply nested relations (5+ levels)
- [ ] Test union return types
- [ ] Test generic type resolution failures
- [ ] Test cache invalidation

### 7.5 Verify Phase 7
- [ ] >80% code coverage
- [ ] All tests green
- [ ] OAS 3.1 spec validators pass
- [ ] Test matrix fully populated

---

## Phase 8: Documentation & Release (Week 7–8)

### 8.1 README
- [ ] Installation instructions
- [ ] Quick start guide (3 steps)
- [ ] Configuration reference
- [ ] Extension developer guide
- [ ] CLI reference
- [ ] UI customization

### 8.2 Inline Documentation
- [ ] All public methods have PHPDoc
- [ ] All extension points documented for 3rd-party developers
- [ ] CHANGELOG generated

### 8.3 Release Prep
- [ ] Tag v1.0.0-alpha
- [ ] Create GitHub release with changelog
- [ ] Submit to Packagist
- [ ] Announce on Tempest community channels

### 8.4 Verify Phase 8
- [ ] Fresh install from `composer require tempest/openapi` works
- [ ] First‑time user can generate spec in <5 minutes
- [ ] All docs proofread

---

## Dependency Graph

```
Phase 0 (Foundation) ────┬── Phase 1 (Route Disc.) ──── Phase 2 (Op. Trans.) ────┐
                         │                                                        │
                         └── Phase 3 (Infer Exts.) ──── Phase 4 (Type→Schema) ────┤
                                                                                   │
Phase 5 (Excep. Map.) ────────────────────────────────────────────────────────────┤
                                                                                   │
Phase 6 (CLI & UI) ───────────────────────────────────────────────────────────────┤
                                                                                   │
Phase 7 (Testing) ←── all above ──────────────────────────────────────────────────┘
                                                                                   │
Phase 8 (Docs & Release) ←── test results ────────────────────────────────────────┘
```

**Critical path:** Phase 0 → Phase 1 → Phase 2 → Phase 4 (needed before integration test)
**Parallel tracks:** Phase 3 can run alongside Phase 2; Phase 5 alongside Phase 4

---

## Milestones

| Milestone | Phase | Deliverable |
|-----------|-------|-------------|
| M0: Core ready | 0 | Package installs, core tests pass, no framework deps |
| M1: Routes discovered | 1 | `openapi:export` lists all API routes |
| M2: Operations populated | 2 | Each route has request/response documented |
| M3: Types inferred | 3 | `query()`, `map()`, models, views resolved correctly |
| M4: Schemas generated | 4 | Full components/schemas with $ref resolution |
| M5: Errors documented | 5 | 422, 4xx, 5xx responses in spec |
| M6: CLI & UI operational | 6 | Commands work, UI renders |
| M7: Tested & verified | 7 | 80%+ coverage, matrix complete, spec valid |
| M8: Released | 8 | v1.0.0-alpha on Packagist |
