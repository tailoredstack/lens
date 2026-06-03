# Tempest OpenAPI

**Clean‑room OpenAPI 3.1 spec generator for [Tempest PHP](https://github.com/tempestphp/tempest-framework).**

Adapts the architecture of `dedoc/scramble` (a Laravel OpenAPI generator) to the Tempest framework — replacing only the framework binding layer while preserving the framework‑agnostic core (Infer engine, PHP type system, OpenAPI builder).

## Status

**Pre‑development.** All specification documents are complete. Implementation has not started.

## Documents

| File | Purpose |
|------|---------|
| [`SPEC-LARAVEL.md`](SPEC-LARAVEL.md) | Clean‑room reconstruction of Scramble's architecture (5 subsystems, framework‑agnostic) |
| [`SPEC-TEMPEST.md`](SPEC-TEMPEST.md) | Tempest binding spec — every extension contract mapped to Tempest equivalents |
| [`ROADMAP.md`](ROADMAP.md) | Development plan (8 phases, 8 milestones, ~8 weeks) |
| [`TODO.md`](TODO.md) | Live status tracker — what's done, what's next, known risks |
| [`FEATURES.md`](FEATURES.md) | Quick‑reference matrix — 106 features across 10 categories (90 supported) |
| [`SPEC-TEST-MATRIX.md`](SPEC-TEST-MATRIX.md) | 1:1 Laravel-to-Tempest feature mapping with edge cases |

## Quick Stats

- **106 features** mapped across 10 categories
- **90 fully compatible** (85%), **5 partial**, **9 unsupported**
- **2,274 lines** of architectural specification
- **33 Tempest packages** analyzed via source inspection
- **~135 Scramble files** analyzed for clean‑room reconstruction

## Architecture

The core pipeline never executes application code — all analysis is static (AST + reflection):

```
Source Code → Route Discovery → Operation Transformation → Type Inference → Type→Schema Mapping → Document Assembly → OpenAPI 3.1 Spec
```

## Package Development

This package follows Tempest's official package development conventions:

| Aspect | Approach |
|--------|----------|
| Registration | `composer.json` with `extra.tempest.can-discover: true` (no service providers) |
| Discovery | `Discovery` + `#[ConsoleCommand]` + config files auto-discovered via Composer metadata |
| Installer | `#[Installer]` attribute + `PublishesFiles` trait for publishing config to user's project |
| Bootstrapping | `#[EventHandler(KernelEvent::BOOTED)]` for any runtime setup |
| Testing | Extends `Tempest\Framework\Testing\IntegrationTest` |
| Config | Plain PHP file returning a config object (`openapi.config.php`) |

For details, see [`SPEC-TEMPEST.md` §7.3](SPEC-TEMPEST.md#73-package-registration).

## License

MIT
