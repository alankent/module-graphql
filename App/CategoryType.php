<?php

namespace AlanKent\GraphQL\App;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;

class CategoryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Category',
            'fields' => [
                'id' => Type::int(),
                'parentId' => Type::int(),
                'name' => Type::string(),
                'isActive' => Type::boolean(),
                'position' => Type::int(),
                'level' => Type::int(),
                'children' => Type::string(),
                'createdAt' => Type::string(),
                'updatedAt' => Type::string(),
                'path' => Type::string(),
                'availableSortBy' => new ListOfType(Type::string()),
                'includeInMenu' => Type::boolean(),
            ],
            'resolveField' => function($val, $args, $context, ResolveInfo $info) {
                $getFn = 'get' . ucfirst($info->fieldName);
                return $val->$getFn();
            }
        ];
        parent::__construct($config);
    }
}
