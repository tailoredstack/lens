# TODO — Lens OpenAPI Live Status

> **Updated:** 2026-06-04
> **Status:** Phase 0.5 complete — core implementation functional

---

## Done

- [x] Analyzed `dedoc/scramble` repo — README, source tree, all contracts, Infer engine, OpenAPI type system, extension points (~135 files mapped)
- [x] Produced framework‑agnostic clean‑room spec (SPEC-LARAVEL.md) covering all 5 subsystems
- [x] Fetched Tempest PHP docs: Introduction, Routing, Database/Models, Validation, Container, Discovery, Exception Handling
- [x] Cloned `tempestphp/tempest-framework` repo — full source inspection of all 33 packages
- [x] Verified every Scramble extension contract against Tempest source
- [x] Identified 7 corrections from source vs initial docs analysis (see notes below)
- [x] Mapped query() behavior, ModelInspector, relation auto‑detection, validation attribute system
- [x] Designed Tempest binding spec (SPEC-TEMPEST.md) — 6 operation transformers, 5 Infer extensions, 6 Type→Schema extensions, 3 exception mappers, config, CLI, UI
- [x] Created development roadmap (ROADMAP.md) — 8 phases, 8 milestones
- [x] Created test matrix (SPEC-TEST-MATRIX.md) — 46 items across 8 categories
- [x] All 5 spec documents written to project root
- [x] Created FEATURES.md — quick‑reference matrix (106 features, 90 supported)
- [x] **Phase 0.1:** Scaffold package structure (composer.json, namespace, directories)
- [x] **Phase 0.2:** Core implementation — Infer engine, Type system (19 classes), OpenAPI builder
- [x] **Phase 0.3:** Namespace set to `Lens\*`
- [x] **Phase 0.4:** Created `OpenApiConfig` config object
- [x] **Phase 0.5:** CLI command (`bin/openapi`), Discovery integration stub
- [x] Type system: 19 type classes + PropertyType DTO
- [x] Infer engine: AST parsing, type inference, namespace resolution, docblock parsing
- [x] OpenAPI builder: type→schema mapping, path generation, operation discovery
- [x] CLI: JSON/YAML output, exclude patterns, include-internal flag
- [x] 18 tests — all passing, 100% type coverage
- [x] CI pipeline: fmt → lint → analyze → type-coverage → tests
- [x] Pre-commit hook: all QA checks

## In Progress

- **Phase 1:** Tempest binding layer — route discovery, operation transformers, request/response mapping

## Next Up (Immediate)

1. **Phase 1.1:** Implement Tempest route discovery (scan controllers, read attributes)
2. **Phase 1.2:** Operation transformers (map controller methods → OpenAPI operations)
3. **Phase 1.3:** Request mapping (Tempest Request → parameters/schema)
4. **Phase 1.4:** Response mapping (return types → responses)
5. **Phase 1.5:** Validation rules → OpenAPI constraints

## Blocked

- *(none currently)*

## Known Risks / Open Questions

| # | Risk/Question | Impact | Resolution |
|---|--------------|--------|------------|
| R1 | No built-in pagination class in Tempest v3.11.3 | PaginationExtension may be a no-op | Document gap; revisit when community convention emerges |
| R2 | No `ModelNotFoundException` equivalent | 404 detection for model binding may need manual mapping | Use `null` return from `findById()` as signal |
| R3 | `Tempest\Auth` package not yet analysed | Auth scopes, authentication schemes unknown | Separate analysis needed; annotate with `#[OA\SecurityScheme]` for now |
| R4 | Cache strategy (file mod timestamps) may conflict with Tempest's internal storage | Need to align cache path with Tempest conventions | Use `internal_storage_path()` for cache |
| R5 | No route names in Tempest | Operation `operationId` must use class::method pattern | Use `Controller::method` as deterministic ID |
| R6 | Tempest uses PHP 8.2 property hooks (`public string $name { get; set; }`) | Reflection may behave differently | Verify ClassReflector handles hooks correctly |

## Edge Cases Discovered During Source Analysis

| # | Edge Case | Filename | Workaround |
|---|-----------|----------|------------|
| EC01 | `IsDatabaseModel` uses `PrimaryKey $id` typed as object, not scalar | `IsDatabaseModel.php` | Infer extension must unwrap `PrimaryKey` → integer/string in schema |
| EC02 | `ModelInspector` auto‑detects BelongsTo for any property whose type is another model class (even without `#[BelongsTo]` attribute) | `ModelInspector.php:248-252` | `TempestModelPropertyExtension` must replicate this detection |
| EC03 | Base `Request` interface has 11 properties (`method`, `uri`, `raw`, `body`, `headers`, `path`, `query`, `files`, `cookies`, `session`, `cookies`) — all must be excluded from schema | `IsRequest.php` | `TempestRequestToSchemaExtension` must skip these |
| EC04 | `#[SkipValidation]` marks properties to skip — must not generate constraints for them | `IsRequest.php:19-50` | Check `#[SkipValidation]` in `TempestValidationExtension` |
| EC05 | `JsonExceptionRenderer` returns `x-validation` header with JSON‑encoded errors | `JsonExceptionRenderer.php:77` | `TempestValidationExceptionToResponse` must include this header |
| EC06 | `GenericRouter::createResponse()` auto‑wraps `array|string|View` in `Ok` | `GenericRouter.php:91-98` | Any controller returning `array` → 200 JSON — critical inference rule |
| EC07 | `#[Virtual]` properties on models are excluded from select and schema | `ModelInspector.php` | Already handled via `getValueFields()` |
| EC08 | `#[Hidden]` properties excluded from `getSelectFields()` | `ModelInspector.php:570-572` | Must also exclude from schema in `TempestModelToSchemaExtension` |
| EC09 | `PropertyReflector::getType()->isRelation()` — plain class type check for BelongsTo auto‑detection | `ModelInspector.php:221` | Schema inference must replicate, not use `instanceof Relation` |
| EC10 | `ConvertsToResponse` interface — some exceptions implement this for custom rendering | `Exceptions/ConvertsToResponse.php` | `TempestHttpExceptionToResponse` should check for this interface |

## Notes

- **7 source‑verified corrections** were applied to the initial docs-based analysis before writing specs.
- The core (Infer engine + Type system + OpenAPI builder) is **framework‑agnostic** and can be tested without any framework bootstrapping.
- The Tempest binding layer is the only part that requires `tempest/framework` as a dependency.
- All 6 spec files checked into project root: `SPEC-LARAVEL.md`, `SPEC-TEMPEST.md`, `ROADMAP.md`, `TODO.md`, `SPEC-TEST-MATRIX.md`, `FEATURES.md`.
