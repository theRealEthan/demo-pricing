<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Signup;

use Axxess\Model\Pricing\Discount;
use Entity\Axxess1\AxAccountdetails;
use Entity\Axxess1\AxCategory;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;

class SignupDiscount extends Discount
{
    //1. Get the discount type/types
    //2. return discounted price and discount name (return type?)
    protected function getServiceDiscounts(AxServices $service): array
    {
        $sql = 'SELECT 
                  adj.*
                FROM
                  ax_adjustments adj
                WHERE adj.ServiceId = :serviceId
                AND adj.IsNewSignup = 1
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
                AND adj.IsNewSignup = 1
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
                AND adj.IsNewSignup = 1
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
                AND adj.IsNewSignup = 1
                AND (adj.StartDate IS NULL OR adj.StartDate <= NOW())
                AND (adj.EndDate IS NULL OR adj.EndDate >= NOW())
                ORDER BY adj.ProductPrice DESC';

        $params = ['categoryId' => $category->getCategoryid()];
        $types = ['categoryId' => \PDO::PARAM_INT];

        return $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }
}
