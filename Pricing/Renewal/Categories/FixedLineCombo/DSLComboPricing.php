<?php

namespace Axxess\Model\Pricing\Renewal\Categories\FixedLineCombo;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Classes\Helper\Constants\LineStatus;
use Entity\Axxess1\AxServicelines;
use Entity\Axxess1\AxServices;
use Exception;

class DSLComboPricing extends FixedLineComboPricing
{
    /**
     * @throws Exception
     */
    public function getLineService(): AxServices
    {
        /**
         * @var $service AxServices
         */
        foreach ($this->getBundleServices() as $service) {
            if (ConstCategory::LINES === $service->getProductid()->getCategoryid()->getCategoryid()) {
                return $service;
            }
        }

        throw new Exception('Could not find line Service for Combo: '.$this->service->getServiceid());
    }

    /**
     * @throws Exception
     */
    public function getLineDetails(): AxServicelines
    {
        return $this->em->getReference('Entity\\Axxess1\\AxServicelines', $this->getLineService()->getServiceid());
    }

    /**
     * @throws Exception
     */
    public function isLineActive(): bool
    {
        if (LineStatus::ACTIVATED === $this->getLineDetails()->getLinestatusid()->getLinestatusid()) {
            return true;
        }

        return false;
    }
}
