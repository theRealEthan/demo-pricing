<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Renewal;

use Axxess\Model\Pricing\Collections\AdjustmentCollection;
use Axxess\Model\Pricing\Discount;
use Entity\Axxess1\AxAccountdetails;
use Entity\Axxess1\AxCategory;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;

class RenewalDiscount extends Discount
{
    //1. Get the discount type/types
    //2. return discounted price and discount name (return type?)
    public function getDiscounts(): AdjustmentCollection
    {
        //Order by Is to get Product Price Discounts before the Percentage discounts.
        //This is for use in Stacking of Discounts
        $serviceDiscounts = $this->getServiceDiscounts($this->service);
        $productDiscounts = $this->getProductDiscounts($this->service->getProductid());
        $accountDiscounts = $this->getProfileDiscounts($this->service->getAccountid());
        $categoryDiscounts = $this->getCategoryDiscounts($this->service->getProductid()->getCategoryid());

        $adjustments = array_merge($serviceDiscounts, $productDiscounts, $accountDiscounts, $categoryDiscounts);

        $var = array_unique($adjustments, SORT_REGULAR);

        return $this->areDiscountsValid($var);
    }

    protected function getServiceDiscounts(AxServices $service): array
    {
        $sql = 'SELECT 
                  adj.*
                FROM
                  ax_adjustments adj
                WHERE adj.ServiceId = :serviceId
                AND adj.IsRenewal = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['serviceId' => $service->getServiceid()];
        $types = ['serviceId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    protected function getProfileDiscounts(AxAccountdetails $account)
    {
        $sql = 'SELECT 
                  adj.*
                FROM
                  ax_adjustments adj
                WHERE adj.AccountId = :accountId
                AND adj.IsRenewal = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['accountId' => $account->getAccountid()];
        $types = ['accountId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    protected function getProductDiscounts(AxProducts $product)
    {
        $sql = 'SELECT 
                  adj.*
                FROM
                  ax_adjustments adj
                WHERE adj.ProductId = :productId
                AND adj.IsRenewal = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['productId' => $product->getProductid()];
        $types = ['productId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    protected function getCategoryDiscounts(AxCategory $category)
    {
        $sql = 'SELECT 
                  adj.*
                FROM
                  ax_adjustments adj
                WHERE adj.CategoryId = :categoryId
                AND adj.IsRenewal = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['categoryId' => $category->getCategoryid()];
        $types = ['categoryId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }
}
