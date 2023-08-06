<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount;

use Axxess\Model\Pricing\PricingException;

final class DiscountCollection extends \ArrayObject
{
    public function __construct(DiscountResponse ...$discountResponse)
    {
        parent::__construct($discountResponse);
    }

    public function append($value)
    {
        if ($value instanceof DiscountResponse) {
            parent::append($value);
        } else {
            throw new PricingException('Cannot append non DiscountResponse Object to a '.__CLASS__);
        }
    }

    public function offsetSet($index, $newval)
    {
        if ($newval instanceof DiscountResponse) {
            parent::offsetSet($index, $newval);
        } else {
            throw new PricingException('Cannot add a non DiscountResponse Object value to a '.__CLASS__);
        }
    }
}
