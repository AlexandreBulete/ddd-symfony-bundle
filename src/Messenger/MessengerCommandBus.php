<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddFoundation\Application\Command\CommandInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerCommandBus implements CommandBusInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    /**
     * @template T
     *
     * @param CommandInterface<T> $command
     *
     * @return T
     */
    public function dispatch(CommandInterface $command): mixed
    {
        try {
            /** @var T */
            return $this->handle($command);
        } catch (HandlerFailedException $e) {
            if ($exception = current($e->getWrappedExceptions())) {
                throw $exception;
            }

            throw $e;
        }
    }
}

