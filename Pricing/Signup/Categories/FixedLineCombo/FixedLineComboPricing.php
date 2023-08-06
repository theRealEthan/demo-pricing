<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Signup\Categories\FixedLineCombo;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Model\Pricing\Collections\DiscountCollection;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Model\Pricing\Signup\SignupPricing;
use Axxess\Model\Pricing\Signup\SignupPricingFactory;
use Axxess\Model\ReturnTypes\Pricing\DiscountResult;
use Entity\Axxess1\AxServices;
use Money\Money;

/**
 * Fixed Line Combos are billed as such.
 *
 * Data Price = Combo Price - Line Price
 * If the line is not active, do not bill
 *
 * If The line is a transfer, until its activated, only bill the data portion.
 * As Per. \Axxess\Classes\Controllers\ControlInvoice::canChargeData
 *
 *
 * Class FixedLineComboPricing
 */
abstract class FixedLineComboPricing extends SignupPricing implements FixedLineComboPricingInterface
{
    //TODO: DISCUSS COMBO LINK
    public function getProductPrice(): PricingCollection
    {
        $combo = new PriceResponse();
        $combo->setService($this->service);
        $combo->setProduct($this->service->getProductid());
        $combo->setDiscount($this->getDiscounts());
        $combo->setVat(Money::ZAR(0));
        $combo->setAmount(Money::ZAR(0));
        $combo->setProrata($this->getProrata(Money::ZAR(0)));

        if ($this->isLineActive()) {
            $line = new PriceResponse();
            $line->setService($this->getLineService());
            $line->setProduct($this->getLineService()->getProductid());
            $line->setDiscount(new DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new DiscountCollection()));
            $line->setVat($this->calculateVatAmount($this->getFinalLinePrice()));
            $line->setAmount($this->getFinalLinePrice());
            $line->setProrata($this->getProrata($this->getFinalLinePrice()));
        } else {
            $line = new PriceResponse();
            $line->setService($this->getLineService());
            $line->setProduct($this->getLineService()->getProductid());
            $line->setDiscount(new DiscountResult(true, 'success', (Money::ZAR(0)), (Money::ZAR(0)), new DiscountCollection()));
            $line->setVat(Money::ZAR(0));
            $line->setAmount(Money::ZAR(0));
            $line->setProrata($this->getProrata(Money::ZAR(0)));
        }

        if ($this->canChargeData()) {
            $data = new PriceResponse();
            $data->setService($this->getDataService());
            $data->setProduct($this->getDataService()->getProductid());
            $data->setDiscount(new DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new DiscountCollection()));
            $data->setVat($this->calculateVatAmount($this->getFinalDataPrice()));
            $data->setAmount($this->getFinalPrice());
            $data->setProrata($this->getProrata($this->getFinalDataPrice()));
        } else {
            $data = new PriceResponse();
            $data->setService($this->getDataService());
            $data->setProduct($this->getDataService()->getProductid());
            $data->setDiscount(new DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new DiscountCollection()));
            $data->setVat(Money::ZAR(0));
            $data->setAmount(Money::ZAR(0));
            $data->setProrata($this->getProrata(Money::ZAR(0)));
        }

        return new PricingCollection($combo, $line, $data);
    }

    public function getParentPrice(): Money
    {
        $price = $this->getPriceAfterDiscount();

        if ($this->service->getProductid()->getIsprorata()) {
            return $this->getProrata($price)->getProrataPrice();
        }
    }

    /**
     * Set IsBundle = 1 because its a service within a combo.
     * Ref: ControlInvoice Line 1089.
     *
     * Check if line price is > Combo Price. (Our Combo Pricing doesnt scale)
     * If it is, Then the line price = Combo Price, and Data will be billed at R0;
     * Ref: Control Invoice Line 1164
     */
    public function getBaseLinePrice(): Money
    {
        /**
         * @var $price PriceResponse
         */
        $price = SignupPricingFactory::getInstance($this->em, $this->date)->getPrice($this->getLineService(), $this->getLineService()->getProductid())[0];
        $linePrice = $price->getAmount();

        if ($linePrice->greaterThan($this->getParentPrice())) {
            $linePrice = $this->getParentPrice();
        }

        return $linePrice;
    }

    /**
     * This is for line activations.
     * The only time a combo will be billed prorata.
     *
     * On New Signup, the line is not active, therefore the service doesnt bill(unless its the data).
     */
    public function getFinalLinePrice(): Money
    {
        $linePrice = $this->getBaseLinePrice();

        if ($this->getLineService()->getProductid()->getIsprorata()) {
            return $this->getProrata($linePrice)->getProrataPrice();
        }

        return $linePrice;
    }

    public function getDataService(): AxServices
    {
        /**
         * @var $service AxServices
         */
        foreach ($this->getBundledServices() as $service) {
            if (ConstCategory::ADSL === $service->getProductid()->getCategoryid()->getCategoryid()) {
                return $service;
            }
        }

        throw new \Exception('Could not find data Service for Combo: '.$this->service->getServiceid());
    }

    /**
     * Data In a combo is billed as Follows;.
     *
     * Combo Price - Line Price = Data Price;
     *
     * Ref: Control Invoice Line 1133
     */
    public function getBaseDataPrice(): Money
    {
        $price = $this->getParentPrice()->subtract($this->getBaseLinePrice());

        if ($price->lessThan(Money::ZAR(0))) {
            return Money::ZAR(0);
        }

        return $price;
    }

    /**
     * This is for new signups of transfer lines, and combo activations.
     *
     * Every Other TIme, the combo will be billed via renewal.
     *
     * @throws \Exception
     */
    public function getFinalDataPrice(): Money
    {
        $dataPrice = $this->getBaseDataPrice();

        if ($this->getDataService()->getProductid()->getIsprorata()) {
            return $this->getProrata($dataPrice)->getProrataPrice();
        }

        return $dataPrice;
    }

    /**
     * If line is active. Data can be billed.
     * If line is not active but is a transfer, Data Can be Billed.
     * If line is not active and not a transfer, Data cannot be billed.
     *
     * Ref:\Axxess\Classes\Controllers\ControlInvoice::canChargeData
     */
    public function canChargeData(): bool
    {
        if ($this->isLineActive()) {
            return true;
        }

        if ($this->isLineTransfer()) {
            return true;
        }

        return false;
    }

    public function canChargeLine(): bool
    {
        if ($this->isLineActive()) {
            return true;
        }
    }

    public function getBundledServices(): array
    {
        $sql = 'SELECT 
                      * 
                    FROM
                      ax_services s 
                    WHERE s.BundleServiceId = :serviceId ';

        $params = ['serviceId' => $this->service->getServiceid()];
        $types = ['serviceId' => \PDO::PARAM_INT];

        $services = $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);

        if (2 !== count($services)) {
            throw new \Exception('Could not find two Services that make up combo: '.$this->service->getServiceid());
        }

        $result = [];
        foreach ($services as $service) {
            /*
             *@var AxServices
             */
            $result[] = $this->em->getReference('Entity\\Axxess1\\AxServices', (int) $service['ServiceId']);
        }

        return $result;
    }

    public function canBill(): bool
    {
        return true;
    }
}
