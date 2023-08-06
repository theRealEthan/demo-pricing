<?php

namespace Axxess\Model\Pricing\Renewal\Categories\FixedLineCombo;

use Axxess\Model\Pricing\Collections\PricingCollection;
use Entity\Axxess1\AxServices;
use Money\Money;

interface FixedLineComboPricingInterface
{
    public function getProductPrice(): PricingCollection;

    public function getParentPrice(): Money;

    public function getLineBaseProductPrice(): Money;

    public function getBundleServices(): array;

    public function getLineService(): AxServices;

    public function getDataService(): AxServices;

    public function getLinePrice(Money $comboPrice): Money;

    public function getDataPrice(): Money;

    public function isLineActive(): bool;
}
