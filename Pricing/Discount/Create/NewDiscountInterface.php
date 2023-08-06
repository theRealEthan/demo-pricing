<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount\Create;

interface NewDiscountInterface
{
    public function getDiscountType(): int;
}
