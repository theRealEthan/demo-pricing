<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\ServiceChange\Categories\FixedLineCombo;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Model\Pricing\Collections\DiscountCollection;
use Axxess\Model\Pricing\Collections\PricingCollection;
use Axxess\Model\Pricing\Response\PriceResponse;
use Axxess\Model\Pricing\ServiceChange\ServiceChangeDiscount;
use Axxess\Model\Pricing\ServiceChange\ServiceChangePricing;
use Axxess\Model\ReturnTypes\Pricing\DiscountResult;
use Entity\Axxess1\AxProducts;
use Money\Money;

/**
 * Fixed Line Combos are Service Changed as such.
 * The Data Portion of the combo is billed on change.
 *
 * If the line in the combo is also changing, then its billed separately.
 *
 * So basically, Combo ServiceChange will only affect data billing.
 *
 * Class FixedLineComboPricing
 */
abstract class FixedLineComboPricing extends ServiceChangePricing implements FixedLineComboPricingInterface
{
    public function getProductPrice(): PricingCollection
    {
        try {
            $combo = new PriceResponse();
            $combo->setService($this->servicechange->getServiceid());
            $combo->setProduct($this->servicechange->getProductid());
            $combo->setDiscount($this->getDiscounts());
            $combo->setVat(Money::ZAR(0));
            $combo->setAmount(Money::ZAR(0));

            $line = new PriceResponse();
            $line->setService($this->getLineService());
            $line->setProduct($this->getChangeDataProduct());
            $line->setDiscount(new DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new DiscountCollection()));
            $line->setVat($this->calculateVatAmount($this->getLinePrice()));
            $line->setAmount($this->getLinePrice());

            $data = new PriceResponse();
            $data->setService($this->getDataService());
            $data->setProduct($this->getChangeDataProduct());
            $data->setDiscount(new DiscountResult(true, 'success', Money::ZAR(0), Money::ZAR(0), new DiscountCollection()));
            $data->setVat($this->calculateVatAmount($this->getDataPrice()));
            $data->setAmount($this->getDataPrice());

            return new PricingCollection($combo, $line, $data);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getCurrentServiceDiscounts(): DiscountResult
    {
        $discount = new ServiceChangeDiscount($this->em, $this->servicechange);

        return $discount->applyDiscounts($discount->getCurrentDiscounts(), self::getBaseProductPrice($this->servicechange->getFromproductid()));
    }

    public function getChangeServiceDiscounts(): DiscountResult
    {
        $discount = new ServiceChangeDiscount($this->em, $this->servicechange);

        return $discount->applyDiscounts($discount->getChangeDiscounts(), self::getBaseProductPrice($this->servicechange->getProductid()));
    }

    protected function calculateServicePrice(): Money
    {
        try {
            return $this->getLinePrice()->add($this->getDataPrice());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getCurrentDataProduct(): AxProducts
    {
        return $this->getDataProductFromComboProduct($this->servicechange->getFromproductid());
    }

    public function getChangeDataProduct(): AxProducts
    {
        return $this->getDataProductFromComboProduct($this->servicechange->getProductid());
    }

    public function getDataProductFromComboProduct(AxProducts $product): AxProducts
    {
        $productId = $this->em->getConnection()->fetchOne('SELECT 
                                                                  p.ProductId 
                                                                FROM
                                                                  ax_productbundle pb 
                                                                  INNER JOIN ax_products p
                                                                  ON pb.ProductId = p.ProductId
                                                                WHERE pb.ProductBundleId = :productId 
                                                                AND p.CategoryId = :categoryData',
            ['productId' => $product->getProductid(), 'categoryData' => ConstCategory::ADSL],
            ['productId' => \PDO::PARAM_INT, 'categoryData' => \PDO::PARAM_INT]);

        if (false !== $productId) {
            return $this->em->getReference('Entity\\Axxess1\\AxProducts', $productId);
        }
    }

    public function getCurrentLineProduct(): AxProducts
    {
        return $this->getLineProductFromComboProduct($this->servicechange->getFromproductid());
    }

    public function getChangeLineProduct(): AxProducts
    {
        return $this->getLineProductFromComboProduct($this->servicechange->getProductid());
    }

    public function getFinalPrice(): Money
    {
        return $this->calculateServicePrice();
    }

    /**
     * Data In a combo is billed as Follows;.
     *
     * Combo Price - Line Price = Data Price;
     *
     * Ref: Control Invoice Line 1133
     */
    public function getDataPrice(): Money
    {
        try {
            $currentComboPrice = $this->getCurrentServiceDiscounts()->getFinalPrice();
            $currentLinePrice = self::getBaseProductPrice($this->getCurrentLineProduct());

            $changeComboPrice = $this->getChangeServiceDiscounts()->getFinalPrice();
            $changeLinePrice = self::getBaseProductPrice($this->getChangeLineProduct());

            if ($currentLinePrice->greaterThan($currentComboPrice)) {
                $currentDataPrice = Money::ZAR(0);
            } else {
                $currentDataPrice = $currentComboPrice->subtract($currentLinePrice);
            }

            if ($changeLinePrice->greaterThan($changeComboPrice)) {
                $changeDataPrice = Money::ZAR(0);
            } else {
                $changeDataPrice = $changeComboPrice->subtract($changeLinePrice);
            }

            return $changeDataPrice->subtract($currentDataPrice);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getParentPrice(): Money
    {
        try {
            return $this->calculateServicePrice();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Set IsBundle = 1 because its a service within a combo.
     * Ref: ControlInvoice Line 1089.
     *
     * Check if line price is > Combo Price. (Our Combo Pricing doesnt scale)
     * If it is, Then the line price = Combo Price, and Data will be billed at R0;
     * Ref: Control Invoice Line 1164
     */
    public function getLinePrice(): Money
    {
        try {
            $currentComboPrice = $this->getCurrentServiceDiscounts()->getFinalPrice();
            $currentLinePrice = self::getBaseProductPrice($this->getCurrentLineProduct());

            $changeComboPrice = $this->getChangeServiceDiscounts()->getFinalPrice();
            $changeLinePrice = self::getBaseProductPrice($this->getChangeLineProduct());

            if ($currentLinePrice->greaterThan($currentComboPrice)) {
                $currentLinePrice = $currentComboPrice;
            }

            if ($changeLinePrice->greaterThan($changeComboPrice)) {
                $changeLinePrice = $changeComboPrice;
            }

            return $changeLinePrice->subtract($currentLinePrice);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getBundledServices(): array
    {
        try {
            $sql = 'SELECT 
                      * 
                    FROM
                      ax_services s 
                    WHERE s.BundleServiceId = :serviceId ';

            $params = ['serviceId' => $this->servicechange->getServiceid()->getServiceid()];
            $types = ['serviceId' => \PDO::PARAM_INT];

            $services = $this->em->getConnection()->fetchAllAssociative($sql, $params, $types);

            $result = [];
            foreach ($services as $service) {
                /*
                 *@var AxServices
                 */
                $result[] = $this->em->getReference('Entity\\Axxess1\\AxServices', (int) $service['ServiceId']);
            }

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function canBill(): bool
    {
        return true;
    }
}
