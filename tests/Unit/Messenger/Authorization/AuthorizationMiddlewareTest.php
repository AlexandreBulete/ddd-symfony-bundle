<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Tests\Unit\Messenger\Authorization;

use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\AuthorizationMiddleware;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionCheckerInterface;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionDenied;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\Actor;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\ActorStamp;
use AlexandreBulete\DddSymfonyBundle\Messenger\Tracing\TraceStamp;
use AlexandreBulete\DddSymfonyBundle\Tests\Unit\Fixture\Billing\Application\Command\IssueInvoiceCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AuthorizationMiddlewareTest extends TestCase
{
    /** @var list<string> */
    private array $granted = [];

    private AuthorizationMiddleware $middleware;

    protected function setUp(): void
    {
        $checker = new class ($this) implements PermissionCheckerInterface {
            public function __construct(private AuthorizationMiddlewareTest $test) {}

            public function isGranted(Actor $actor, string $permission): bool
            {
                return in_array($permission, $this->test->granted(), true);
            }
        };

        $this->middleware = new AuthorizationMiddleware(
            new PermissionRegistry([IssueInvoiceCommand::class => 'billing.issue_invoice']),
            $checker,
        );
    }

    /**
     * @return list<string>
     */
    public function granted(): array
    {
        return $this->granted;
    }

    #[Test]
    public function an_actor_without_the_permission_is_refused(): void
    {
        try {
            $this->handle(new IssueInvoiceCommand(), Actor::user('u-1', 'Pauline'));
            self::fail('refused expected');
        } catch (PermissionDenied $denied) {
            self::assertSame('billing.issue_invoice', $denied->permission);
            self::assertInstanceOf(AccessDeniedException::class, $denied, 'a 403 over HTTP');
        }
    }

    #[Test]
    public function an_actor_with_the_permission_goes_through(): void
    {
        $this->granted = ['billing.issue_invoice'];

        $this->handle(new IssueInvoiceCommand(), Actor::agent('a-1', 'Comptable'));

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function the_system_is_not_checked(): void
    {
        $this->handle(new IssueInvoiceCommand(), Actor::system());

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function what_is_not_a_use_case_is_not_checked(): void
    {
        $this->handle(new \stdClass(), Actor::user('u-1', 'Pauline'));

        $this->addToAssertionCount(1);
    }

    private function handle(object $message, Actor $actor): void
    {
        $envelope = new Envelope($message, [new ActorStamp($actor), new TraceStamp('m', 'm', null, 'test')]);
        $this->middleware->handle($envelope, new StackMiddleware());
    }
}
