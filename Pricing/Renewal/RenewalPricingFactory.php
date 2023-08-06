<?php

declare(strict_types=1);
/**
 * @author RAM
 */

namespace Axxess\Model\Pricing\Renewal;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Model\Pricing\PricingInterface;
use Axxess\Model\Pricing\Renewal\Categories\CloudHostingPricing;
use Axxess\Model\Pricing\Renewal\Categories\CustomRealmPricing;
use Axxess\Model\Pricing\Renewal\Categories\DSLLinePricing;
use Axxess\Model\Pricing\Renewal\Categories\DSLStaticIp;
use Axxess\Model\Pricing\Renewal\Categories\EmailPricing;
use Axxess\Model\Pricing\Renewal\Categories\FibreLinePricing;
use Axxess\Model\Pricing\Renewal\Categories\FixedLineCombo\DSLComboPricing;
use Axxess\Model\Pricing\Renewal\Categories\FixedLineCombo\FibreComboPricing;
use Axxess\Model\Pricing\Renewal\Categories\FixedLineDataPricing;
use Axxess\Model\Pricing\Renewal\Categories\FixedWirelessComboPricing;
use Axxess\Model\Pricing\Renewal\Categories\HostingPricing;
use Axxess\Model\Pricing\Renewal\Categories\MobileDataPricing;
use Axxess\Model\Pricing\Renewal\Categories\OtherPricing;
use Axxess\Model\Pricing\Renewal\Categories\RegistrationPricing;
use Axxess\Model\Pricing\Renewal\Categories\SpamFilterPricing;
use Axxess\Model\Pricing\Renewal\Categories\VoipPricing;
use Axxess\Plugins\Doctrine\EntityManager;
use Entity\Axxess1\AxServices;

/**
 * Class RenewalPricingFactory.
 */
class RenewalPricingFactory
{
    protected EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getCategory(AxServices $service): PricingInterface
    {
        $date = new \DateTime('now');

        switch ($service->getProductid()->getCategoryid()->getCategoryid()) {
            case ConstCategory::ADSL:
                return new FixedLineDataPricing($this->em, $service, $date);
            case ConstCategory::LINES:
                return new DSLLinePricing($this->em, $service, $date);
            case ConstCategory::BUNDLE:
                return new DSLComboPricing($this->em, $service, $date);
            case ConstCategory::FIBRE:
                return new FibreLinePricing($this->em, $service, $date);
            case ConstCategory::FIBRECOMBO:
                return new FibreComboPricing($this->em, $service, $date);
            case ConstCategory::HOSTING:
                return new HostingPricing($this->em, $service, $date);
            case ConstCategory::CLOUDHOSTING:
                return new CloudHostingPricing($this->em, $service, $date);
            case ConstCategory::REGISTRATION:
                return new RegistrationPricing($this->em, $service, $date);
            case ConstCategory::EMAIL:
                return new EmailPricing($this->em, $service, $date);
            case ConstCategory::SPAMFILTER:
                return new SpamFilterPricing($this->em, $service, $date);
            case ConstCategory::STATICIP:
                return new DSLStaticIp($this->em, $service, $date);
            case ConstCategory::CUSTOMREALM:
                return new CustomRealmPricing($this->em, $service, $date);
            case ConstCategory::VOIP:
                return new VoipPricing($this->em, $service, $date);
            case ConstCategory::FIXEDWIRELESS_COMBO:
                return new FixedWirelessComboPricing($this->em, $service, $date);
            case ConstCategory::MOBILEDATA:
                return new MobileDataPricing($this->em, $service, $date);
            case ConstCategory::OTHER:
                return new OtherPricing($this->em, $service, $date);
        }
    }
}
