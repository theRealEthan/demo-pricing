<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\ServiceChange\Categories;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Classes\Helper\Constants\ConstSubCategory;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Model\Pricing\ServiceChange\PricingInterface;
use Axxess\Model\Pricing\ServiceChange\ServiceChangePricing;
use Entity\Axxess1\AxServiceFixedWireless;
use Entity\Axxess1\AxServices;

class FixedWirelessComboPricing extends ServiceChangePricing implements PricingInterface
{
    public function getProductPrice(): PricingCollection
    {
        try {
            $combo = new PriceResponse();
            $combo->setService($this->service);
            $combo->setProduct($this->service->getProductid());
            $combo->setDiscount(0);
            $combo->setVat(0);
            $combo->setAmount(0);

            if ($this->canBill()) {
                $data = new PriceResponse();
                $data->setService($this->getDataService());
                $data->setProduct($this->getDataService()->getProductid());
                $data->setDiscount($this->getDiscountAmount($this->service, $this->calculateProductPrice($this->service, $this->product)));
                $data->setVat($this->calculateVatAmount($this->calculateProductPrice($this->service, $this->product)));
                $data->setAmount($this->calculateProductPrice($this->service, $this->product));
            } else {
                $data = new PriceResponse();
                $data->setService($this->getDataService());
                $data->setProduct($this->getDataService()->getProductid());
                $data->setVat(0);
                $data->setAmount(0);
            }

            $fixedWireless = new PriceResponse();
            $fixedWireless->setService($this->getFixedWirelessService());
            $fixedWireless->setProduct($this->getFixedWirelessService()->getProductid());
            $fixedWireless->setDiscount(0);
            $fixedWireless->setVat(0);
            $fixedWireless->setAmount(0);

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
