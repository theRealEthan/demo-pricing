<?php
/**
 * @author RAM
 */

namespace Axxess\Model\Pricing\Topup;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Model\Pricing\Topup\Categories\FixedLineDataPricing;
use Axxess\Model\Pricing\Topup\Categories\FixedWirelessDataPricing;
use Axxess\Model\Pricing\Topup\Categories\MobileDataPricing;
use Axxess\Model\Pricing\Topup\Categories\SmsPricing;
use Axxess\Model\Pricing\Topup\Categories\VoipPricing;
use Axxess\Models\Discount\DiscountModel;
use Axxess\Plugins\Doctrine\EntityManager;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;

class TopupPricingFactory
{
    protected EntityManager $em;
    protected DiscountModel $discountModel;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public static function getCategory(EntityManager $em, AxServices $service, AxProducts $product)
    {
        $discountModel = new DiscountModel($em);

        switch ($service->getProductid()->getCategoryid()->getCategoryid()) {
            case ConstCategory::ADSL:
                return new FixedLineDataPricing($em, $service, $product, $discountModel);
            case ConstCategory::SMS:
                return new SmsPricing($em, $service, $product, $discountModel);
            case ConstCategory::MOBILEDATA:
                return new MobileDataPricing($em, $service, $product, $discountModel);
            case ConstCategory::VOIP:
                return new VoipPricing($em, $service, $product, $discountModel);
            case ConstCategory::FIXEDWIRELESS_DATA:
                return new FixedWirelessDataPricing($em, $service, $product, $discountModel);
        }
    }
}
