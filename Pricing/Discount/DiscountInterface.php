<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount;

use Entity\Axxess1\AxAdjustments;

interface DiscountInterface
{
    public function getRawDiscount();

    public function isDiscountValid(AxAdjustments $adjustment): bool;

    public function getDiscountedAmount();
}
