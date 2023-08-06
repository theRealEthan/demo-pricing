<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Response;

use Entity\Axxess1\AxAdjustments;

class DiscountResponse
{
    private AxAdjustments $adjustment;
    private string $name;
    private \Money\Money $baseAmount;
    private \Money\Money $discountedAmount;
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

    public function getBaseAmount(): \Money\Money
    {
        return $this->baseAmount;
    }

    public function setBaseAmount(\Money\Money $baseAmount): void
    {
        $this->baseAmount = $baseAmount;
    }

    public function getDiscountedAmount(): \Money\Money
    {
        return $this->discountedAmount;
    }

    public function setDiscountedAmount(\Money\Money $discountedAmount): void
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
