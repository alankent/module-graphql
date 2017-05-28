<?php

namespace AlanKent\GraphQL\App;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class ProductValue
{
    /** @var Magento\Catalog\Api\Data\ProductInterface */
    private $product;

    public function __construct($product)
    {
        $this->product = $product;
    }

    public function sku($args, $context, $type, $info) {
        return $this->product->getSku();
    }

    public function name($args, $context, $type, $info) {
        return $this->product->getName();
    }
}
