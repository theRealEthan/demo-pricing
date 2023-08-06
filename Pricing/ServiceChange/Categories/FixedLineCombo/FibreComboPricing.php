<?php

namespace Axxess\Model\Pricing\ServiceChange\Categories\FixedLineCombo;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Axxess\Classes\Helper\Constants\LineStatus;
use Entity\Axxess1\AxProducts;
use Entity\Axxess1\AxServiceFibre;
use Entity\Axxess1\AxServices;

/**
 * Class FibreComboPricing.
 */
class FibreComboPricing extends FixedLineComboPricing
{
    public function getLineService(): AxServices
    {
        try {
            /**
             * @var $service AxServices
             */
            foreach ($this->getBundledServices() as $service) {
                if (ConstCategory::FIBRE === $service->getProductid()->getCategoryid()->getCategoryid()) {
                    return $service;
                }
            }

            throw new \Exception('Could not find line Service for Combo: '.$this->service->getServiceid());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getDataService(): AxServices
    {
        try {
            /**
             * @var $service AxServices
             */
            foreach ($this->getBundledServices() as $service) {
                if (ConstCategory::ADSL === $service->getProductid()->getCategoryid()->getCategoryid()) {
                    return $service;
                }
            }

            throw new \Exception('Could not find data Service for Combo: '.$this->service->getServiceid());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getLineDetails(): AxServiceFibre
    {
        try {
            /*
             * @var \Entity\Axxess1\AxServiceFibre
             */
            return $this->em->getReference('Entity\\Axxess1\\AxServiceFibre', $this->getLineService()->getServiceid());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function isLineActive(): bool
    {
        try {
            if (LineStatus::ACTIVATED === $this->getLineDetails()->getLineStatus()->getLinestatusid()) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function isLineTransfer(): bool
    {
        try {
            if ($this->getLineDetails()->getIsTransfer()) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getLineProductFromComboProduct(AxProducts $product): AxProducts
    {
        $productId = $this->em->getConnection()->fetchOne('SELECT 
                                                                  p.ProductId 
                                                                FROM
                                                                  ax_productbundle pb 
                                                                  INNER JOIN ax_products p
                                                                  ON pb.ProductId = p.ProductId
                                                                WHERE pb.ProductBundleId = :productId 
                                                                AND p.CategoryId = :categoryData',
            ['productId' => $product->getProductid(), 'categoryData' => ConstCategory::FIBRE],
            ['productId' => \PDO::PARAM_INT, 'categoryData' => \PDO::PARAM_INT]);

        if (false !== $productId) {
            return $this->em->getReference('Entity\\Axxess1\\AxProducts', $productId);
        }
    }
}
