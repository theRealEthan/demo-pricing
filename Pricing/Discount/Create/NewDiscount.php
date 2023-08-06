<?php

declare(strict_types=1);

namespace Axxess\Model\Pricing\Discount\Create;

use Axxess\Plugins\Doctrine\EntityManager;

abstract class NewDiscount
{
    protected EntityManager $em;
    protected ?\DateTime $startDate;
    protected ?\DateTime $endDate;
    protected ?int $percentage;
    protected ?int $price;
    protected array $discountType;
    protected int $adjustmentTypeId;
    protected string $reason;

    public function __construct(EntityManager $em, array $discountType, string $reason, int $adjustmentTypeId, int $operatorId, ?\DateTime $startDate, ?\DateTime $endDate, ?int $percentage, ?int $price)
    {
        $this->em = $em;

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reason = $reason;
        $this->percentage = $percentage;
        $this->price = $price;
        $this->discountType = $discountType;
        $this->adjustmentTypeId = $adjustmentTypeId;
        $this->operatorId = $operatorId;
    }
}
