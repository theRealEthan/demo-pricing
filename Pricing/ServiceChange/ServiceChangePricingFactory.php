<?php
/**
 * @author RAM
 */

namespace Axxess\Model\Pricing\ServiceChange;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Model\Pricing\ServiceChange\Categories\CloudHostingPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\CustomRealmPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\DSLLinePricing;
use Axxess\Model\Pricing\ServiceChange\Categories\DSLStaticIp;
use Axxess\Model\Pricing\ServiceChange\Categories\EmailPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\FibreLinePricing;
use Axxess\Model\Pricing\ServiceChange\Categories\FixedLineCombo\DSLComboPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\FixedLineCombo\FibreComboPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\FixedLineDataPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\FixedWirelessComboPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\HostingPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\MobileDataPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\OtherPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\RegistrationPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\SpamFilterPricing;
use Axxess\Model\Pricing\ServiceChange\Categories\VoipPricing;
use Axxess\Models\Discount\DiscountModel;
use Axxess\Plugins\Doctrine\EntityManager;
use Entity\Axxess1\AxServicechange;

class ServiceChangePricingFactory
{
    protected EntityManager $em;
    protected DiscountModel $discountModel;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public static function getCategory(EntityManager $em, AxServicechange $serviceChange)
    {
        switch ($serviceChange->getProductid()->getCategoryid()->getCategoryid()) {
            case ConstCategory::ADSL:
                return new FixedLineDataPricing($em, $serviceChange);
            case ConstCategory::LINES:
                return new DSLLinePricing($em, $serviceChange);
            case ConstCategory::BUNDLE:
                return new DSLComboPricing($em, $serviceChange);
            case ConstCategory::FIBRE:
                return new FibreLinePricing($em, $serviceChange);
            case ConstCategory::FIBRECOMBO:
                return new FibreComboPricing($em, $serviceChange);
            case ConstCategory::HOSTING:
                return new HostingPricing($em, $serviceChange);
            case ConstCategory::CLOUDHOSTING:
                return new CloudHostingPricing($em, $serviceChange);
            case ConstCategory::REGISTRATION:
                return new RegistrationPricing($em, $serviceChange);
            case ConstCategory::EMAIL:
                return new EmailPricing($em, $serviceChange);
            case ConstCategory::SPAMFILTER:
                return new SpamFilterPricing($em, $serviceChange);
            case ConstCategory::STATICIP:
                return new DSLStaticIp($em, $serviceChange);
            case ConstCategory::CUSTOMREALM:
                return new CustomRealmPricing($em, $serviceChange);
            case ConstCategory::VOIP:
                return new VoipPricing($em, $serviceChange);
            case ConstCategory::FIXEDWIRELESS_COMBO:
                return new FixedWirelessComboPricing($em, $serviceChange);
            case ConstCategory::MOBILEDATA:
                return new MobileDataPricing($em, $serviceChange);
            case ConstCategory::OTHER:
                return new OtherPricing($em, $serviceChange);
        }
    }
}
