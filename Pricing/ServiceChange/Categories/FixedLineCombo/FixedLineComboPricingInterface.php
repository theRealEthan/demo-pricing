<?php

namespace Axxess\Model\Pricing\ServiceChange\Categories\FixedLineCombo;

use Axxess\Model\Pricing\Collections\PricingCollection;
use Entity\Axxess1\AxProducts;

interface FixedLineComboPricingInterface
{
    public function getDataProductFromComboProduct(AxProducts $product): AxProducts;

    public function getCurrentDataProduct(): AxProducts;

    public function getChangeDataProduct(): AxProducts;

    public function getProductPrice(): PricingCollection;
}
