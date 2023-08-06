<?php

namespace Axxess\Model\Pricing;

use Axxess\Model\Pricing\Collections\PricingCollection;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;

interface PricingFactoryInterface
{
    public function getPrice(AxServices $services, AxProducts $products): PricingCollection;

    public function getCategory(AxServices $services, \DateTime $date);
}
