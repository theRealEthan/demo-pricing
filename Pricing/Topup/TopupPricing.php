<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Topup;

use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Discount\DiscountResult;
use Axxess\Model\Pricing\Discount\Types\TopupDiscount;
use Axxess\Model\Pricing\Pricing;
use Axxess\Model\Pricing\PricingInterface;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Models\Discount\DiscountModel;
use Axxess\Plugins\Doctrine\EntityManager;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;

abstract class TopupPricing extends Pricing implements PricingInterface
{
    protected EntityManager $em;

    protected AxServices $service;

    protected DiscountModel $discountModel;

    protected AxProducts $topupProduct;

    public function __construct(EntityManager $em, AxServices $service, AxProducts $topupProduct, DiscountModel $discountModel)
    {
        $this->em = $em;
        $this->service = $service;
        $this->discountModel = $discountModel;
        $this->topupProduct = $topupProduct;
    }

    public function getProductPrice(): PricingCollection
    {
        try {
            $service = new PriceResponse();
            $service->setService($this->service);
            $service->setProduct($this->topupProduct);
            $service->setDiscount($this->getDiscount($this->getBaseProductPrice($this->topupProduct)));
            $service->setVat($this->calculateVatAmount($this->calculateServicePrice($this->service, $this->topupProduct)));
            $service->setAmount($this->calculateServicePrice($this->service, $this->topupProduct));

            return new PricingCollection($service);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function calculateServicePrice(AxServices $service, AxProducts $product, $isBundle = 0): float
    {
        try {
            $price = $this->getBaseProductPrice($product);

            return $this->getDiscountedPrice($price);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getDiscount(float $price): DiscountResult
    {
        $discount = new TopupDiscount($this->em, $this->service, $this->topupProduct, $price);

        return $discount->getDiscountedAmount();
    }

    public function getDiscountedPrice(float $price): float
    {
        $discount = $this->getDiscount($price);

        return $discount->getFinalPrice();
    }
}
