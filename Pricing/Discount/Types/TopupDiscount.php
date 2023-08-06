<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount\Types;

use Axxess\Model\Pricing\Discount\AdjustmentCollection;
use Axxess\Model\Pricing\Discount\Discount;
use Entity\Axxess1\AxAdjustments;

class TopupDiscount extends Discount
{
    public function getRawDiscount(): AdjustmentCollection
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
                AND adj.IsTopup = 1
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
                AND adj.IsTopup = 1
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
                AND adj.IsTopup = 1
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
                AND adj.IsTopup = 1
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
