<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Signup\Categories;

use Axxess\Classes\Helper\Constants\LineStatus;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Model\Pricing\Signup\SignupPricing;
use Money\Money;

class DSLLinePricing extends SignupPricing
{
    public function getProductPrice(): PricingCollection
    {
        try {
            $line = new PriceResponse();
            if ($this->canBill()) {
                $line->setService($this->service);
                $line->setProduct($this->service->getProductid());
                $line->setDiscount($this->getDiscounts());
                $line->setVat($this->calculateVatAmount($this->getFinalPrice()));
                $line->setAmount($this->getFinalPrice());
            } else {
                $line->setService($this->service);
                $line->setProduct($this->service->getProductid());
                $line->setDiscount(new \Axxess\Model\ReturnTypes\Pricing\DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new \Axxess\Model\Pricing\Collections\DiscountCollection()));
                $line->setVat(0);
                $line->setAmount(0);
            }

            return new PricingCollection($line);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function canBill(): bool
    {
        try {
            return $this->isLineActivated();
        } catch (\Exception $e) {
            //Rather bill than not
            throw $e;
        }
    }

    private function isLineActivated()
    {
        try {
            $sql = 'SELECT 
                      sl.LineStatusId
                    FROM
                      ax_servicelines sl 
                    WHERE sl.ServiceId =  :serviceId';

            $params = ['serviceId' => $this->service->getServiceid()];
            $types = ['serviceId' => \PDO::PARAM_INT];

            $lineStatus = (int) $this->em->getConnection()->fetchOne($sql, $params, $types);

            //For cancelled ones already. Use a mock class for deleted ones.
            if (LineStatus::ACTIVATED === $lineStatus) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            //Rather bill than not
            throw $e;
        }
    }
}
