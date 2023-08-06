<?php

/** @noinspection ALL */

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount\Types;

use Axxess\Model\Pricing\Discount\AdjustmentCollection;
use Axxess\Model\Pricing\Discount\Discount;
use Axxess\Model\Pricing\Discount\DiscountCollection;
use Axxess\Model\Pricing\Discount\DiscountResult;
use Axxess\Model\Pricing\Discount\ServiceChangeDiscountResponse;
use Axxess\Model\Pricing\Discount\ServiceChangeDiscountResult;
use Entity\Axxess1\AxAdjustments;

class ServiceChangeDiscount extends Discount
{
    public function getRawDiscount()
    {
    }

    private function getOldProductDiscounts(): AdjustmentCollection
    {
        //Order by Is to get Product Price Discounts before the Percentage discounts.
        //This is for use in Stacking of Discounts

        $serviceDiscounts = $this->getServiceDiscounts($this->service->getServiceid());
        $productDiscounts = $this->getProductDiscounts($this->service->getProductid()->getProductid());
        $accountDiscounts = $this->getProfileDiscounts($this->service->getAccountid()->getAccountid());
        $categoryDiscounts = $this->getCategoryDiscounts($this->service->getProductid()->getCategoryid()->getCategoryid());

        $adjustments = array_unique(array_merge_recursive($serviceDiscounts, $productDiscounts, $accountDiscounts, $categoryDiscounts));

        //Sort so that product Price Discounts get Processed First.
        //ie. Stacked Discounts
        usort($adjustments, function ($a, $b) {
            return $b['ProductPrice'] <=> $a['ProductPrice'];
        });

        $collection = new AdjustmentCollection();
        if (count($adjustments)) {
            foreach ($adjustments as $adjustment) {
                $adj = $this->em->getReference('Entity\\Axxess1\\AxAdjustments', $adjustment['AdjustmentId']);

                if ($this->isDiscountValid($adj)) {
                    $collection->append($adj);
                }
            }
        }

        return $collection;
    }

    private function getNewProductDiscounts(): AdjustmentCollection
    {
        //Order by Is to get Product Price Discounts before the Percentage discounts.
        //This is for use in Stacking of Discounts

        $serviceDiscounts = $this->getServiceDiscounts($this->service->getServiceid());
        $productDiscounts = $this->getProductDiscounts($this->product->getProductid());
        $accountDiscounts = $this->getProfileDiscounts($this->service->getAccountid()->getAccountid());
        $categoryDiscounts = $this->getCategoryDiscounts($this->product->getCategoryid()->getCategoryid());

        $adjustments = array_unique(array_merge_recursive($serviceDiscounts, $productDiscounts, $accountDiscounts, $categoryDiscounts));

        //Sort so that product Price Discounts get Processed First.
        //ie. Stacked Discounts
        usort($adjustments, function ($a, $b) {
            return $b['ProductPrice'] <=> $a['ProductPrice'];
        });

        $collection = new AdjustmentCollection();
        if (count($adjustments)) {
            foreach ($adjustments as $adjustment) {
                $adj = $this->em->getReference('Entity\\Axxess1\\AxAdjustments', $adjustment['AdjustmentId']);

                if ($this->isDiscountValid($adj)) {
                    $collection->append($adj);
                }
            }
        }

        return $collection;
    }

    public function getServiceChangeDiscountedAmount(): ServiceChangeDiscountResult
    {
        $old = $this->getOldDiscountedAmount();
        $new = $this->getNewDiscountedAmount();

        return new ServiceChangeDiscountResult(true, 'success', $old, $new);
    }

    private function getOldDiscountedAmount(): DiscountResult
    {
        $adjustments = $this->getOldProductDiscounts();
        $discountCollection = new DiscountCollection();
        $basePrice = (float) $this->service->getProductid()->getPrice();

        $price = $basePrice;
        $numAdjustments = count($adjustments);
        $i = 0;

        /**
         * @var $adjustment \Entity\Axxess1\AxAdjustments
         */
        foreach ($adjustments as $adjustment) {
            /*
             * We Round The last Discounted Amount.
             *
             * ie.
             * Base Price: 299
             * Discount1: 5%
             * Discount2: 50%
             *
             * Discount1Price = 284.05
             * Discount2Price = 142.025
             *
             * Final Discounted Price: 143 (we ceil)
             *
             * So show the Discount2Price Rounded.
             */
            if (++$i === $numAdjustments) {
                $isLast = true;
            }

            if ($this->isPriceDiscount($adjustment)) {
                $discountedAmount = (float) $adjustment->getProductprice();

                //if the base price is greater than the discounted price
                if ($discountedAmount < $price) {
                    //TODO: Return Discount Response to Collection.
                    $discountResponse = new ServiceChangeDiscountResponse();
                    $discountResponse->setIsOldProduct(true);
                    $discountResponse->setAdjustment($adjustment);
                    $discountResponse->setBaseAmount($price);
                    $discountResponse->setDiscountedAmount($discountedAmount);
                    if ($isLast) {
                        $discountResponse->setDiscountedAmount(ceil($discountedAmount));
                        $discountResponse->setIsRounded(true);
                    }

                    $discountCollection->append($discountResponse);

                    $price = (float) $discountedAmount;
                }
                //For Price Stacking.

                //TODO: Return Discount Response to Collection.
                $discountResponse = new ServiceChangeDiscountResponse();
                $discountResponse->setIsOldProduct(true);
                $discountResponse->setAdjustment($adjustment);
                //$discountResponse->set
            }

            if ($this->isPercentageDiscount($adjustment)) {
                $discountedAmount = $price - (($price * $adjustment->getRpercent()) / 100);

                if ($isLast) {
                    $discountedAmount = ceil($discountedAmount);
                }

                //if the base price is greater than the discounted price
                if ($discountedAmount < $price) {
                    //TODO: Return Discount Response to Collection.
                    $discountResponse = new ServiceChangeDiscountResponse();
                    $discountResponse->setIsOldProduct(true);
                    $discountResponse->setAdjustment($adjustment);
                    $discountResponse->setBaseAmount($price);
                    $discountResponse->setDiscountedAmount($discountedAmount);
                    if ($isLast) {
                        $discountResponse->setDiscountedAmount(ceil($discountedAmount));
                        $discountResponse->setIsRounded(true);
                    }

                    $discountCollection->append($discountResponse);

                    $price = (float) $discountedAmount;
                }

                $price = (float) $discountedAmount;
            }
        }

        if (!isset($discountedAmount)) {
            $discountedAmount = $basePrice;
        }

        return new DiscountResult(true, 'success', $basePrice, ceil($discountedAmount), $discountCollection);
    }

    private function getNewDiscountedAmount(): DiscountResult
    {
        $adjustments = $this->getNewProductDiscounts();
        $discountCollection = new DiscountCollection();
        $basePrice = (float) $this->product->getPrice();

        $price = $basePrice;

        $numAdjustments = count($adjustments);
        $i = 0;

        /**
         * @var $adjustment \Entity\Axxess1\AxAdjustments
         */
        foreach ($adjustments as $adjustment) {
            /*
             * We Round The last Discounted Amount.
             *
             * ie.
             * Base Price: 299
             * Discount1: 5%
             * Discount2: 50%
             *
             * Discount1Price = 284.05
             * Discount2Price = 142.025
             *
             * Final Discounted Price: 143 (we ceil)
             *
             * So show the Discount2Price Rounded.
             */
            if (++$i === $numAdjustments) {
                $isLast = true;
            }

            if ($this->isPriceDiscount($adjustment)) {
                $discountedAmount = (float) $adjustment->getProductprice();

                //if the base price is greater than the discounted price
                if ($discountedAmount < $price) {
                    //TODO: Return Discount Response to Collection.
                    $discountResponse = new ServiceChangeDiscountResponse();
                    $discountResponse->setIsNewProduct(true);
                    $discountResponse->setAdjustment($adjustment);
                    $discountResponse->setBaseAmount($price);
                    $discountResponse->setDiscountedAmount($discountedAmount);
                    if ($isLast) {
                        $discountResponse->setDiscountedAmount(ceil($discountedAmount));
                        $discountResponse->setIsRounded(true);
                    }

                    $discountCollection->append($discountResponse);

                    $price = (float) $discountedAmount;
                }
                //For Price Stacking.

                //TODO: Return Discount Response to Collection.
                $discountResponse = new ServiceChangeDiscountResponse();
                $discountResponse->setIsNewProduct(true);
                $discountResponse->setAdjustment($adjustment);
                //$discountResponse->set
            }

            if ($this->isPercentageDiscount($adjustment)) {
                $discountedAmount = $price - (($price * $adjustment->getRpercent()) / 100);

                if ($isLast) {
                    $discountedAmount = ceil($discountedAmount);
                }

                //if the base price is greater than the discounted price
                if ($discountedAmount < $price) {
                    //TODO: Return Discount Response to Collection.
                    $discountResponse = new ServiceChangeDiscountResponse();
                    $discountResponse->setIsNewProduct(true);
                    $discountResponse->setAdjustment($adjustment);
                    $discountResponse->setBaseAmount($price);
                    $discountResponse->setDiscountedAmount($discountedAmount);
                    if ($isLast) {
                        $discountResponse->setDiscountedAmount(ceil($discountedAmount));
                        $discountResponse->setIsRounded(true);
                    }

                    $discountCollection->append($discountResponse);

                    $price = (float) $discountedAmount;
                }

                $price = (float) $discountedAmount;
            }
        }

        if (!isset($discountedAmount)) {
            $discountedAmount = $basePrice;
        }

        return new DiscountResult(true, 'success', $basePrice, ceil($discountedAmount), $discountCollection);
    }

    public function isDiscountValid(AxAdjustments $adjustment): bool
    {
        if ($this->isServiceValid($adjustment) && $this->isProfileValid($adjustment) && $this->isProductValid($adjustment) && $this->isCategoryValid($adjustment)) {
            return true;
        }

        return false;
    }

    protected function getServiceDiscounts(int $serviceId): array
    {
        $sql = 'SELECT 
                  adj.*
                FROM
                  ax_adjustments adj
                WHERE adj.ServiceId = :serviceId
                AND adj.IsUpgrade = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['serviceId' => $serviceId];
        $types = ['serviceId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    protected function getProfileDiscounts(int $accountId)
    {
        $sql = 'SELECT 
                  adj.*
                FROM
                  ax_adjustments adj
                WHERE adj.AccountId = :accountId
                AND adj.IsUpgrade = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['accountId' => $accountId];
        $types = ['accountId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    protected function getProductDiscounts(int $productId)
    {
        $sql = 'SELECT 
                  adj.*
                FROM
                  ax_adjustments adj
                WHERE adj.ProductId = :productId
                AND adj.IsUpgrade = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['productId' => $productId];
        $types = ['productId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    protected function getCategoryDiscounts(int $categoryId)
    {
        $sql = 'SELECT 
                  adj.*
                FROM
                  ax_adjustments adj
                WHERE adj.CategoryId = :categoryId
                AND adj.IsUpgrade = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['categoryId' => $categoryId];
        $types = ['categoryId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    protected function createDiscount()
    {
    }
}
