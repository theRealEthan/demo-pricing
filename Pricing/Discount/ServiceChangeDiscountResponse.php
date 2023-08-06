<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount;

class ServiceChangeDiscountResponse extends DiscountResponse
{
    protected bool $isNewProduct = false;
    protected bool $isOldProduct = false;

    public function isNewProduct(): bool
    {
        return $this->isNewProduct;
    }

    public function setIsNewProduct(bool $isNewProduct): void
    {
        $this->isNewProduct = $isNewProduct;
    }

    public function isOldProduct(): bool
    {
        return $this->isOldProduct;
    }

    public function setIsOldProduct(bool $isOldProduct): void
    {
        $this->isOldProduct = $isOldProduct;
    }
}
