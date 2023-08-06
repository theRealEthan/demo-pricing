<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Response;

use Axxess\Model\Pricing\ServiceProperties;
use Axxess\Model\ReturnTypes\Pricing\DiscountResult;
use Axxess\Model\ReturnTypes\Pricing\ProrataResult;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;
use Money\Money;

/**
 * Class PriceResponse.
 */
class PriceResponse
{
    private AxServices $service;

    private AxProducts $product;

    private Money $basePrice;

    private DiscountResult $discount;

    private ServiceProperties $serviceProperties;

    private Money $vat;

    private Money $amount;

    private ProrataResult $prorata;

    public function getService(): AxServices
    {
        return $this->service;
    }

    public function setService(AxServices $service): void
    {
        $this->service = $service;
    }

    public function getProduct(): AxProducts
    {
        return $this->product;
    }

    public function setProduct(AxProducts $product): void
    {
        $this->product = $product;
    }

    public function getDiscount(): DiscountResult
    {
        return $this->discount;
    }

    public function setDiscount(DiscountResult $discount): void
    {
        $this->discount = $discount;
    }

    public function getServiceProperties(): ServiceProperties
    {
        return $this->serviceProperties;
    }

    public function setServiceProperties(ServiceProperties $serviceProperties): void
    {
        $this->serviceProperties = $serviceProperties;
    }

    public function getVat(): Money
    {
        return $this->vat;
    }

    public function setVat(Money $vat): void
    {
        $this->vat = $vat;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function setAmount(Money $amount): void
    {
        $this->amount = $amount;
    }

    public function getProrata(): ProrataResult
    {
        return $this->prorata;
    }

    public function setProrata(ProrataResult $prorata): void
    {
        $this->prorata = $prorata;
    }

    public function getBasePrice(): Money
    {
        return $this->basePrice;
    }

    public function setBasePrice(Money $basePrice): void
    {
        $this->basePrice = $basePrice;
    }
}
