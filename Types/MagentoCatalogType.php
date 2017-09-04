<?php

namespace AlanKent\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\NonNull;

/**
 * Object type for querying the Magento_Catalog service contracts.
 */
class MagentoCatalogType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Magento_Catalog',
            'description' => 'Mutating service contracts.',
            'fields' => [
                'product' => [
                    'type' => new ProductType(),
                    'description' => 'Retrieve product for the specified SKU.',
                    'args' => [
                        // One recommendation is to always have one input arg with an InputType.
                        'sku' => [
                            'type' => Type::string(),
                            'description' => 'The SKU of the product to search for. (Supply SKU or id.)'
                        ],
                        'id' => [
                            'type' => Type::int(),
                            'description' => 'The id of the product to search for. (Supply SKU or id.)'
                        ],
                        'storeId' => [
                            'type' => Type::int(),
                            'description' => 'The store view id, or zero for the default store view.',
                            'defaultValue' => 0
                        ]
                    ],
                    'resolve' => function($val, $args, $context, $info) {
                        $sc = $context->getServiceContract('Magento\Catalog\Api\ProductRepositoryInterface');
                        if (isset($args['id']) && isset($args['sku'])) {
                            throw new \Exception('Specify either "sku" or "id", not both.');
                        }
                        if (isset($args['id'])) {
                            return new ProductValue($sc->getById($args['id'], false, $args['storeId']));
                        }
                        if (isset($args['sku'])) {
                            return new ProductValue($sc->get($args['sku'], false, $args['storeId']));
                        }
                        throw new \Exception('You must specify either "sku" or "id".');
                    }
                ]
            ],
            //'resolveField' => function($val, $args, $context, ResolveInfo $info) {
                //return self::{$info->fieldName}($val, $args, $context, $info);
            //}
        ];
        parent::__construct($config);
    }

    //public static function product($val, $args, $context, $info)
    //{
    //}
}
