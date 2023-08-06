<?php

namespace Axxess\Model\Pricing\Discount;

use Axxess\Model\ReturnTypes\User\ReturnType1Abstract;

/**
 * Returns whether the purchase was successful or not and gives a descriptive message.
 *
 * Class RedeemDiscountResult
 */
class ServiceChangeDiscountResult extends ReturnType1Abstract
{
    protected float $basePrice;
    protected float $finalPrice;

    protected DiscountResult $oldProductDiscountResult;

    protected DiscountResult $newProductDiscountResult;

    /**
     * @param $result
     * @param $userMessage
     * @param \stdClass|null $otherResults
     *
     * @throws \Throwable
     */
    public function __construct($result, $userMessage, DiscountResult $oldProductDiscountResult, DiscountResult $newProductDiscountResult)
    {
        try {
            $this->oldProductDiscountResult = $oldProductDiscountResult;
            $this->newProductDiscountResult = $newProductDiscountResult;

            //Next constructor throws an exception on failure and params have no default values
            parent::__construct($result, $userMessage);
        } catch (\Throwable $ex) {
            throw $ex;
        }
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function setBasePrice(float $basePrice): void
    {
        $this->basePrice = $basePrice;
    }

    public function getFinalPrice(): float
    {
        return $this->finalPrice;
    }

    public function setFinalPrice(float $finalPrice): void
    {
        $this->finalPrice = $finalPrice;
    }

    public function getOldProductDiscountResult(): DiscountResult
    {
        return $this->oldProductDiscountResult;
    }

    public function setOldProductDiscountResult(DiscountResult $oldProductDiscountResult): void
    {
        $this->oldProductDiscountResult = $oldProductDiscountResult;
    }

    public function getNewProductDiscountResult(): DiscountResult
    {
        return $this->newProductDiscountResult;
    }

    public function setNewProductDiscountResult(DiscountResult $newProductDiscountResult): void
    {
        $this->newProductDiscountResult = $newProductDiscountResult;
    }
}
