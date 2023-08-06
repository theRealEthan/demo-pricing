<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Renewal;

use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\DiscountInterface;
use Axxess\Model\Pricing\Pricing;
use Axxess\Model\Pricing\Response\PriceResponse;

abstract class RenewalPricing extends Pricing
{
    public function getDiscountClass(): DiscountInterface
    {
        return new RenewalDiscount($this->em, $this->service);
    }

    public function getProductPrice(): PricingCollection
    {
        $pricing = $this->getPricing();

        $service = new PriceResponse();
        $service->setService($this->service);
        $service->setProduct($this->service->getProductid());
        $service->setBasePrice($pricing->getBasePrice());
        $service->setDiscount($pricing->getDiscounts());
        $service->setVat($this->calculateVatAmount($pricing->getFinalPrice()));
        $service->setAmount($pricing->getFinalPrice());

        return new PricingCollection($service);
    }
}
