<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount;

use Axxess\Classes\Helper\General;
use Axxess\Model\AbstractLogging;
use Axxess\Plugins\Doctrine\EntityManager;
use Entity\Axxess1\AxAdjustments;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;

abstract class Discount implements DiscountInterface
{
    //1. Get the discount type/types
    //2. return discounted price and discount name (return type?)

    protected EntityManager $em;
    protected AxServices $service;
    protected AxProducts $product;
    protected float $price;

    public function __construct(EntityManager $em, AxServices $service, AxProducts $product, float $price)
    {
        $this->em = $em;
        $this->service = $service;
        $this->product = $product;
        $this->price = $price;
    }

    protected function isServiceValid(AxAdjustments $adjustment): bool
    {
        if (!is_null($adjustment->getServiceid())) {
            if ($this->service->getServiceid() === $adjustment->getServiceid()) {
                return true;
            }

            return false;
        }

        return true;
    }

    protected function isProfileValid(AxAdjustments $adjustment): bool
    {
        if (!is_null($adjustment->getAccountid())) {
            if ($this->service->getAccountid()->getAccountid() === $adjustment->getAccountid()->getAccountid()) {
                return true;
            }

            return false;
        }

        return true;
    }

    protected function isProductValid(AxAdjustments $adjustment): bool
    {
        if (!is_null($adjustment->getProductid())) {
            if ($this->product->getProductid() === $adjustment->getProductid()->getProductid()) {
                return true;
            }

            return false;
        }

        return true;
    }

    protected function isCategoryValid(AxAdjustments $adjustment): bool
    {
        if (!is_null($adjustment->getCategoryid())) {
            if ($this->product->getCategoryid()->getCategoryid() === $adjustment->getCategoryid()->getCategoryid()) {
                return true;
            }

            return false;
        }

        return true;
    }

    public function getDiscountedAmount(): DiscountResult
    {
        $adjustments = $this->getRawDiscount();
        $discountCollection = new DiscountCollection();
        $basePrice = $this->price;

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
                if ($discountedAmount < $this->price) {
                    //TODO: Return Discount Response to Collection.
                    $discountResponse = new DiscountResponse();
                    $discountResponse->setAdjustment($adjustment);
                    $discountResponse->setBaseAmount($this->price);
                    $discountResponse->setDiscountedAmount($discountedAmount);
                    if ($isLast) {
                        $discountResponse->setDiscountedAmount(ceil($discountedAmount));
                        $discountResponse->setIsRounded(true);
                    }

                    $discountCollection->append($discountResponse);

                    $this->price = (float) $discountedAmount;
                }
                //For Price Stacking.

                //TODO: Return Discount Response to Collection.
                $discountResponse = new DiscountResponse();
                $discountResponse->setAdjustment($adjustment);
                //$discountResponse->set
            }

            if ($this->isPercentageDiscount($adjustment)) {
                $discountedAmount = $this->price - (($this->price * $adjustment->getRpercent()) / 100);

                if ($isLast) {
                    $discountedAmount = ceil($discountedAmount);
                }

                //if the base price is greater than the discounted price
                if ($discountedAmount < $this->price) {
                    //TODO: Return Discount Response to Collection.
                    $discountResponse = new DiscountResponse();
                    $discountResponse->setAdjustment($adjustment);
                    $discountResponse->setBaseAmount($this->price);
                    $discountResponse->setDiscountedAmount($discountedAmount);
                    if ($isLast) {
                        $discountResponse->setDiscountedAmount(ceil($discountedAmount));
                        $discountResponse->setIsRounded(true);
                    }

                    $discountCollection->append($discountResponse);

                    $this->price = (float) $discountedAmount;
                }

                $this->price = (float) $discountedAmount;
            }
        }

        if (!isset($discountedAmount)) {
            $discountedAmount = $basePrice;
        }

        return new DiscountResult(true, 'success', $basePrice, ceil($discountedAmount), $discountCollection);
    }

    protected function isPercentageDiscount(AxAdjustments $adjustment)
    {
        if (!is_null($adjustment->getRpercent())) {
            return true;
        }
    }

    protected function isPriceDiscount(AxAdjustments $adjustment)
    {
        if (!is_null($adjustment->getProductprice())) {
            return true;
        }
    }

    protected function createDiscount()
    {
    }

    public static function getAllProfileDiscounts(EntityManager $em, int $accountId): array
    {
        $sql = '
            SELECT
                ad.AdjustmentId,
                ad.AccountId, 
                ad.RPercent, 
                ad.StartDate, 
                ad.EndDate, 
                ad.IsTopup,
                ad.isNewSignup,
                ad.IsUpgrade,
                ad.IsRenewal, 
                ad.DateCreated,
                op.Fullname,
                aoa.reason
            FROM ax_adjustments ad
            LEFT JOIN ax_adjustment_type adt ON ad.adjustmentTypeId = adt.id
            INNER JOIN ax_operator_adjustments aoa ON ad.`AdjustmentId` = aoa.`adjustment_id`
            INNER JOIN ax_operator op ON aoa.`operator_id` = op.OperatorId
            WHERE AccountId = :accountId
            AND(
                ad.EndDate > NOW()
                OR ad.EndDate IS NULL
            )
                ';

        $params = ['accountId' => $accountId];
        $types = ['accountId' => \PDO::PARAM_INT];

        return $em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    public static function getAllServiceDiscounts(EntityManager $em, int $serviceId): array
    {
        $sql = '
            SELECT
	        serv.`AccountId`,
                ad.AdjustmentId,
                ad.ServiceId,  
                ad.RPercent,
                ad.ProductPrice,
                ad.StartDate, 
                ad.EndDate, 
                ad.IsTopup, 
                ad.IsUpgrade,  
                ad.IsRenewal, 
                ad.DateCreated, 
                op.Fullname, 
                aoa.reason
            FROM ax_adjustments ad
            INNER JOIN ax_adjustment_type adt ON ad.adjustmentTypeId = adt.id
            INNER JOIN ax_operator_adjustments aoa ON ad.`AdjustmentId` = aoa.`adjustment_id`
            INNER JOIN ax_operator op ON aoa.`operator_id` = op.OperatorId
            INNER JOIN ax_services serv ON serv.`ServiceId` = ad.`ServiceId`
            WHERE ad.`ServiceId` = :serviceId
            AND(
                ad.EndDate > NOW()
                OR ad.EndDate IS NULL
            )
                ';

        $params = ['serviceId' => $serviceId];
        $types = ['serviceId' => \PDO::PARAM_INT];

        return $em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    public static function getAllActiveDiscounts(EntityManager $em, int $accountId): array
    {
        $sql = '       
            SELECT 
                ad.AdjustmentId,
            ad.ServiceId,
                ad.AccountId,  
                ad.RPercent,
                ad.ProductPrice,
                ad.StartDate, 
                ad.EndDate, 
                ad.IsNewSignup,
                ad.IsTopup, 
                ad.IsUpgrade,  
                ad.IsRenewal, 
                ad.DateCreated, 
                op.Fullname, 
                oa.reason
            FROM ax_services s
            INNER JOIN ax_adjustments ad ON s.ServiceId = ad.ServiceId
            INNER JOIN ax_operator_adjustments oa ON ad.AdjustmentId = oa.adjustment_id
            INNER JOIN ax_operator op ON op.OperatorId = oa.operator_id
            WHERE s.AccountId = :accountId
            AND(
                ad.EndDate > NOW()
                OR ad.EndDate IS NULL
            )

            UNION 

            SELECT 
                ad.AdjustmentId,
                ad.ServiceId,
                ad.AccountId,  
                ad.RPercent, 
                ad.ProductPrice,
                ad.StartDate, 
                ad.EndDate, 
                ad.IsNewSignup,
                ad.IsTopup, 
                ad.IsUpgrade,  
                ad.IsRenewal, 
                ad.DateCreated, 
                op.Fullname, 
                oa.reason
            FROM ax_adjustments ad
            INNER JOIN ax_operator_adjustments oa ON ad.AdjustmentId = oa.adjustment_id
            INNER JOIN ax_operator op ON op.OperatorId = oa.operator_id
            WHERE ad.AccountId = :accountId
            AND(
                ad.EndDate > NOW()
                OR ad.EndDate IS NULL
            )
               ';

        $params = ['accountId' => $accountId];
        $types = ['accountId' => \PDO::PARAM_INT];

        return $em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    public static function getServiceDiscountHistory(EntityManager $em, int $accountId): array
    {
        $sql = '       
            SELECT 
                ad.AdjustmentId,
            ad.ServiceId,
                ad.AccountId,  
                ad.RPercent, 
                ad.ProductPrice,
                ad.StartDate, 
                ad.EndDate,
                ad.IsNewSignup,
                ad.IsTopup, 
                ad.IsUpgrade,  
                ad.IsRenewal, 
                ad.DateCreated, 
                op.Fullname, 
                oa.reason
            FROM ax_services s
            INNER JOIN ax_adjustments ad ON s.ServiceId = ad.ServiceId
            INNER JOIN ax_operator_adjustments oa ON ad.AdjustmentId = oa.adjustment_id
            INNER JOIN ax_operator op ON op.OperatorId = oa.operator_id
            WHERE s.AccountId = :accountId

            UNION 

            SELECT 
                ad.AdjustmentId,
                ad.ServiceId,
                ad.AccountId,  
                ad.RPercent, 
                ad.ProductPrice,
                ad.StartDate, 
                ad.EndDate, 
                ad.IsNewSignup,
                ad.IsTopup, 
                ad.IsUpgrade,  
                ad.IsRenewal, 
                ad.DateCreated, 
                op.Fullname, 
                oa.reason
            FROM ax_adjustments ad
            INNER JOIN ax_operator_adjustments oa ON ad.AdjustmentId = oa.adjustment_id
            INNER JOIN ax_operator op ON op.OperatorId = oa.operator_id
            WHERE ad.AccountId = :accountId
               ';

        $params = ['accountId' => $accountId];
        $types = ['accountId' => \PDO::PARAM_INT];

        return $em->getConnection()->fetchAllAssociative($sql, $params, $types);
    }

    public static function deleteDiscount(EntityManager $em, int $adjustmentId): bool
    {
        try {
            $result = false;

            $sql = '
                UPDATE ax_adjustments
                SET EndDate = NOW()      
                WHERE AdjustmentId = :adjustmentId
                    ';

            $params = ['adjustmentId' => $adjustmentId];
            $types = ['adjustmentId' => \PDO::PARAM_INT];

            $updateSql = $em->getConnection()->executeQuery($sql, $params, $types);
            if ($updateSql) {
                $result = true;
            }
        } catch (\Exception $e) {
            General::alertDevs(__METHOD__, AbstractLogging::dump($e));
        }

        return $result;
    }

    public static function doesProfileDiscountExist(EntityManager $em, int $accountId, int $adjustmentTypeId): bool
    {
        try {
            $result = false;

            $sql = '
            SELECT COUNT(*)
            FROM ax_adjustments ad 
            WHERE ad.AccountId = :accountId
            AND ad.AdjustmentTypeId = :adjustmentTypeId
            AND(
                ad.EndDate > NOW()
                OR ad.EndDate IS NULL
            )
                ';

            $params = [
                'accountId' => $accountId,
                'adjustmentTypeId' => $adjustmentTypeId,
            ];

            $types = [
                'accountId' => \PDO::PARAM_INT,
                'adjustmentTypeId' => \PDO::PARAM_INT,
                'isNewSignUp' => \PDO::PARAM_BOOL,
                'isTopup' => \PDO::PARAM_BOOL,
                'isUpgrade' => \PDO::PARAM_BOOL,
                'isRenewal' => \PDO::PARAM_BOOL,
            ];

            $doesItExist = $em->getConnection()->fetchOne($sql, $params, $types);
            if ((int) $doesItExist > 0) {
                $result = true;
            }
        } catch (\Exception $e) {
            General::alertDevs(__METHOD__, AbstractLogging::dump($e));
        }

        return $result;
    }

    public static function doesServiceDiscountExist(EntityManager $em, int $serviceId, int $adjustmentTypeId): bool
    {
        try {
            $result = false;

            $sql = '
            SELECT COUNT(*)
            FROM ax_adjustments ad 
            WHERE ad.ServiceId = :serviceId
            AND ad.AdjustmentTypeId = :adjustmentTypeId
            AND(
                ad.EndDate > NOW()
                OR ad.EndDate IS NULL
            )
                ';

            $params = [
                'serviceId' => $serviceId,
                'adjustmentTypeId' => $adjustmentTypeId,
            ];

            $types = [
                'serviceId' => \PDO::PARAM_INT,
                'adjustmentTypeId' => \PDO::PARAM_INT,
            ];

            $doesItExist = $em->getConnection()->fetchOne($sql, $params, $types);
            if ((int) $doesItExist > 0) {
                $result = true;
            }
        } catch (\Exception $e) {
            General::alertDevs(__METHOD__, AbstractLogging::dump($e));
        }

        return $result;
    }
}
