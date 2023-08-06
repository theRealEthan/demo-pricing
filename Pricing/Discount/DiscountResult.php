<?php

namespace Axxess\Model\Pricing\Discount;

use Axxess\Model\ReturnTypes\User\ReturnType1Abstract;

/**
 * Returns whether the purchase was successful or not and gives a descriptive message.
 *
 * Class RedeemDiscountResult
 */
class DiscountResult extends ReturnType1Abstract
{
    protected float $basePrice;
    protected float $finalPrice;

    protected DiscountCollection $discountCollection;

    /**
     * @param $result
     * @param $userMessage
     * @param \stdClass|null $otherResults
     *
     * @throws \Throwable
     */
    public function __construct($result, $userMessage, $basePrice, $finalPrice, DiscountCollection $discountCollection)
    {
        try {
            $this->basePrice = $basePrice;
            $this->finalPrice = $finalPrice;
            $this->discountCollection = $discountCollection;

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

    public function getDiscountCollection(): DiscountCollection
    {
        return $this->discountCollection;
    }

    public function setDiscountCollection(DiscountCollection $discountCollection): void
    {
        $this->discountCollection = $discountCollection;
    }
}
