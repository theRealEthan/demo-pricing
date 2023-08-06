<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Renewal\Categories\FixedLineCombo;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Renewal\RenewalDiscount;
use Axxess\Model\Pricing\Renewal\RenewalPricing;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Model\Pricing\ServiceProperties;
use Axxess\Model\ReturnTypes\Pricing\DiscountResult;
use Entity\Axxess1\AxServices;
use Exception;
use Money\Currencies\ISOCurrencies;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

/**
 * Fixed Line Combos are billed as such.
 *
 * Data Price = Combo Price - Line Price
 * If the line is not active, do not bill
 *
 * If The line is a transfer, until its activated, only bill the data portion.
 * As Per. \Axxess\Classes\Controllers\ControlInvoice::canChargeData
 */
abstract class FixedLineComboPricing extends RenewalPricing implements FixedLineComboPricingInterface
{
    public function getProductPrice(): PricingCollection
    {
        $comboPricingResult = $this->getPricing();

        $comboPriceResponse = new PriceResponse();
        $comboPriceResponse->setService($this->service);
        $comboPriceResponse->setProduct($this->service->getProductid());
        $comboPriceResponse->setDiscount($comboPricingResult->getDiscounts());
        $comboPriceResponse->setAmount(Money::ZAR(0));
        $comboPriceResponse->setVat(Money::ZAR(0));
        $comboPriceResponse->setServiceProperties(new ServiceProperties($this));

        $linePriceResponse = new PriceResponse();
        $linePriceResponse->setService($this->getLineService());
        $linePriceResponse->setProduct($this->getLineService()->getProductid());
        $linePriceResponse->setDiscount(new DiscountResult(true));
        $linePriceResponse->setAmount($this->getLinePrice($comboPricingResult->getFinalPrice()));
        $linePriceResponse->setVat($this->calculateVatAmount($this->getLinePrice($comboPricingResult->getFinalPrice())));

        $dataPriceResponse = new PriceResponse();
        $dataPriceResponse->setService($this->getDataService());
        $dataPriceResponse->setProduct($this->getDataService()->getProductid());
        $dataPriceResponse->setDiscount(new DiscountResult(true));
        $dataPriceResponse->setVat($this->calculateVatAmount($this->getDataPrice()));
        $dataPriceResponse->setAmount($this->getDataPrice());

        return new PricingCollection($comboPriceResponse, $linePriceResponse, $dataPriceResponse);
    }

    public function getFinalPrice(): Money
    {
        return $this->calculateServicePrice();
    }

    public function getDiscounts(): DiscountResult
    {
        $discount = new RenewalDiscount($this->em, $this->service);

        return $discount->applyDiscounts($discount->getDiscounts(), $this->getBaseProductPrice());
    }

    protected function calculateServicePrice(): Money
    {
        return $this->getDiscounts()->getFinalPrice();
    }

    public function getLineBaseProductPrice(): Money
    {
        $parser = new DecimalMoneyParser(new ISOCurrencies());

        return $parser->parse($this->getLineService()->getProductid()->getPrice(), 'ZAR');
    }

    public function getLinePrice(Money $comboPrice): Money
    {
        $linePrice = $this->getLineBaseProductPrice();

        if ($linePrice->greaterThan($comboPrice)) {
            return $comboPrice;
        }

        return $linePrice;
    }

    public function getDataPrice(): Money
    {
        $price = $this->getParentPrice()->subtract($this->getLinePrice($this->getFinalPrice()));

        if ($price->lessThan(Money::ZAR(0))) {
            return Money::ZAR(0);
        }

        return $price;
    }

    public function getParentPrice(): Money
    {
        return $this->calculateServicePrice();
    }

    public function getBundleServices(): array
    {
        $result = [];

        try {
            $services = $this->em->getConnection()->fetchAllAssociative('
            SELECT s.ServiceId
            FROM ax_services s
            WHERE s.BundleServiceId = :serviceId',
                ['serviceId' => $this->service->getServiceid()],
                ['serviceId' => \PDO::PARAM_INT]
            );

            foreach ($services as $service) {
                $result[] = $this->em->getReference('Entity\\Axxess1\\AxServices', (int) $service['ServiceId']);
            }
        } catch (Exception $e) {
            return [];
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getDataService(): AxServices
    {
        /**
         * @var $service AxServices
         */
        foreach ($this->getBundleServices() as $service) {
            if (ConstCategory::ADSL === $service->getProductid()->getCategoryid()->getCategoryid()) {
                return $service;
            }
        }

        throw new Exception('Could not find data Service for Combo: '.$this->service->getServiceid());
    }

    public function canAddToInvoice(): bool
    {
        return $this->isLineActive();
    }

    public function canAddToInvoiceReason(): string
    {
        return !$this->isLineActive() ? 'Line not activated' : '';
    }
}
