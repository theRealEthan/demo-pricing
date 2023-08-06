<?php

namespace Axxess\Model\Pricing\Signup\Categories\FixedLineCombo;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Classes\Helper\Constants\LineStatus;
use Entity\Axxess1\AxServicelines;
use Entity\Axxess1\AxServices;

class DSLComboPricing extends FixedLineComboPricing
{
    public function getLineService(): AxServices
    {
        /**
         * @var $service AxServices
         */
        foreach ($this->getBundledServices() as $service) {
            if (ConstCategory::LINES === $service->getProductid()->getCategoryid()->getCategoryid()) {
                return $service;
            }
        }

        throw new \Exception('Could not find line Service for Combo: '.$this->service->getServiceid());
    }

    public function getLineDetails(): AxServicelines
    {
        return $this->em->getReference('Entity\\Axxess1\\AxServicelines', $this->getLineService()->getServiceid());
    }

    public function isLineActive(): bool
    {
        if (LineStatus::ACTIVATED === $this->getLineDetails()->getLinestatusid()->getLinestatusid()) {
            return true;
        }

        return false;
    }

    public function isLineTransfer(): bool
    {
        if ($this->getLineDetails()->getIstransfer()) {
            return true;
        }

        return false;
    }
}
