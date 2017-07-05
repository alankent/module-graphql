<?php

namespace AlanKent\GraphQL\App;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class ProductType extends ObjectType
{
    public function __construct()
    {
        // TODO: Later want to get this info from API calls, not hard coded like this.
        $auto = [
            'core_attributes' => [
                'id' => [ 'type' => Type::int() ],
                'sku' => [ 'type' => Type::string(), 'description' => 'Stock Keeping Unit (SKU).' ],
                'name' => [ 'type' => Type::string() ],
                'attributeSetId' => [ 'type' => Type::int() ],
                'price' => [ 'type' => Type::float() ],
                'status' => [ 'type' => Type::int() ],
                'visibility' => [ 'type' => Type::int() ],
                'typeId' => [ 'type' => Type::string() ],
                'createdAt' => [ 'type' => Type::string() ],
                'updatedAt' => [ 'type' => Type::string() ],
                'weight' => [ 'type' => Type::float() ],
            ],
            'extension_attributes' => [
                'material' => [ 'type' => Type::string() ],
            ],
            'custom_attributes' => [
                'cust_attr' => [ 'type' => Type::string(), 'description' => 'Demo custom attribute I added by hand' ],
            ]
        ];

        $fields = [];

        foreach ($auto['core_attributes'] as $name => $attr) {
            $fields[$name] = [
                'type' => $attr['type'],
                'description' => isset($attr['description']) ? $attr['description'] : "Core attribute '$name'. ",
                'resolve' => function($val, $args, $context, ResolveInfo $info) {
                    $getFn = 'get' . ucfirst($info->fieldName);
                    return $val->$getFn();
                }
            ];
        }

        foreach ($auto['extension_attributes'] as $name => $attr) {
            $fields[$name] = [
                'type' => $attr['type'],
                'description' => isset($attr['description']) ? $attr['description'] : "Extension attribute '$name'.",
                'resolve' => function($val, $args, $context, ResolveInfo $info) {
                    //return $val->getExtensionAttribute($info->fieldName);
                    $getFn = 'get' . ucfirst($info->fieldName);
                    return $val->$getFn();
                }
            ];
        }

        foreach ($auto['custom_attributes'] as $name => $attr) {
            $fields[$name] = [
                'type' => $attr['type'],
                'description' => isset($attr['description']) ? $attr['description'] : "Custom attribute '$name'.",
                'resolve' => function($val, $args, $context, ResolveInfo $info) {
                    //return $val->getCustomAttribute($info->fieldName);
                    $getFn = 'get' . ucfirst($info->fieldName);
                    return $val->$getFn();
                }
            ];
        }

        $config = [
            'name' => 'Product',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }
}
