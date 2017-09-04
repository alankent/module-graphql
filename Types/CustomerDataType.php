<?php

namespace AlanKent\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Example class
 */
class CustomerDataType extends ObjectType
{
    /**
     * Constructor.
     */
    public function __construct(\AlanKent\GraphQL\App\EntityManager $entityManager)
    {
        $config = [
            'name' => 'CustomerData',
            'description' => 'Information about customers',
            'fields' => function() {
                return [
                    'id' => [
                        'type' => Type::id(),
                        'description' =>'Customer id',
                        'resolve' => function($val, $args, $context, $info) {

                        }
                    ],
                    'firstName' => [
                        'type' => Type::string(),
                        'resolve' => function($val, $args, $context, $info) {

                        }
                    ],
                    'lastName' => [
                        'type' => Type::string(),
                        'resolve' => function($val, $args, $context, $info) {
                        }
                    ]
                ];
            },
        ];
        parent::__construct($config);
    }
}
