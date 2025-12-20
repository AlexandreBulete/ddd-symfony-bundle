# DDD Symfony Bundle

Symfony Bundle for DDD Foundation. Provides Messenger integration for Command/Query buses and a base Kernel for DDD projects.

## Installation

```bash
composer require alexandrebulete/ddd-symfony-bundle
```

## Configuration

Add the bundle to your `config/bundles.php`:

```php
return [
    // ...
    Alexandrebulete\DddSymfonyBundle\DddSymfonyBundle::class => ['all' => true],
];
```

## Features

### DddKernel - Auto-import Bounded Contexts

The bundle provides a `DddKernel` that automatically imports services, packages, and routes from your Bounded Contexts.

#### Setup

Extend `DddKernel` in your application's `src/Kernel.php`:

```php
<?php

declare(strict_types=1);

namespace App;

use Alexandrebulete\DddSymfonyBundle\DddKernel;

class Kernel extends DddKernel
{
    // You can override configureContainer/configureRoutes if needed
}
```

#### What it does

The `DddKernel` automatically imports:

- **Services**: `src/*/Infrastructure/Symfony/config/services.{php,yaml}`
- **Packages**: `src/*/Infrastructure/Symfony/config/packages/*.{php,yaml}`
- **Routes**: `src/*/Infrastructure/Symfony/routes/*.{php,yaml}`

This means each Bounded Context can define its own configuration without modifying the main `config/` folder.

#### Expected BC Structure

```
src/
├── Post/                              # Bounded Context
│   └── Infrastructure/
│       └── Symfony/
│           ├── config/
│           │   ├── services.php       # Auto-imported
│           │   └── packages/
│           │       └── doctrine.yaml  # Auto-imported
│           └── routes/
│               └── api.yaml           # Auto-imported
└── User/                              # Another Bounded Context
    └── Infrastructure/
        └── Symfony/
            └── config/
                └── services.php       # Auto-imported
```

### Command/Query Bus

The bundle automatically configures two Symfony Messenger buses:

- `command.bus` - For write operations (Commands)
- `query.bus` - For read operations (Queries)

### Automatic Handler Registration

Handlers decorated with `#[AsCommandHandler]` or `#[AsQueryHandler]` are automatically registered to their respective buses.

```php
use Alexandrebulete\DddFoundation\Application\Command\AsCommandHandler;
use Alexandrebulete\DddFoundation\Application\Command\CommandInterface;

readonly class CreatePostCommand implements CommandInterface
{
    public function __construct(
        public string $title,
        public string $content,
    ) {}
}

#[AsCommandHandler]
readonly class CreatePostHandler
{
    public function __invoke(CreatePostCommand $command): void
    {
        // Handle the command
    }
}
```

### Using the Buses

```php
use Alexandrebulete\DddFoundation\Application\Command\CommandBusInterface;
use Alexandrebulete\DddFoundation\Application\Query\QueryBusInterface;

class PostController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function create(): Response
    {
        $this->commandBus->dispatch(new CreatePostCommand(
            title: 'My Post',
            content: 'Content...',
        ));

        // ...
    }

    public function list(): Response
    {
        $posts = $this->queryBus->ask(new GetPostsQuery(
            page: 1,
            itemsPerPage: 10,
        ));

        // ...
    }
}
```

## Customization

### Override Messenger Configuration

You can override the messenger configuration by creating your own `config/packages/messenger.yaml`:

```yaml
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - doctrine_transaction
            query.bus: ~
```

### Extend DddKernel

If you need custom container or routes configuration, override the methods:

```php
<?php

declare(strict_types=1);

namespace App;

use Alexandrebulete\DddSymfonyBundle\DddKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class Kernel extends DddKernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);

        // Add your custom imports here
        $container->import($this->getProjectDir().'/config/custom/*.yaml');
    }
}
```
