<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Signup\Categories;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Classes\Helper\Constants\ConstSubCategory;
use Axxess\Classes\Helper\Constants\SimProducts;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Model\Pricing\Signup\SignupPricing;
use Axxess\Model\Pricing\Signup\SignupPricingFactory;
use Entity\Axxess1\AxServiceFixedWireless;
use Entity\Axxess1\AxServices;
use Money\Money;

class FixedWirelessComboPricing extends SignupPricing
{
    public function getProductPrice(): PricingCollection
    {
        $combo = new PriceResponse();
        $combo->setService($this->service);
        $combo->setProduct($this->service->getProductid());
        $combo->setDiscount($this->getDiscounts());
        $combo->setVat(Money::ZAR(0));
        $combo->setAmount(Money::ZAR(0));
        $combo->setProrata($this->getProrata(Money::ZAR(0)));

        $data = new PriceResponse();
        if ($this->canBill()) {
            $data->setService($this->getDataService());
            $data->setProduct($this->getDataService()->getProductid());
            $data->setDiscount(new \Axxess\Model\ReturnTypes\Pricing\DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new \Axxess\Model\Pricing\Collections\DiscountCollection()));
            $data->setVat($this->calculateVatAmount($this->getFinalPrice()));
            $data->setAmount($this->getFinalPrice());
            $data->setProrata($this->getProrata($this->getPriceAfterDiscount()));
        } else {
            $data->setService($this->getDataService());
            $data->setProduct($this->getDataService()->getProductid());
            $data->setDiscount(new \Axxess\Model\ReturnTypes\Pricing\DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new \Axxess\Model\Pricing\Collections\DiscountCollection()));
            $data->setVat(Money::ZAR(0));
            $data->setAmount(Money::ZAR(0));
            $data->setProrata($this->getProrata(Money::ZAR(0)));
        }

        $fixedWireless = new PriceResponse();
        $fixedWireless->setService($this->getFixedWirelessService());
        $fixedWireless->setProduct($this->getFixedWirelessService()->getProductid());
        $fixedWireless->setDiscount(new \Axxess\Model\ReturnTypes\Pricing\DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new \Axxess\Model\Pricing\Collections\DiscountCollection()));
        $fixedWireless->setVat(Money::ZAR(0));
        $fixedWireless->setAmount(Money::ZAR(0));
        $fixedWireless->setProrata($this->getProrata(Money::ZAR(0)));

        $sim = new PriceResponse();
        $sim->setService($this->getSimService());
        $sim->setProduct($this->getSimService()->getProductid());
        $sim->setDiscount(new \Axxess\Model\ReturnTypes\Pricing\DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new \Axxess\Model\Pricing\Collections\DiscountCollection()));
        $sim->setVat($this->calculateVatAmount($this->getSimPrice()));
        $sim->setAmount($this->getSimPrice());
        $sim->setProrata($this->getProrata(Money::ZAR(0)));

        return new PricingCollection($combo, $data, $fixedWireless, $sim);
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

    public function getSimService(): AxServices
    {
        /**
         * @var $service AxServices
         */
        foreach ($this->getLinkedHardware() as $service) {
            if (in_array($service->getProductid()->getCategoryid()->getCategoryid(), [ConstCategory::HARDWARE, ConstCategory::OTHER]) && in_array($service->getProductid()->getSubcategoryid()->getSubcategoryid(), SimProducts::ALL_SIM_SUBCATS)) {
                return $service;
            }
        }

        throw new \Exception('Could not find SIM Service for Fixed Wireless Combo: '.$this->service->getServiceid());
    }

    public function getSimPrice(): Money
    {
        /**
         * @var $price PriceResponse
         */
        $price = SignupPricingFactory::getInstance($this->em, $this->date)->getPrice($this->getSimService(), $this->getSimService()->getProductid())[0];

        return $price->getAmount();
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

    public function getLinkedHardware(): array
    {
        try {
            $sql = 'SELECT 
                      * 
                    FROM
                      ax_services s 
                    WHERE s.CustomServiceRefId = :serviceId ';

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
