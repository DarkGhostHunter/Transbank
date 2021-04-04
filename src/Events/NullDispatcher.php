<?php

namespace DarkGhostHunter\Transbank\Events;

use Psr\EventDispatcher\EventDispatcherInterface;

class NullDispatcher implements EventDispatcherInterface
{
    /**
     * @inheritDoc
     */
    public function dispatch(object $event): void
    {
        // Do absolutely nothing.
    }
}
