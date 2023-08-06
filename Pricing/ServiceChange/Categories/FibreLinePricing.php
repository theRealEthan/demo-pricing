<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\ServiceChange\Categories;

use Axxess\Classes\Helper\Constants\LineStatus;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Model\Pricing\ServiceChange\PricingInterface;
use Axxess\Model\Pricing\ServiceChange\ServiceChangePricing;

class FibreLinePricing extends ServiceChangePricing implements PricingInterface
{
    public function getProductPrice(): PricingCollection
    {
        try {
            if ($this->canBill()) {
                $line = new PriceResponse();
                $line->setService($this->service);
                $line->setProduct($this->service->getProductid());
                $line->setDiscount($this->getDiscountAmount($this->service, $this->calculateProductPrice($this->service, $this->product)));
                $line->setVat($this->calculateVatAmount($this->calculateProductPrice($this->service, $this->product)));
                $line->setAmount($this->calculateProductPrice($this->service, $this->product));
            } else {
                $line = new PriceResponse();
                $line->setService($this->service);
                $line->setProduct($this->service->getProductid());
                $line->setDiscount(0);
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
            throw $e;
        }
    }

    private function isLineActivated()
    {
        try {
            $sql = 'SELECT 
                      sf.line_status
                    FROM
                      ax_service_fibre sf 
                    WHERE sf.service_id = :serviceId';

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
