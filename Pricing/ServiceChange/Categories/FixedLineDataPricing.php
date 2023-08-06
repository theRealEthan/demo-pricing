<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\ServiceChange\Categories;

use Axxess\Model\Pricing\ServiceChange\PricingInterface;
use Axxess\Model\Pricing\ServiceChange\ServiceChangePricing;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;

class FixedLineDataPricing extends ServiceChangePricing implements PricingInterface
{
    /**
     * If Product Type (Capped/Uncapped) is different to the change product. ie. Capped to Uncapped, Bill the new Service Prorata(if applicable).
     * eg: \Axxess\Classes\Pricing Line 265.
     *
     * @throws \Exception
     */
    protected function calculateServicePrice(AxServices $service, AxProducts $serviceChangeProduct, int $isBundle = 0): float
    {
        try {
            if ($service->getProductid()->getUnit() === $serviceChangeProduct->getUnit()) {
                return parent::calculateServicePrice($service, $serviceChangeProduct);
            } else {
                $newProductPrice = $this->discountModel->getProductPrice(
                    $service->getAccountid()->getAccountid(),
                    $serviceChangeProduct->getProductid(),
                    $serviceChangeProduct->getPrice(),
                    $serviceChangeProduct->getCategoryid()->getCategoryid(),
                    null,
                    $isTopup = 0,
                    $isUpgrade = 1,
                    $isNewSignup = 0,
                    $isRenewal = 0,
                    $quantity = 1,
                    $isBundle,
                    $service->getServiceid()
                );

                $price = $this->applyServiceDiscount($newProductPrice);

                if (true === $serviceChangeProduct->getIsprorata()) {
                    $price = self::getProrataPrice($price, $serviceChangeProduct->getProratasteps(), null);
                }
            }

            if ($price > 0) {
                $price = 0;
            }

            return $price;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
