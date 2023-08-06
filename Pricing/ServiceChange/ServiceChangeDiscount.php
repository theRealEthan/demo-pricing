<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\ServiceChange;

use Axxess\Model\Pricing\Collections\AdjustmentCollection;
use Axxess\Model\Pricing\Discount;
use Axxess\Plugins\Doctrine\EntityManager;
use Entity\Axxess1\AxAccountdetails;
use Entity\Axxess1\AxAdjustments;
use Entity\Axxess1\AxCategory;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServicechange;
use Entity\Axxess1\AxServices;

class ServiceChangeDiscount extends Discount
{
    //1. Get the discount type/types
    //2. return discounted price and discount name (return type?)

    protected EntityManager $em;
    protected AxServicechange $serviceChange;
    protected AxServices $service;

    public function __construct(EntityManager $em, AxServicechange $serviceChange)
    {
        $this->em = $em;
        $this->serviceChange = $serviceChange;
        $this->service = $serviceChange->getServiceid();
    }

    public function getCurrentDiscounts(): AdjustmentCollection
    {
        //Order by Is to get Product Price Discounts before the Percentage discounts.
        //This is for use in Stacking of Discounts
        $serviceDiscounts = $this->getServiceDiscounts($this->serviceChange->getServiceid());
        $productDiscounts = $this->getProductDiscounts($this->serviceChange->getFromproductid());
        $accountDiscounts = $this->getProfileDiscounts($this->serviceChange->getServiceid()->getAccountid());
        $categoryDiscounts = $this->getCategoryDiscounts($this->serviceChange->getProductid()->getCategoryid());

        $adjustments = array_merge_recursive($serviceDiscounts, $productDiscounts, $accountDiscounts, $categoryDiscounts);

        return $this->areDiscountsValid($adjustments);
    }

    public function getChangeDiscounts(): AdjustmentCollection
    {
        //Order by Is to get Product Price Discounts before the Percentage discounts.
        //This is for use in Stacking of Discounts
        $serviceDiscounts = $this->getServiceDiscounts($this->serviceChange->getServiceid());
        $productDiscounts = $this->getProductDiscounts($this->serviceChange->getProductid());
        $accountDiscounts = $this->getProfileDiscounts($this->serviceChange->getServiceid()->getAccountid());
        $categoryDiscounts = $this->getCategoryDiscounts($this->serviceChange->getProductid()->getCategoryid());

        $adjustments = array_merge_recursive($serviceDiscounts, $productDiscounts, $accountDiscounts, $categoryDiscounts);

        return $this->areDiscountsValid($adjustments);
    }

    public function isDiscountValid(AxAdjustments $adjustment): bool
    {
        if ($this->isServiceValid($adjustment) && $this->isProfileValid($adjustment) && $this->isProductValid($adjustment) && $this->isCategoryValid($adjustment) && $this->isChangeProductValid($adjustment) && $this->isChangeCategoryValid($adjustment)) {
            return true;
        }

        return false;
    }

    private function isChangeProductValid(AxAdjustments $adjustment): bool
    {
        if (!is_null($adjustment->getProductid())) {
            if ($this->serviceChange->getProductid()->getProductid() === $adjustment->getProductid()->getProductid()) {
                return true;
            }

            return false;
        }

        return true;
    }

    private function isChangeCategoryValid(AxAdjustments $adjustment): bool
    {
        if (!is_null($adjustment->getCategoryid())) {
            if ($this->serviceChange->getProductid()->getCategoryid()->getCategoryid() === $adjustment->getCategoryid()->getCategoryid()) {
                return true;
            }

            return false;
        }

        return true;
    }

    protected function getServiceDiscounts(AxServices $service): array
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
                AND adj.IsUpgrade = 1
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
                AND adj.IsUpgrade = 1
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
                AND adj.IsUpgrade = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['categoryId' => $category->getCategoryid()];
        $types = ['categoryId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }
}
