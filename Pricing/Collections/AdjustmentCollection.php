<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Collections;

use Entity\Axxess1\AxAdjustments;

class AdjustmentCollection implements \Iterator
{
    private array $objects = [];
    private int $position = 0;

    public function add(AxAdjustments $object): void
    {
        $this->objects[] = $object;
    }

    // Implementing Iterator methods
    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): AxAdjustments
    {
        return $this->objects[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function valid(): bool
    {
        return isset($this->objects[$this->position]);
    }
}
