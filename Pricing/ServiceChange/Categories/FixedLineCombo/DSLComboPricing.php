<?php

namespace Axxess\Model\Pricing\ServiceChange\Categories\FixedLineCombo;

use Axxess\Classes\Helper\Constants\ConstCategory;
use Entity\Axxess1\AxProducts;

class DSLComboPricing extends FixedLineComboPricing
{
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
            ['productId' => $product->getProductid(), 'categoryData' => ConstCategory::LINES],
            ['productId' => \PDO::PARAM_INT, 'categoryData' => \PDO::PARAM_INT]);

        if (false !== $productId) {
            return $this->em->getReference('Entity\\Axxess1\\AxProducts', $productId);
        }
    }
}
