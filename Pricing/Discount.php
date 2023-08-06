<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing;

use Axxess\Model\Pricing\Collections\AdjustmentCollection;
use Axxess\Model\Pricing\Collections\DiscountCollection;
use Axxess\Model\Pricing\Response\DiscountResponse;
use Axxess\Model\ReturnTypes\Pricing\DiscountResult;
use Axxess\Plugins\Doctrine\EntityManager;
use Entity\Axxess1\AxAdjustments;
use Entity\Axxess1\AxServices;
use Money\Currencies\ISOCurrencies;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

abstract class Discount implements DiscountInterface
{
    protected EntityManager $em;

    protected AxServices $service;

    public function __construct(EntityManager $em, AxServices $service)
    {
        $this->em = $em;
        $this->service = $service;
    }

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

    public function areDiscountsValid(array $adjustments)
    {
        $adjustments = $this->sortDiscounts($adjustments);

        $collection = new AdjustmentCollection();
        if (count($adjustments)) {
            foreach ($adjustments as $adjustment) {
                /**
                 * @var $adj \Entity\Axxess1\AxAdjustments
                 */
                $adj = $this->em->getReference('Entity\\Axxess1\\AxAdjustments', $adjustment['AdjustmentId']);

                if ($this->isDiscountValid($adj)) {
                    $collection->add($adj);
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

    /**
     * Sort so that product Price Discounts get Processed First.
     * ie. Stacked Discounts.
     */
    public function sortDiscounts(array $adjustments): array
    {
        usort($adjustments, function ($a, $b) {
            return $b['ProductPrice'] <=> $a['ProductPrice'];
        });

        return $adjustments;
    }

    protected function isServiceValid(AxAdjustments $adjustment): bool
    {
        if (!is_null($adjustment->getServiceid())) {
            if ($this->service === $adjustment->getServiceid()) {
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
            if ($this->service->getProductid()->getProductid() === $adjustment->getProductid()->getProductid()) {
                return true;
            }

            return false;
        }

        return true;
    }

    protected function isCategoryValid(AxAdjustments $adjustment): bool
    {
        if (!is_null($adjustment->getCategoryid())) {
            if ($this->service->getProductid()->getCategoryid()->getCategoryid() === $adjustment->getCategoryid()->getCategoryid()) {
                return true;
            }

            return false;
        }

        return true;
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

    /**
     * Using the MoneyPHP package, it doesnt do percentages very well.
     * It does do allocations via percentage though.
     *
     * https://www.moneyphp.org/en/stable/features/allocation.html
     */
    public static function discountByPercentage(Money $amount, int $percentageDiscounted): Money
    {
        $percentagePaid = 100 - $percentageDiscounted;

        list($discounted, $discountedAmount) = $amount->allocate([$percentageDiscounted, $percentagePaid]);

        return $discountedAmount;
    }

    public function applyDiscounts(AdjustmentCollection $adjustments, Money $basePrice): DiscountResult
    {
        $discountCollection = new DiscountCollection();
        $runningTotal = $basePrice;

        /**
         * @var $adjustment \Entity\Axxess1\AxAdjustments
         */
        foreach ($adjustments as $adjustment) {
            if ($this->isPriceDiscount($adjustment)) {
                $parser = new DecimalMoneyParser(new ISOCurrencies());
                $discountedAmount = $parser->parse($adjustment->getProductprice(), 'ZAR');

                //if the base price is greater than the discounted price
                if ($discountedAmount->lessThan($runningTotal)) {
                    //TODO: Return Discount Response to Collection.
                    $discountResponse = new DiscountResponse();
                    $discountResponse->setAdjustment($adjustment);
                    $discountResponse->setBaseAmount($runningTotal);
                    $discountResponse->setDiscountedAmount($discountedAmount);
                    $discountCollection->append($discountResponse);

                    $runningTotal = $discountedAmount;
                }
            }

            if ($this->isPercentageDiscount($adjustment)) {
                $discountedAmount = $this->discountByPercentage($runningTotal, (int) $adjustment->getRpercent());

                //if the base price is greater than the discounted price
                if ($discountedAmount < $runningTotal) {
                    //TODO: Return Discount Response to Collection.
                    $discountResponse = new DiscountResponse();
                    $discountResponse->setAdjustment($adjustment);
                    $discountResponse->setBaseAmount($runningTotal);
                    $discountResponse->setDiscountedAmount($discountedAmount);
                    $discountCollection->append($discountResponse);

                    $runningTotal = $discountedAmount;
                }
            }
        }

        if (!isset($discountedAmount)) {
            $discountedAmount = $basePrice;
        }

        $result = new DiscountResult(true);
        $result->setBasePrice($basePrice);
        $result->setFinalPrice($discountedAmount);
        $result->setDiscountCollection($discountCollection);

        return $result;
    }
}
