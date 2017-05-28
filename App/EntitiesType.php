<?php

namespace AlanKent\GraphQL\App;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\NonNull;

/**
 * Object type for querying the avaialble entities (*RepositoryInterface service contracts).
 */
class EntitiesType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Entities',
            'description' => 'All queriable entities exposed via GraphQL.',
            'fields' => [

                'product' => [
                    'type' => new ProductType(),
                    'description' => 'Retrieve product for the specified SKU or id.',
                    'args' => [
                        // TODO: One recommendation was to always have one input arg with an InputType, not multiple args like is done here.
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
                            'description' => 'The store view id, or omitted for the default store view.',
                            //'defaultValue' => 0  // Default should be null? Is null same as zero?
                        ]
                    ],
                    'resolve' => function($val, $args, $context, $info) {
                        // Lazy load at runtime.
                        $sc = $context->getServiceContract(\Magento\Catalog\Api\ProductRepositoryInterface::class);

                        $storeId = isset($args['storeId']) ? $args['storeId'] : null;

                        if (isset($args['id']) && isset($args['sku'])) {
                            throw new \Exception('Specify either "sku" or "id", not both.');
                        }
                        if (isset($args['id'])) {
                            return $sc->getById($args['id'], false, $storeId);
                        }
                        if (isset($args['sku'])) {
                            return $sc->get($args['sku'], false, $storeId);
                        }
                        throw new \Exception('You must specify either "sku" or "id".');
                    }
                ],

                'category' => [
                    'type' => new CategoryType(),
                    'description' => 'Retrieve category for the specified id.',
                    'args' => [
                        'id' => [
                            'type' => new NonNull(Type::int()),
                            'description' => 'The id of the category to return.'
                        ],
                        //'storeId' => [
                            //'type' => Type::int(),
                            //'description' => 'The store view id, or omitted for the default store view.',
                            ////TODO: 'defaultValue' => 0  // Default should be null? Is null same as zero?
                        //]
                    ],
                    'resolve' => function($val, $args, $context, $info) {
                        // Lazy load at runtime.
                        $sc = $context->getServiceContract(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
                        //$storeId = isset($args['storeId']) ? $args['storeId'] : null;
                        return $sc->get($args['id']/*, false, $storeId*/);
                    }
                ],
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
