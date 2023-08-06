<?php

namespace Axxess\Model\Pricing;

class ServiceProperties
{
    protected bool $canAddToInvoice = true;

    protected string $canAddToInvoiceReason = '';

    public function __construct(Pricing $pricing)
    {
        $this->canAddToInvoice = $pricing->canAddToInvoice();
        $this->canAddToInvoiceReason = $pricing->canAddToInvoiceReason();
    }

    public function canAddToInvoice(): bool
    {
        return $this->canAddToInvoice;
    }

    public function setCanAddToInvoice(bool $canAddToInvoice = true): void
    {
        $this->canAddToInvoice = $canAddToInvoice;
    }

    public function getCanAddToInvoiceReason(): string
    {
        return $this->canAddToInvoiceReason;
    }

    public function setCanAddToInvoiceReason(string $canAddToInvoiceReason = ''): void
    {
        $this->canAddToInvoiceReason = $canAddToInvoiceReason;
    }
}
