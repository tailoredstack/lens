# Features Matrix — Tempest OpenAPI

> **Legend:**
> - ✅ = Fully supported
> - ⚠️ = Partially supported (functionally similar, different mechanism)
> - ❌ = Not supported / Not applicable
> - 🚧 = In progress (currently being implemented)

---

## A. Route Discovery

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| A01 | Collect routes from controllers | ✅ | `RouteDiscovery` + `ClassReflector` |
| A02 | HTTP method attributes (GET, POST, PUT...) | ✅ | `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]` |
| A03 | Route URI prefix | ✅ | `#[Prefix('/api')]` attribute |
| A04 | Route middleware | ✅ | `#[WithMiddleware]` attribute |
| A05 | Route model binding | ✅ | `Bindable` interface + `IsBindingValue` attribute |
| A06 | API / web route separation | ✅ | `#[Stateless]` decorator |
| A07 | Named routes | ❌ | Tempest has no route names; `operationId` uses `Controller::method` |
| A08 | Route groups / resource controllers | ❌ | No `Route::resource()` macro; CRUD routes registered individually |
| A09 | Route fallback / catch-all | ❌ | Not a framework concept |
| A10 | Route caching | ⚠️ | Uses Tempest discovery cache instead of Laravel route cache |

---

## B. Request Inference

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| B01 | Request DTO → requestBody schema | ✅ | Public properties of Request subclass mapped to schema |
| B02 | Query parameter inference (GET/DELETE) | ✅ | `$request->query` → OAS `in: query` parameters |
| B03 | Path parameter inference | ✅ | `{id}` in URI + method parameter type |
| B04 | Request body inference (POST/PUT/PATCH) | ✅ | `$request->body` → `requestBody` |
| B05 | File upload inference | ✅ | `$request->files` → `format: binary` |
| B06 | Header parameter inference | ⚠️ | Accessible but not typically validated via DTO |
| B07 | Cookie parameter inference | ⚠️ | Accessible but rarely used |
| B08 | `#[MapFrom]` name aliasing | ✅ | Maps input key → different property name |
| B09 | FormRequest `rules()` method | ❌ | Tempest uses attribute rules instead of method |
| B10 | FormRequest `authorize()` method | ❌ | No per‑request authorization gate |

---

## C. Response Inference

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| C01 | JSON response (`new Json(...)`) | ✅ | Explicit JSON response class |
| C02 | Created response (`new Created(...)`) | ✅ | HTTP 201 with body |
| C03 | Generic response (`new Ok(...)`) | ✅ | HTTP 200 with body |
| C04 | Array return → 200 JSON | ✅ | Auto‑wrapped by `GenericRouter::createResponse()` |
| C05 | View return → HTML | ✅ | `View` interface response |
| C06 | NotFound response | ✅ | HTTP 404 |
| C07 | ServerError response | ✅ | HTTP 500 |
| C08 | Redirect response | ✅ | 301/302/307/308 |
| C09 | Custom response classes | ✅ | Any class implementing `Response` interface |
| C10 | Empty / No Content response | ✅ | `Status::NO_CONTENT` |
| C11 | Paginated response | ❌ | No built‑in paginator in Tempest v3.11.3 |

---

## D. Type Inference

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| D01 | PHP built‑in scalar types | ✅ | `int`, `float`, `string`, `bool` |
| D02 | PHP docblock types (`@param`, `@return`) | ✅ | Via `phpstan/phpdoc-parser` |
| D03 | Union / intersection types | ✅ | `UnionType`, `IntersectionType` nodes |
| D04 | Nullable types (`?type`) | ✅ | `Nullable` wrapper node |
| D05 | Array / iterable types | ✅ | `ArrayType`, `IterableType` nodes |
| D06 | Generic / template types | ✅ | `GenericType` node for `QueryBuilder<Model>` |
| D07 | Enum types (backed enums) | ✅ | `EnumType` node |
| D08 | Model property types | ✅ | Via `ModelInspector::getValueFields()` |
| D09 | Model relation types | ✅ | `#[BelongsTo]`, `#[HasMany]`, `#[HasOne]`, etc. |
| D10 | Query builder return type resolution | ✅ | `query(Model::class)` → generic type |
| D11 | `map()` helper return type resolution | ✅ | `map($data)->with(...)->to($class)` → target class |
| D12 | View data array inference | ✅ | `->data(...)` key extraction |

---

## E. Schema Generation

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| E01 | Model → object schema | ✅ | Properties from `getValueFields()` |
| E02 | Request DTO → requestBody schema | ✅ | Custom properties minus base Request props |
| E03 | Enum → string schema + enum values | ✅ | Backed enum values extracted |
| E04 | DateTime → string + format | ✅ | `date-time`, `date`, `time` formats |
| E05 | Nested relations → `$ref` | ✅ | Circular reference safe |
| E06 | Array/collection → typed items | ✅ | Item type resolved recursively |
| E07 | Paginated → envelope schema | ❌ | No paginated type — blocked by missing paginator |
| E08 | `#[Hidden]` property exclusion | ✅ | Skipped in schema generation |
| E09 | `#[Virtual]` property exclusion | ✅ | Skipped in schema generation |
| E10 | `PrimaryKey` → integer/string | ✅ | Unwrapped to scalar type |
| E11 | `#[SerializeAs]` custom property name | ✅ | Alternative schema property name |
| E12 | API Resources / JsonResource | ❌ | Tempest has no resource layer |

---

## F. Validation → OAS Constraints

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| F01 | `#[HasLength(min, max)]` → minLength/maxLength | ✅ | |
| F02 | `#[IsEmail]` → format: email | ✅ | |
| F03 | `#[IsIn(...)]` → enum | ✅ | |
| F04 | `#[IsBetween(min, max)]` → minimum/maximum | ✅ | |
| F05 | `#[IsUrl]` → format: uri | ✅ | |
| F06 | `#[IsUuid]` → format: uuid | ✅ | |
| F07 | `#[MatchesRegEx]` → pattern | ✅ | |
| F08 | `#[IsInteger]` → type: integer | ✅ | |
| F09 | `#[IsBoolean]` → type: boolean | ✅ | |
| F10 | `#[IsNumeric]` → type: number | ✅ | |
| F11 | `#[HasCount(min, max)]` → minItems/maxItems | ✅ | |
| F12 | `#[HasDateTimeFormat('Y-m-d')]` → format: date | ✅ | |
| F13 | `#[IsNotNull]` → nullable: false | ✅ | |
| F14 | `#[IsNotEmptyString]` → minLength: 1 | ✅ | |
| F15 | `#[IsAlpha]` → pattern | ✅ | `^[a-zA-Z]+$` |
| F16 | `#[IsAlphaNumeric]` → pattern | ✅ | `^[a-zA-Z0-9]+$` |
| F17 | `#[IsAfterDate]` / `#[IsBeforeDate]` → min/max | ✅ | Date string comparison |

---

## G. Exception Handling

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| G01 | `ValidationFailed` → 422 with field errors | ✅ | Full error body + `x-validation` header |
| G02 | `HttpRequestFailed` → dynamic status | ✅ | Uses exception's status code |
| G03 | `AccessWasDenied` → 403 | ✅ | Authorization failure |
| G04 | Generic `Exception` → 500 | ✅ | Debug mode: detailed; production: message only |
| G05 | `ConvertsToResponse` interface | ✅ | Custom exception rendering |
| G06 | Priority‑ordered exception renderers | ✅ | `#[Priority]` attribute matching Tempest system |
| G07 | Content‑negotiated error format | ✅ | `Request::accepts(ContentType::JSON)` check |
| G08 | `ModelNotFoundException` → 404 | ⚠️ | No dedicated exception; detect null from `findById()` |
| G09 | 401 Unauthorized vs 403 Forbidden | ⚠️ | Tempest uses 403 for both; no separate 401 exception |
| G10 | Rate limiting (429) | ❌ | Not a framework concern |

---

## H. Configuration

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| H01 | OpenAPI metadata (title, version, desc) | ✅ | Via `OpenApiConfig` |
| H02 | Server URL(s) | ✅ | Multiple servers supported |
| H03 | Security schemes (Bearer, apiKey, OAuth2) | ✅ | Standard OAS security schemes |
| H04 | Tags | ✅ | For endpoint grouping |
| H05 | Output format (JSON / YAML) | ✅ | Configurable |
| H06 | Debug mode | ✅ | Toggles error detail verbosity |
| H07 | Custom media types | ✅ | Via `CustomMediaTypeExtension` |
| H08 | Config file auto‑discovery | ✅ | Tempest `openapi.config.php` convention |

---

## I. CLI & Tooling

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| I01 | `openapi:generate` CLI command | ✅ | Scan routes, build spec, output stats |
| I02 | `openapi:export` CLI command | ✅ | Write spec JSON/YAML to file |
| I03 | `#[Get('/openapi')]` UI controller | ✅ | Serve Stoplight Elements / Scalar |
| I04 | Configurable UI theme | ✅ | elements | scalar | swagger |
| I05 | Spec caching | ✅ | File‑based cache via `internal_storage_path()` |
| I06 | Incremental re‑scan | ✅ | File modification timestamps |
| I07 | Discovery cache integration | ✅ | Leverages Tempest's discovery cache |

---

## J. Extension System

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| J01 | `TypeToSchemaExtension` | ✅ | Custom type → schema mapping |
| J02 | `OperationExtension` | ✅ | Custom operation enrichment |
| J03 | `ExceptionToResponseExtension` | ✅ | Custom exception → OAS response |
| J04 | `AfterOpenApiGenerated` | ✅ | Post-generation hook |
| J05 | `CustomMediaTypeExtension` | ✅ | Custom media type resolution |
| J06 | `Infer\MethodReturnTypeExtension` | ✅ | Custom method return type resolution |
| J07 | `Infer\FunctionReturnTypeExtension` | ✅ | Custom function return type resolution |
| J08 | `Infer\PropertyTypeExtension` | ✅ | Custom property type resolution |
| J09 | `Infer\AfterClassDefinitionCreated` | ✅ | Post-class-definition hook |

---

## Summary Counts

| Section | Total | ✅ | ⚠️ | ❌ | 🚧 | % Ready |
|---------|-------|---|---|---|----|---------|
| A. Route Discovery | 10 | 6 | 1 | 3 | 0 | 60% |
| B. Request Inference | 10 | 6 | 2 | 2 | 0 | 60% |
| C. Response Inference | 11 | 9 | 0 | 1 | 0 | 82% |
| D. Type Inference | 12 | 12 | 0 | 0 | 0 | 100% |
| E. Schema Generation | 12 | 9 | 0 | 2 | 0 | 75% |
| F. Validation → OAS | 17 | 17 | 0 | 0 | 0 | 100% |
| G. Exception Handling | 10 | 7 | 2 | 1 | 0 | 70% |
| H. Configuration | 8 | 8 | 0 | 0 | 0 | 100% |
| I. CLI & Tooling | 7 | 7 | 0 | 0 | 0 | 100% |
| J. Extension System | 9 | 9 | 0 | 0 | 0 | 100% |
| **Total** | **106** | **90** | **5** | **9** | **0** | **85%** |

**Key:**
- ✅ Fully supported: **90/106 (85%)**
- ⚠️ Partially supported: **5/106 (5%)** — functional but different mechanism
- ❌ Not supported: **9/106 (8%)** — no Tempest equivalent
- 🚧 In progress: **0/106 (0%)** — implementation not started
