<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\ServiceChange;

use Axxess\Model\Pricing\Collections\DiscountCollection;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Pricing;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Model\ReturnTypes\Pricing\DiscountResult;
use Axxess\Plugins\Doctrine\EntityManager;
use Entity\Axxess1\AxServicechange;
use Money\Money;

class ServiceChangePricing extends Pricing
{
    protected EntityManager $em;

    protected AxServicechange $servicechange;

    public function __construct(EntityManager $em, AxServicechange $servicechange)
    {
        $this->em = $em;
        $this->servicechange = $servicechange;
    }

    public function getProductPrice(): PricingCollection
    {
        try {
            $service = new PriceResponse();
            $service->setService($this->servicechange->getServiceid());
            $service->setProduct($this->servicechange->getProductid());
            $service->setDiscount($this->getDiscounts());
            $service->setVat($this->calculateVatAmount($this->getFinalPrice()));
            $service->setAmount($this->getFinalPrice());

            return new PricingCollection($service);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getFinalPrice(): Money
    {
        return $this->calculateServicePrice();
    }

    protected function calculateServicePrice(): Money
    {
        try {
            /*
             * Service Changes are calculates as follows
             *
             * $ChangeProductPrice = NewProduct base price with all discounts added. including discounts for the service its changing to.
             * $CurrentProductPrice = Current base price with all discounts added. including discounts for the service that its currently on.
             *
             *
             * The difference between these two prices
             *
             *
             * (If Prorata Applies(on the new product), then its applied)
             *
             */

            //1. Get New Product Price Incl Discounts.

            $changeProduct = $this->getChangeServiceDiscounts();
            $currentProduct = $this->getCurrentServiceDiscounts();

            //TODO: Check isProrata

            return $changeProduct->getFinalPrice()->subtract($currentProduct->getFinalPrice());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getCurrentServiceDiscounts(): DiscountResult
    {
        $discount = new ServiceChangeDiscount($this->em, $this->servicechange);

        return $discount->applyDiscounts($discount->getCurrentDiscounts(), self::getBaseProductPrice($this->servicechange->getFromproductid()));
    }

    public function getChangeServiceDiscounts(): DiscountResult
    {
        $discount = new ServiceChangeDiscount($this->em, $this->servicechange);

        return $discount->applyDiscounts($discount->getChangeDiscounts(), self::getBaseProductPrice($this->servicechange->getProductid()));
    }

    private function getBaseServiceChangePrice()
    {
        return self::getBaseProductPrice($this->servicechange->getProductid())->subtract(self::getBaseProductPrice($this->servicechange->getFromproductid()));
    }

    /**
     * This merges the two discount collections together.
     *
     * @throws \Axxess\Model\Pricing\PricingException
     * @throws \Throwable
     */
    public function getDiscounts(): DiscountResult
    {
        $changeProduct = $this->getChangeServiceDiscounts();
        $currentProduct = $this->getCurrentServiceDiscounts();

        //$currentProduct->getDiscountCollection()->append($changeProduct->getDiscountCollection());

        $finalDiscountCollection = new DiscountCollection();

        if ($currentProduct->getDiscountCollection()->count() > 0) {
            foreach ($currentProduct->getDiscountCollection() as $collection) {
                $finalDiscountCollection->append($collection);
            }
        }

        if ($changeProduct->getDiscountCollection()->count() > 0) {
            foreach ($changeProduct->getDiscountCollection() as $collection) {
                $finalDiscountCollection->append($collection);
            }
        }

        return new DiscountResult(true, 'success', $this->getBaseServiceChangePrice(), $this->getFinalPrice(), $finalDiscountCollection);
    }
}
