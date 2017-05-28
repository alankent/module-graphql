<?php

namespace AlanKent\GraphQL\App;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class QueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Query',
            'description' => 'Query type for all supported queries.',
            'fields' => [
                'hello' => [
                    'type' => Type::string(),
                    'description' => 'Returns a simple greeting (Hellow World!) message.'
                ],
                //'me' => new UserType()
                /*
                'catalog' => [
                    'type' => new MagentoCatalogType(),
                    'description' => 'Magento Catalog module type',
                    'resolve' => function() { return 'XYZ'; }
                ],
                */
                'entities' => [
                    'type' => new EntitiesType(),
                    'description' => 'All entities exposed for querying.',
                    'resolve' => function() { return 'XYZ'; }
                ],
            ],
            'resolveField' => function($val, $args, $context, ResolveInfo $info) {
                return $this->{$info->fieldName}($val, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    public function hello()
    {
        return 'Your graphql-php endpoint is ready! Use GraphiQL to browse API';
    }

    public function me()
    {
        $me = new UserType();
        $me->id = '1';
        $me->firstName = 'Alan';
        $me->lastName = 'Kent';
        return $me;
    }
}
