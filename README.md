# Lens OpenAPI

OpenAPI 3.1 specification generator for Tempest PHP framework. Uses static analysis — no runtime execution required.

## Features

- **Zero Runtime** — Static analysis of controller attributes and types
- **Tempest Native** — Supports `#[Get]`, `#[Post]`, `#[Put]`, `#[Delete]`, `#[Route]` attributes
- **Request DTOs** — Automatically expands Request classes into requestBody schemas
- **Exception Mapping** — `#[Throws]` attributes become OpenAPI responses (404, 422, etc.)
- **Security** — `#[Auth]`, `#[AllowGuest]`, `#[Can]` attributes mapped to security requirements
- **Scalar Viewer** — Built-in API documentation UI served at `/docs`
- **Extension System** — Customize type-to-schema, operation transformers, validation rules
- **Environment Config** — Configure via `lens.config.php` or `LENS_*` environment variables

## Installation

```bash
composer require lens/openapi
```

### Install via Tempest CLI

Lens registers an installer that automatically publishes the configuration file:

```bash
php bin/tempest install lens
```

This will:
1. Create `app/lens.config.php` with minimal stub
2. Register Scalar viewer routes (`/docs`, `/openapi.json`)
3. Configure environment variable support

### Manual Installation

Copy the configuration file manually:

```bash
php bin/lens config:publish
```

### No Config? No Problem

If no config file exists, Lens automatically uses `LensConfig::fromEnv()` which loads from environment variables with sensible defaults:

```env
LENS_TITLE="My API"
LENS_VERSION=2.0.0
LENS_BASE_PATH=/api/v1
LENS_SCALAR_ROUTE=/docs
```

## Quick Start

### 1. Generate OpenAPI Spec

```bash
php bin/lens generate
```

Output: `openapi.json`

### 2. View Documentation

Start your Tempest application:

```bash
php bin/tempest serve
```

Visit: `http://localhost:8080/docs`

Scalar viewer serves your OpenAPI spec with interactive API explorer.

## Configuration

### Config File (`app/lens.config.php`)

After running `php bin/tempest install lens`, a minimal stub is created:

```php
return new LensConfig(
    title: env('LENS_TITLE', env('APP_NAME') . ' API'),
    version: env('LENS_VERSION', '1.0.0'),
    basePath: env('LENS_BASE_PATH', '/'),
    
    scalar: new ScalarConfig(
        enabled: env('LENS_SCALAR_ENABLED', true),
        route: env('LENS_SCALAR_ROUTE', '/docs'),
        title: env('LENS_SCALAR_TITLE', 'API Documentation'),
    ),
);
```

Customize by editing the file or using environment variables.

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `LENS_SOURCES` | Comma-separated source directories | `src` |
| `LENS_TITLE` | API title | `Tempest API` |
| `LENS_VERSION` | API version | `1.0.0` |
| `LENS_BASE_PATH` | Base path for API | `/` |
| `LENS_EXCLUDE` | Namespaces to exclude | — |
| `LENS_INCLUDE_INTERNAL` | Include `__*` methods | `false` |
| `LENS_SCALAR_ENABLED` | Enable Scalar viewer | `true` |
| `LENS_SCALAR_ROUTE` | Scalar viewer route | `/docs` |
| `LENS_SCALAR_SPEC_ROUTE` | OpenAPI JSON route | `/openapi.json` |
| `LENS_SCALAR_TITLE` | Scalar page title | `API Documentation` |
| `LENS_SCALAR_SPEC_URL` | External spec URL | — |
| `LENS_SECURITY_SCHEMES` | JSON string of security schemes | — |

Example `.env`:

```env
LENS_TITLE="My App API"
LENS_VERSION=2.0.0
LENS_BASE_PATH=/api/v1
LENS_SCALAR_ROUTE=/api-docs
LENS_EXCLUDE="App\\Internal,App\\Dev"
```

## Usage

### Controller Example

```php
<?php

namespace App\Http\Controllers;

use Tempest\Http\Get;
use Tempest\Http\Post;
use Tempest\Http\Throws;
use Tempest\Http\Auth;
use Tempest\Http\Exceptions\NotFoundException;

class UserController
{
    /**
     * List all users
     * 
     * @tag Users
     * @tag Admin
     */
    #[Get("/users")]
    #[Auth]
    public function index(): array
    {
        return [];
    }

    /**
     * Get a specific user
     * 
     * @param int $id The user ID
     * @return array User data
     */
    #[Get("/users/{id}")]
    #[Throws(NotFoundException::class)]
    public function show(int $id): array
    {
        return [];
    }

    /**
     * Create a new user
     * 
     * @example {"name": "John", "email": "john@example.com"}
     */
    #[Post("/users")]
    public function store(CreateUserRequest $request): User
    {
        return new User();
    }
}
```

### Request DTO

```php
<?php

namespace App\Http\Requests;

class CreateUserRequest
{
    public string $name;
    public string $email;
    public ?string $phone = null;
}
```

Generates:

```json
{
  "requestBody": {
    "content": {
      "application/json": {
        "schema": {
          "type": "object",
          "properties": {
            "name": {"type": "string"},
            "email": {"type": "string"},
            "phone": {"type": "string", "nullable": true}
          },
          "required": ["name", "email"]
        }
      }
    }
  }
}
```

### Response Models

```php
<?php

namespace App\Models;

class User
{
    public int $id;
    public string $name;
    public string $email;
}
```

Generates schema in `components/schemas` and `$ref` in responses.

## CLI Commands

### Generate Spec

```bash
# Basic
php bin/lens generate

# Custom output
php bin/lens generate -o api-spec.json

# YAML format
php bin/lens generate -f yaml

# Multiple sources
php bin/lens generate -s src -s modules

# Exclude namespaces
php bin/lens generate -e 'App\Internal' -e 'App\Dev'

# Custom base path
php bin/lens generate -b /api/v1
```

### Publish Config

```bash
php bin/lens config:publish
```

## Routes (Auto-Registered)

When Lens is installed, these routes are automatically registered:

| Route | Description |
|-------|-------------|
| `/docs` | Scalar API documentation viewer |
| `/openapi.json` | OpenAPI specification JSON |

Configure in `lens.config.php`:

```php
scalar: new ScalarConfig(
    route: '/api-docs',
    specRoute: '/api-spec.json',
)
```

## Extension System

### Type-to-Schema

```php
use Lens\Extensions\TypeToSchema\TypeToSchemaExtension;

class CarbonDateToSchema implements TypeToSchemaExtension
{
    public function supports(Type $type): bool
    {
        return $type->className === 'Carbon\\Carbon';
    }

    public function convert(Type $type): array
    {
        return [
            'type' => 'string',
            'format' => 'date-time',
        ];
    }
}
```

### Operation Transformer

```php
use Lens\Extensions\Operation\OperationTransformer;

class CustomOperationTransformer implements OperationTransformer
{
    public function transform(
        Engine $engine,
        string $controllerClass,
        string $method,
        Type $returnType,
        array $params,
        \Closure $schemaConverter,
        array $meta = []
    ): ?array {
        // Custom operation building logic
        return null; // Return null to let next transformer handle it
    }
}
```

### Validation Rule to Constraint

```php
use Lens\Extensions\Validation\ValidationRuleToConstraint;

class UppercaseToConstraint implements ValidationRuleToConstraint
{
    public function supports(string $ruleClass): bool
    {
        return $ruleClass === 'Tempest\\Validation\\Rules\\Uppercase';
    }

    public function convert(string $ruleClass): array
    {
        return ['pattern' => '^[A-Z]+$'];
    }
}
```

## Testing

```bash
# Run all tests
composer test

# Run without coverage
composer test:local

# Type coverage
composer type-coverage
```

## License

MIT
