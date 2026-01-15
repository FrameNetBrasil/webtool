---
title: System Architecture
order: 1
description: Technical architecture and design patterns
---

# System Architecture

Understanding Webtool 4.2's technical architecture.

## Technology Stack

### Backend
- **Laravel 12**: PHP framework
- **PHP 8.4**: Modern PHP features
- **PostgreSQL**: Primary database
- **Redis**: Caching and queuing
- **Reverb**: WebSocket server for real-time features

### Frontend
- **Vite**: Asset bundling
- **AlpineJS**: Reactive components
- **HTMX**: Dynamic content updates
- **Fomantic-UI**: CSS framework
- **JointJS**: Graph visualizations

## Directory Structure

```
app/
├── Http/Controllers/     # Route controllers with PHP attributes
├── Services/            # Business logic layer
├── Repositories/        # Data access abstractions
├── Data/               # DTOs and form validation
├── UI/                 # User interface components and views
└── View/Components/    # Blade component classes

resources/
├── css/                # LESS stylesheets
├── js/                 # JavaScript modules
└── views/              # Legacy Blade templates

public/scripts/         # Third-party JS libraries
```

## Key Design Patterns

### Service Layer Pattern

Business logic is encapsulated in service classes:

```php
namespace App\Services;

class FrameService
{
    public static function createFrame(array $data): Frame
    {
        // Business logic here
    }
}
```

### Repository Pattern

Data access is abstracted through repositories:

```php
namespace App\Repositories;

class Frame
{
    public static function listByFilter($filter): array
    {
        // Database queries here
    }
}
```

### Data Transfer Objects

Spatie Laravel Data for type-safe DTOs:

```php
use Spatie\LaravelData\Data;

class CreateFrameData extends Data
{
    public function __construct(
        public string $name,
        public string $definition,
    ) {}
}
```

## Database Architecture

The database schema is optimized for linguistic data:

- **Core entities**: frames, lexical units, constructions
- **Annotations**: annotation sets, labels, multimodal data
- **Relations**: entity relations, semantic networks
- **Views**: Optimized queries for complex relationships

## Frontend Architecture

### Component-Based UI

Blade components in `app/UI/components/`:
- Reusable UI elements
- AlpineJS for interactivity
- HTMX for dynamic updates

### Asset Pipeline

Vite handles:
- LESS compilation
- JavaScript bundling
- Hot module replacement in development

## Extending Webtool

### Adding New Features

1. Create controller with route attributes
2. Implement service layer for business logic
3. Create repository methods for data access
4. Build Blade views with components
5. Add tests (Pest)

### Custom Commands

Create Artisan commands in `app/Console/Commands/`:

```php
php artisan make:command ProcessAnnotations
```

### Adding Routes

Use PHP attributes in controllers:

```php
#[Get(path: '/myfeature')]
public function index() { ... }
```

## Performance Optimization

- **Eager loading**: Prevent N+1 queries
- **Caching**: Redis for frequently accessed data
- **Queue jobs**: Background processing for heavy tasks
- **Database indexing**: Optimized for linguistic queries

## See Also

- [Database Schema](database-schema.md)
- [Custom Components](custom-components.md)
- [Testing Guide](testing-guide.md)
