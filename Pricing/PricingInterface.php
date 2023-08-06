<?php

namespace Axxess\Model\Pricing;

use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\ReturnTypes\Pricing\PricingResult;
use Money\Money;

interface PricingInterface
{
    public function getBaseProductPrice(): Money;

    public function getDiscountClass(): DiscountInterface;

    public function getPricing(): PricingResult;

    public function getProductPrice(): PricingCollection;

    public function canAddToInvoice(): bool;

    public function canAddToInvoiceReason(): string;
}
