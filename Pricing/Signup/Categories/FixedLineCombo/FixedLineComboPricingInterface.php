<?php

namespace Axxess\Model\Pricing\Signup\Categories\FixedLineCombo;

use Axxess\Model\Pricing\Collections\PricingCollection;
use Entity\Axxess1\AxServices;
use Money\Money;

interface FixedLineComboPricingInterface
{
    public function getParentPrice(): Money;

    public function getLineService(): AxServices;

    public function getDataService(): AxServices;

    public function getBaseLinePrice(): Money;

    public function getFinalLinePrice(): Money;

    public function getBaseDataPrice(): Money;

    public function getFinalDataPrice(): Money;

    public function getBundledServices(): array;

    public function canChargeData(): bool;

    public function canChargeLine(): bool;

    public function isLineActive(): bool;

    public function isLineTransfer(): bool;

    public function getProductPrice(): PricingCollection;
}
