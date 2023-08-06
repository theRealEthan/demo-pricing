<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount\Forms;

use Axxess\Model\Pricing\Discount\DiscountTypes;
use Axxess\Plugins\Doctrine\EntityManager;
use Axxess\Plugins\Phalcon\Form\Elements\DateTime;
use Axxess\Plugins\Phalcon\Form\Form;
use Axxess\Plugins\Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\Numeric;
use Phalcon\Forms\Element\Text;

class NewServiceDiscountForm extends Form
{
    protected EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $customisableOptions = ['namespace' => 'NewServiceDiscountForm'];

        parent::__construct(null, [], $customisableOptions);
        $this->em = $em;

        $rPercent = new Numeric('rPercent', ['min' => '0', 'max' => '100']);
        $rPercent->setLabel('Discount Percentage:');
        $rPercent->setAttribute('class', 'form-control');
        $this->add($rPercent);

        $price = new Numeric('price', ['min' => '0']);
        $price->setLabel('Discount Price:');
        $price->setAttribute('class', 'form-control');
        $this->add($price);

        $reason = new Text('reason');
        $reason->setLabel('Discount Reason:');
        $reason->setAttribute('class', 'form-control');
        $reason->addValidators([
            new PresenceOf([
                'message' => 'Discount Reason is required.',
            ]),
        ]);
        $this->add($reason);

        $startDate = new DateTime('startDate');
        $startDate->setLabel('Start Date:');
        $startDate->setAttribute('class', 'form-control');
        $startDate->addValidators([
            new PresenceOf([
                'message' => 'A Start Date is required.',
            ]),
        ]);
        $this->add($startDate);

        $endDate = new DateTime('endDate');
        $endDate->setLabel('End Date:');
        $endDate->setAttribute('class', 'form-control');
        $this->add($endDate);

        $isTopUp = new Check('isTopUp', ['value' => DiscountTypes::TOPUP]);
        $isTopUp->setLabel('Top Up Discount');
        $this->add($isTopUp);

        $isUpgrade = new Check('isUpgrade', ['value' => DiscountTypes::SERVICE_CHANGE]);
        $isUpgrade->setLabel('Upgrade Discount');
        $this->add($isUpgrade);

        $isRenewal = new Check('isRenewal', ['value' => DiscountTypes::RENEWAL]);
        $isRenewal->setLabel('Renewal Discount');
        $this->add($isRenewal);
    }
}
