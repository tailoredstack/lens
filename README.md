# Lens OpenAPI

**OpenAPI 3.1 spec generator for PHP — static analysis, no runtime execution.**

Generates OpenAPI 3.1 specifications from PHP type declarations, attributes, and docblocks. Built for Tempest PHP framework compatibility.

## Status

**Phase 0.5 complete.** Core implementation functional:
- ✅ Type system (19 type classes)
- ✅ Infer engine (AST parsing, type inference)
- ✅ OpenAPI builder (type→schema mapping)
- ✅ CLI command (`bin/openapi`)
- ✅ 100% test coverage (18 tests)

## Installation

```bash
composer require lens/openapi
```

## Usage

### CLI

```bash
# Generate to stdout
bin/openapi

# Generate JSON file
bin/openapi -o openapi.json

# Generate YAML with custom title
bin/openapi -f yaml -o openapi.yaml --title "My API" --version "1.0.0"

# Exclude namespaces
bin/openapi --exclude "App\\Internal" --exclude "App\\Tests"

# Include __magic methods
bin/openapi --include-internal
```

### Composer Scripts

```bash
composer openapi          # Generate openapi.json
composer openapi -- -f yaml -o spec.yaml
```

### Programmatic

```php
use Lens\OpenApi;
use Lens\Config\OpenApiConfig;

$config = new OpenApiConfig(
    sources: ['/path/to/src'],
    title: 'My API',
    version: '1.0.0',
    basePath: '/api/v1',
    exclude: ['App\\Internal'],
);

$spec = OpenApi::generate($config);
```

### Configuration

Publish the config file:

```bash
php tempest lens:install
```

This creates `lens.config.php` in your project root. Customize options like title, version, basePath, and exclusions.

## Features

- **Static analysis** — no runtime execution required
- **Type inference** — parses PHP type declarations, nullable, union, intersection types
- **Attribute support** — detects `#[Get]`, `#[Post]`, `#[Route]` attributes
- **Docblock parsing** — reads `@var`, `@route` annotations
- **OpenAPI 3.1** — generates compliant specifications
- **JSON/YAML output** — choose your format
- **100% type coverage** — enforced via Pest type-coverage plugin

## QA Commands

```bash
composer fmt           # Format code
composer lint          # Run linter
composer analyze       # Static analysis
composer type-coverage # Type coverage (100% required)
composer test:local    # Run tests
composer qa            # Run all checks
```

## Documents

| File | Purpose |
|------|---------|
| [`SPEC-LARAVEL.md`](SPEC-LARAVEL.md) | Clean‑room reconstruction of Scramble's architecture |
| [`SPEC-TEMPEST.md`](SPEC-TEMPEST.md) | Tempest binding spec |
| [`ROADMAP.md`](ROADMAP.md) | Development plan (8 phases) |
| [`TODO.md`](TODO.md) | Live status tracker |
| [`FEATURES.md`](FEATURES.md) | Feature matrix (106 features) |
| [`SPEC-TEST-MATRIX.md`](SPEC-TEST-MATRIX.md) | Test coverage mapping |

## Architecture

```
Source Code → Infer Engine → Type System → OpenAPI Builder → Spec
```

## License

MIT
