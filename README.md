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
    AlexandreBulete\DddSymfonyBundle\DddSymfonyBundle::class => ['all' => true],
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

use AlexandreBulete\DddSymfonyBundle\DddKernel;

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
use AlexandreBulete\DddFoundation\Application\Command\AsCommandHandler;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;

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
use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;

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

### Autocomplete Form Field

The bundle provides an **Autocomplete Symfony Form field** powered by **Stimulus + Tom Select** (no jQuery).

It is designed for admin back-offices and DDD projects, and works with **ID-based values** (Value Objects friendly).

#### Assets setup (Importmap)

If your application uses Importmap / AssetMapper:

```bash
php bin/console importmap:require tom-select
php bin/console importmap:require tom-select/dist/css/tom-select.default.css
```

Enable the Stimulus controller in assets/controllers.json:

```json

{
    "controllers": {
        "@alexandrebulete/ddd-symfony-bundle": {
            "autocomplete": {
                "enabled": true,
                "fetch": "eager",
                "path": "@alexandrebulete/ddd-symfony-bundle/autocomplete_controller",
                "autoimport": {
                    "tom-select/dist/css/tom-select.default.css": true
                }
            }
        }
    }
}
```

#### Usage

```php
use AlexandreBulete\DddSymfonyBundle\Form\Type\AutocompleteType;

$builder->add('authorId', AutocompleteType::class, [
    'label' => 'Author',
    'remote_url' => $this->urlGenerator->generate('admin_user_autocomplete'),
    'placeholder' => 'Search by email…',
    'min_length' => 2,
]);
```
The autocomplete endpoint must return:

```json
{
  "results": [
    { "id": "uuid-1", "text": "user@example.com" }
  ]
}
```

#### Options

| Option | Description |
|--------|-------------|
| remote_url | AJAX endpoint URL |
| placeholder | Input placeholder |
| min_length | Minimum characters before search |
| limit | Maximum results returned |
| initial_text | Preselected label (edit forms) |
<!-- | multiple | Whether the field is multiple | -->

## Tracing: who acts, and what caused what

Every message on `command.bus` and `query.bus` carries two stamps, set when it
is dispatched:

- `ActorStamp` — who asks: a `user`, an `agent`, or the `system`;
- `TraceStamp` — its place in a chain of causes: its own `messageId`, the
  `correlationId` of the whole chain, the `causationId` of the message that
  sent it, and the `channel` the chain entered through (`http`, `cli`, …).

Who acts is decided once, at the entry point:

| Situation | Actor |
|---|---|
| A signed-in user dispatches (back office, API) | that user |
| A message is sent while another is handled | `system`, same chain |
| `TraceContext::runAs($actor, $channel, fn)` (agent runtime, webhook) | `$actor` |
| An explicit `ActorStamp` | that actor |
| A message consumed from a transport | what it was sent with |
| CLI, cron, nobody signed in | `system` |

Read the current trace from anywhere — an audit writer, a logger:

```php
$trace = $traceContext->current();   // ?Trace
$trace->actor->label;                 // "Pauline Martin"
$trace->stamp->correlationId;         // the whole chain
```

A security user tells which account it is by implementing
`ActorAwareInterface` (`SecurityUser` accepts an `Actor`); otherwise its
identifier is used. Symfony Security is optional: without it, the system is
the actor of every chain.

Stamps are set by a decorator of each bus, at dispatch time: it is the only
moment a message sent with `DispatchAfterCurrentBusStamp` still knows its
cause. A bus of your own gets the same behaviour by adding
`ddd.messenger.tracing_middleware`, minus that deferred case.

## Development

```bash
composer install
composer qa     # phpstan (max + strict rules), then phpunit
```

The integration test boots a kernel with Framework and Doctrine only — no
Security, no AssetMapper: the minimum the bundle must work with.

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

use AlexandreBulete\DddSymfonyBundle\DddKernel;
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
