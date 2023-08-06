<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing;

use Axxess\Model\Pricing\Collections\AdjustmentCollection;
use Axxess\Model\ReturnTypes\Pricing\DiscountResult;
use Entity\Axxess1\AxAdjustments;

interface DiscountInterface
{
    //TODO: Figure our Service
    //public function getDiscounts():AdjustmentCollection;

    public function applyDiscounts(AdjustmentCollection $adjustments, \Money\Money $basePrice): DiscountResult;

    public function isDiscountValid(AxAdjustments $adjustment): bool;
}
