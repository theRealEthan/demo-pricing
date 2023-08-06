<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount\Create;

use Axxess\Model\Pricing\Discount\DiscountTypes;
use Axxess\Model\Pricing\PricingException;
use Entity\Axxess1\AxAccountdetails;
use Entity\Axxess1\AxAdjustments;
use Entity\Axxess1\AxOperatorAdjustments;
use Entity\Axxess1\AxServices;

class NewServiceDiscount extends NewDiscount
{
    public function create(AxServices $service, AxAccountdetails $account): AxAdjustments
    {
        $discount = new AxAdjustments();
        $discount->setDatecreated(new \DateTime());

        if (!is_null($this->percentage)) {
            $discount->setRpercent($this->percentage);
        }

        if (!is_null($this->price)) {
            $discount->setProductprice($this->price);
        }

        if (!is_null($this->startDate)) {
            $discount->setStartdate($this->startDate);
        }
        if (!is_null($this->endDate)) {
            $discount->setEnddate($this->endDate);
        }

        if (!is_null($this->adjustmentTypeId)) {
            $discount->setAdjustmenttypeid($this->em->getReference('Entity\\Axxess1\\AxAdjustmentType', $this->adjustmentTypeId));
        }

        foreach ($this->discountType as $type) {
            switch ((int) $type) {
                case DiscountTypes::TOPUP:
                    $discount->setIstopup(true);
                    break;
                case DiscountTypes::SERVICE_CHANGE:
                    $discount->setIsupgrade(true);
                    break;
                case DiscountTypes::RENEWAL:
                    $discount->setIsrenewal(true);
                    break;
                default:
                    throw new PricingException("Discount Type {$this->discountType} not found");
            }
        }

        $discount->setServiceid($service);
        $discount->setAccountid($account);
        $this->em->persist($discount);
        $this->em->flush();

        if (!is_null($this->operatorId)) {
            $operatorAdjustment = new AxOperatorAdjustments();
            $operatorAdjustment->setAdjustment($discount);
            $operatorAdjustment->setOperator($this->em->getReference('Entity\\Axxess1\\AxOperator', $this->operatorId));

            if (!is_null($this->reason)) {
                $operatorAdjustment->setReason($this->reason);
            }
        }

        $this->em->persist($operatorAdjustment);
        $this->em->flush();

        return $discount;
    }
}
