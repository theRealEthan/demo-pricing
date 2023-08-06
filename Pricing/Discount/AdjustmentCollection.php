<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount;

use Axxess\Model\Pricing\PricingException;
use Entity\Axxess1\AxAdjustments;

final class AdjustmentCollection extends \ArrayObject
{
    public function __construct(AxAdjustments ...$priceResponse)
    {
        parent::__construct($priceResponse);
    }

    public function append($value)
    {
        if ($value instanceof AxAdjustments) {
            parent::append($value);
        } else {
            throw new PricingException('Cannot append non AxAdjustment Object to a '.__CLASS__);
        }
    }

    public function offsetSet($index, $newval)
    {
        if ($newval instanceof AxAdjustments) {
            parent::offsetSet($index, $newval);
        } else {
            throw new PricingException('Cannot add a non AxAdjustment Object value to a '.__CLASS__);
        }
    }
}
