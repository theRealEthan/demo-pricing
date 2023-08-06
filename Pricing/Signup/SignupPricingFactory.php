<?php

declare(strict_types=1);
/**
 * @author RAM
 */

namespace Axxess\Model\Pricing\Signup;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\PricingFactoryInterface;
use Axxess\Model\Pricing\PricingInterface;
use Axxess\Model\Pricing\Signup\Categories\CloudHostingPricing;
use Axxess\Model\Pricing\Signup\Categories\CustomRealmPricing;
use Axxess\Model\Pricing\Signup\Categories\DSLLinePricing;
use Axxess\Model\Pricing\Signup\Categories\DSLStaticIp;
use Axxess\Model\Pricing\Signup\Categories\EmailPricing;
use Axxess\Model\Pricing\Signup\Categories\FibreLinePricing;
use Axxess\Model\Pricing\Signup\Categories\FixedLineCombo\DSLComboPricing;
use Axxess\Model\Pricing\Signup\Categories\FixedLineCombo\FibreComboPricing;
use Axxess\Model\Pricing\Signup\Categories\FixedLineDataPricing;
use Axxess\Model\Pricing\Signup\Categories\FixedWirelessComboPricing;
use Axxess\Model\Pricing\Signup\Categories\HardwarePricing;
use Axxess\Model\Pricing\Signup\Categories\HostingPricing;
use Axxess\Model\Pricing\Signup\Categories\MobileDataPricing;
use Axxess\Model\Pricing\Signup\Categories\OtherPricing;
use Axxess\Model\Pricing\Signup\Categories\RegistrationPricing;
use Axxess\Model\Pricing\Signup\Categories\SpamFilterPricing;
use Axxess\Model\Pricing\Signup\Categories\VoipPricing;
use Axxess\Plugins\Doctrine\EntityManager;
use DateTime;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;

/**
 * Class RenewalPricingFactory.
 */
class SignupPricingFactory implements PricingFactoryInterface
{
    // Hold the class instance.
    private static $instance = null;

    protected EntityManager $em;

    protected DateTime $date;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    // The object is created from within the class itself
    // only if the class has no instance.
    public static function getInstance(EntityManager $em)
    {
        if (null == self::$instance) {
            self::$instance = new SignupPricingFactory($em);
        }

        return self::$instance;
    }

    public function getPrice(AxServices $service, AxProducts $product): PricingCollection
    {
        return $this->getCategory($service, $product)->getProductPrice();
    }

    public function getCategory(AxServices $service, DateTime $date): PricingInterface
    {
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
            case ConstCategory::HARDWARE:
                return new HardwarePricing($this->em, $service, $date);
        }
    }
}
