<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Collections;

use Axxess\Model\Pricing\Response\PriceResponse;

final class PricingCollection extends \ArrayObject
{
    public function __construct(PriceResponse ...$priceResponse)
    {
        parent::__construct($priceResponse);
    }

    public function append($value)
    {
        if ($value instanceof PriceResponse) {
            parent::append($value);
        } else {
            throw new PricingException('Cannot append non Price Object to a '.__CLASS__);
        }
    }

    public function offsetSet($index, $newval)
    {
        if ($newval instanceof PriceResponse) {
            parent::offsetSet($index, $newval);
        } else {
            throw new PricingException('Cannot add a non Price Object value to a '.__CLASS__);
        }
    }
}
