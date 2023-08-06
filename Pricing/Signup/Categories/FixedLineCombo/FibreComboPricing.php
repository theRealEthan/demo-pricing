<?php

namespace Axxess\Model\Pricing\Signup\Categories\FixedLineCombo;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Classes\Helper\Constants\LineStatus;
use Entity\Axxess1\AxServiceFibre;
use Entity\Axxess1\AxServices;

/**
 * Class FibreComboPricing.
 */
class FibreComboPricing extends FixedLineComboPricing
{
    public function getLineService(): AxServices
    {
        /**
         * @var $service AxServices
         */
        foreach ($this->getBundledServices() as $service) {
            if (ConstCategory::FIBRE === $service->getProductid()->getCategoryid()->getCategoryid()) {
                return $service;
            }
        }

        throw new \Exception('Could not find line Service for Combo: '.$this->service->getServiceid());
    }

    public function getLineDetails(): AxServiceFibre
    {
        return $this->em->getReference('Entity\\Axxess1\\AxServiceFibre', $this->getLineService()->getServiceid());
    }

    public function isLineActive(): bool
    {
        if (LineStatus::ACTIVATED === $this->getLineDetails()->getLineStatus()->getLinestatusid()) {
            return true;
        }

        return false;
    }

    public function isLineTransfer(): bool
    {
        if ($this->getLineDetails()->getIsTransfer()) {
            return true;
        }

        return false;
    }
}
