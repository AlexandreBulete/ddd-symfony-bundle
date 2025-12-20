<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Messenger;

use AlexandreBulete\DddFoundation\Application\Query\QueryBusInterface;
use AlexandreBulete\DddFoundation\Application\Query\QueryInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerQueryBus implements QueryBusInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    /**
     * @template T
     *
     * @param QueryInterface<T> $query
     *
     * @return T
     */
    public function ask(QueryInterface $query): mixed
    {
        try {
            /** @var T */
            return $this->handle($query);
        } catch (HandlerFailedException $e) {
            if ($exception = current($e->getWrappedExceptions())) {
                throw $exception;
            }

            throw $e;
        }
    }
}

