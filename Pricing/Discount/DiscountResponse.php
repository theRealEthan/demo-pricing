<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount;

use Entity\Axxess1\AxAdjustments;

class DiscountResponse
{
    private AxAdjustments $adjustment;
    private string $name;
    private float $baseAmount;
    private float $discountedAmount;
    private bool $isRounded;

    public function getAdjustment(): AxAdjustments
    {
        return $this->adjustment;
    }

    public function setAdjustment(AxAdjustments $adjustment): void
    {
        $this->adjustment = $adjustment;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getBaseAmount(): float
    {
        return $this->baseAmount;
    }

    public function setBaseAmount(float $baseAmount): void
    {
        $this->baseAmount = $baseAmount;
    }

    public function getDiscountedAmount(): float
    {
        return $this->discountedAmount;
    }

    public function setDiscountedAmount(float $discountedAmount): void
    {
        $this->discountedAmount = $discountedAmount;
    }

    public function isRounded(): bool
    {
        return $this->isRounded;
    }

    public function setIsRounded(bool $isRounded): void
    {
        $this->isRounded = $isRounded;
    }
}
