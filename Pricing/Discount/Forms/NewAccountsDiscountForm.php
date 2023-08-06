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
use Phalcon\Forms\Element\Radio;
use Phalcon\Forms\Element\Text;

class NewAccountsDiscountForm extends Form
{
    protected EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $customisableOptions = ['namespace' => 'NewAccountsDiscountForm'];

        parent::__construct(null, [], $customisableOptions);

        $this->em = $em;

        $rPercent = new Numeric('rPercent');
        $rPercent->setLabel('Discount Percentage:');
        $rPercent->setAttribute('class', 'form-control');
        $rPercent->addValidators([
            new PresenceOf([
                'message' => 'Discount Percentage is required.',
            ]),
        ]);
        $this->add($rPercent);

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

        $isNewSignUp = new Radio('isNewSignUp', [
            'name' => 'isNewSignUp',
            'value' => 'isNewSignUp',
            'onclick' => 'setIsNewSignUp',
            'checked' => 'true',
        ]);
        $isNewSignUp->setLabel('isTopUp');
        $this->add($isNewSignUp);

        $isTopUp = new Radio('isTopUp', [
            'name' => 'isTopUp',
            'value' => 'isTopUp',
            'onclick' => 'setIsTopUp',
            'checked' => 'true',
        ]);
        $isTopUp->setLabel('isTopUp');
        $this->add($isTopUp);

        $isUpgrade = new Radio('isUpgrade', [
            'name' => 'isUpgrade',
            'value' => 'isUpgrade',
            'onclick' => 'setIsUpgrade',
            'checked' => 'true',
        ]);
        $isUpgrade->setLabel('isUpgrade');
        $this->add($isUpgrade);

        $isRenewal = new Radio('isRenewal', [
            'name' => 'isRenewal',
            'value' => 'isRenewal',
            'onclick' => 'setIsRenewal',
            'checked' => 'true',
        ]);
        $isRenewal->setLabel('isRenewal');
        $this->add($isRenewal);

        $isTopUp = new Check('isTopUp', ['value' => DiscountTypes::TOPUP]);
        $isTopUp->setLabel('isTopUp');
        $this->add($isTopUp);

        $isUpgrade = new Check('isUpgrade', ['value' => DiscountTypes::SERVICE_CHANGE]);
        $isUpgrade->setLabel('isUpgrade');
        $this->add($isUpgrade);

        $isRenewal = new Check('isRenewal', ['value' => DiscountTypes::RENEWAL]);
        $isRenewal->setLabel('isRenewal');
        $this->add($isRenewal);

        $isNewSignUp = new Check('isNewSignUp', ['value' => DiscountTypes::SIGNUP]);
        $isNewSignUp->setLabel('isNewSignUp');
        $this->add($isNewSignUp);
    }
}
