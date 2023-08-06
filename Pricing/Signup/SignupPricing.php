<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Signup;

use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Discount;
use Axxess\Model\Pricing\Pricing;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Model\ReturnTypes\Pricing\PricingResult;
use Axxess\Model\ReturnTypes\Pricing\ProrataResult;
use Money\Money;

abstract class SignupPricing extends Pricing
{
    public function getDiscountClass(): Discount
    {
        return new SignupDiscount($this->em, $this->service);
    }

    public function getProductPrice(): PricingCollection
    {
        $pricing = $this->getPricing();

        $service = new PriceResponse();
        $service->setService($this->service);
        $service->setProduct($this->service->getProductid());
        $service->setDiscount($pricing->getDiscounts());
        $service->setVat($this->calculateVatAmount($pricing->getFinalPrice()));
        $service->setAmount($pricing->getFinalPrice());
        $service->setProrata($pricing->getProrataResult());

        return new PricingCollection($service);
    }

    public function getPricing(): PricingResult
    {
        //getBasePrice
        $basePrice = self::getBaseProductPrice();
        $discount = $this->getDiscountClass();
        $discounts = $discount->applyDiscounts($discount->getDiscounts(), $basePrice);
        $prorata = $this->getProrata($discounts->getFinalPrice());

        $result = new PricingResult(true, 'success');
        $result->setBasePrice($basePrice);
        $result->setDiscounts($discounts);
        $result->setFinalPrice($prorata->getProrataPrice());
        $result->setProrataResult($prorata);

        return $result;
    }

    public function getFinalPrice(): Money
    {
        if ($this->product->getIsprorata()) {
            return $this->getProrata($this->getPriceAfterDiscount())->getProrataPrice();
        }

        return $this->getPriceAfterDiscount();
    }

    public function getProrata(Money $basePrice): ProrataResult
    {
        if ($this->service->getProductid()->getIsprorata()) {
            $prorata = $this->getProrataAmount($this->service->getProductid(), $basePrice, $this->date);

            $result = new ProrataResult(true);
            $result->setBasePrice($basePrice);
            $result->setProrataPrice($prorata);
            $result->setDate($this->date);

            return $result;
        } else {
            $result = new ProrataResult(false);
            $result->setBasePrice($basePrice);
            $result->setProrataPrice($basePrice);
            $result->setDate($this->date);

            return $result;
        }
    }

    protected function getPriceAfterDiscount()
    {
        return $this->getDiscounts()->getFinalPrice();
    }
}
