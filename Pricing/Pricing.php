<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing;

use Axxess\Model\ReturnTypes\Pricing\PricingResult;
use Axxess\Plugins\Doctrine\EntityManager;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServices;
use Money\Currencies\ISOCurrencies;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

abstract class Pricing implements PricingInterface
{
    protected EntityManager $em;

    protected AxServices $service;

    protected AxProducts $product;

    protected \DateTime $date;

    public function __construct(EntityManager $em, AxServices $service, \DateTime $date)
    {
        $this->em = $em;
        $this->service = $service;
        $this->date = $date;
    }

    public function getBaseProductPrice(): Money
    {
        $parser = new DecimalMoneyParser(new ISOCurrencies());

        return $parser->parse($this->service->getProductid()->getPrice(), 'ZAR');
    }

    public function getPricing(): PricingResult
    {
        $basePrice = $this->getBaseProductPrice();
        $discountClass = $this->getDiscountClass();
        $discountResult = $discountClass->applyDiscounts($discountClass->getDiscounts(), $basePrice);

        $pricingResult = new PricingResult(true);
        $pricingResult->setBasePrice($basePrice);
        $pricingResult->setDiscounts($discountResult);
        $pricingResult->setFinalPrice($discountResult->getFinalPrice());

        return $pricingResult;
    }

    public function canAddToInvoice(): bool
    {
        return true;
    }

    public function canAddToInvoiceReason(): string
    {
        return '';
    }

    /**
     * RAM:
     * Pastel Accounting Package wants us to formulate the vat amount from priceInclusive as done here.
     * Remember that done in different ways while mathematically equivalent will result in different floating pt
     * arithmetic on a single system, never mind different systems i.e. 8, 16, 32, 64 bit systems.
     */
    public function calculateVatAmount(Money $priceInclVat): Money
    {
        // RAM: Solve the simultaneous equations to get what Pastel Accounting package wants us to use.
        //$vatAmount = $priceIncVat - $priceExcVat;
        //$vatAmount = $priceExcVat * (VAT_PERC / 100);

        // RAM: This can be formulated to be equivalent to
        $vatAmount = $priceInclVat->multiply((VAT_PERC / (VAT_PERC + 100)));

        return $vatAmount;
    }

    public function getProrataAmount(AxProducts $product, Money $amount, \DateTime $date = null): Money
    {
        //TODO: TEST THE CRAP OUTTA THIS. CEIL AND PRORATA STEPS
        $totalDays = cal_days_in_month(CAL_GREGORIAN, (int) date('m'), (int) date('Y'));
        $currentDay = (int) date('j');
        if (null !== $date) {
            $day = (int) $date->format('d');
            $month = $date->format('m');
            $year = $date->format('Y');
            if ($day > 0 && !empty($month) && !empty($year)) {
                $totalDays = cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);
                $currentDay = $day;
            }
        }

        // Apply if ProRata steps are set
        // RAM: Could be int|null, perhaps other null values too
        if (!empty($product->getProratasteps())) {
            // Price/Vat per step
            $pricePerStep = $amount->divide($product->getProratasteps());

            // How many days per step
            $daysPerStep = $totalDays / $product->getProratasteps();
            // Price/Vat price for the selected step
            $price = ceil(
                $pricePerStep * (0 == ceil($currentDay / $daysPerStep) ? 1 : ceil($currentDay / $daysPerStep))
            );
            $price = round($price);
        } else {
            $currentDay--;
            // Price per day
            $pricePerDay = $amount->divide($totalDays);

            $price = $amount->subtract($pricePerDay->multiply($currentDay));
        }

        return $price;
    }
}
