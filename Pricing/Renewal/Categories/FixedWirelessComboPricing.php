<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Renewal\Categories;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Classes\Helper\Constants\ConstSubCategory;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Renewal\RenewalPricing;
use Axxess\Model\Pricing\Response\PriceResponse;
use Entity\Axxess1\AxServiceFixedWireless;
use Entity\Axxess1\AxServices;
use Money\Money;

/**
 * Fixed Wireless Combos are billed as such.
 *
 * Combo Price is billed on the Data Portion.
 * If the service is not active, do not bill. ax_service_fixed_wireless date_activated
 *
 * Class FixedWirelessComboPricing
 */
class FixedWirelessComboPricing extends RenewalPricing
{
    public function getProductPrice(): PricingCollection
    {
        try {
            $combo = new PriceResponse();
            $combo->setService($this->service);
            $combo->setProduct($this->service->getProductid());
            $combo->setDiscount($this->getDiscounts());
            $combo->setVat(Money::ZAR(0));
            $combo->setAmount(Money::ZAR(0));

            $data = new PriceResponse();
            if ($this->canBill()) {
                $data->setService($this->getDataService());
                $data->setProduct($this->getDataService()->getProductid());
                $data->setDiscount(new \Axxess\Model\ReturnTypes\Pricing\DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new \Axxess\Model\Pricing\Collections\DiscountCollection()));
                $data->setVat($this->calculateVatAmount($this->getFinalPrice()));
                $data->setAmount($this->getFinalPrice());
            } else {
                $data->setService($this->getDataService());
                $data->setProduct($this->getDataService()->getProductid());
                $data->setDiscount(new \Axxess\Model\ReturnTypes\Pricing\DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new \Axxess\Model\Pricing\Collections\DiscountCollection()));
                $data->setVat(Money::ZAR(0));
                $data->setAmount(Money::ZAR(0));
            }

            $fixedWireless = new PriceResponse();
            $fixedWireless->setService($this->getFixedWirelessService());
            $fixedWireless->setProduct($this->getFixedWirelessService()->getProductid());
            $fixedWireless->setDiscount(new \Axxess\Model\ReturnTypes\Pricing\DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new \Axxess\Model\Pricing\Collections\DiscountCollection()));
            $fixedWireless->setVat(Money::ZAR(0));
            $fixedWireless->setAmount(Money::ZAR(0));

            return new PricingCollection($combo, $data, $fixedWireless);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function canBill(): bool
    {
        try {
            $excludedProviders = [
                ConstSubCategory::MTN_LTE_CAPPED_COMBO,
                ConstSubCategory::RESELLER_MTN_LTE_CAPPED_COMBO,
                ConstSubCategory::MTN_LTE_UNCAPPED_COMBO,
                ConstSubCategory::RESELLER_MTN_LTE_UNCAPPED_COMBO,
                ConstSubCategory::MTN_5G_COMBO,
                ConstSubCategory::RESELLER_MTN_5G_COMBO,
            ];

            if (in_array($this->service->getProductid()->getSubcategoryid()->getSubcategoryid(), $excludedProviders)) {
                return true;
            }

            if ($this->isActive()) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function isActive(): bool
    {
        try {
            if (!is_null($this->getFixedWirelessDetails()->getDateActivated())) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getFixedWirelessService(): AxServices
    {
        try {
            /**
             * @var $service AxServices
             */
            foreach ($this->getBundledServices() as $service) {
                if (ConstCategory::FIXEDWIRELESS === $service->getProductid()->getCategoryid()->getCategoryid()) {
                    return $service;
                }
            }

            throw new \Exception('Could not find fixed wireless Service for Combo: '.$this->service->getServiceid());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getDataService(): AxServices
    {
        try {
            /**
             * @var $service AxServices
             */
            foreach ($this->getBundledServices() as $service) {
                if (ConstCategory::FIXEDWIRELESS_DATA === $service->getProductid()->getCategoryid()->getCategoryid()) {
                    return $service;
                }
            }

            throw new \Exception('Could not find data Service for Combo: '.$this->service->getServiceid());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getFixedWirelessDetails(): AxServiceFixedWireless
    {
        try {
            /**
             * @var AxServiceFixedWireless $objectFixedWireless
             */
            $query = $this->em->createQuery('SELECT sfw FROM Entity\\Axxess1\\AxServiceFixedWireless sfw WHERE sfw.service = ?1');
            $query->setParameter(1, $this->em->getReference('Entity\\Axxess1\\AxServiceFixedWireless', (int) $this->getFixedWirelessService()->getServiceid()));
            $objectFixedWireless = $query->getSingleResult();

            return $objectFixedWireless;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getBundledServices(): array
    {
        try {
            $sql = 'SELECT 
                      * 
                    FROM
                      ax_services s 
                    WHERE s.BundleServiceId = :serviceId ';

            $params = ['serviceId' => $this->service->getServiceid()];
            $types = ['serviceId' => \PDO::PARAM_INT];

            $services = $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);

            $result = [];
            foreach ($services as $service) {
                /*
                 *@var AxServices
                 */
                $result[] = $this->em->getReference('Entity\\Axxess1\\AxServices', (int) $service['ServiceId']);
            }

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
