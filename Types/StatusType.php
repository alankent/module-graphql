<?php

namespace AlanKent\GraphQL\Types;

use AlanKent\GraphQL\Persistence\Entity;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputType;

class StatusType extends ObjectType
{
    /** @var StatusType */
    private static $type;

    /**
     * Constructor.
     */
    public function __construct() {
        $config = [
            'name' => 'Status',
            'description' => 'Return status of mutation method.',
            'fields' => [
                'success' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'description' => 'True on success, false on error.',
                    'resolve' => function($val, $args, $context, ResolveInfo $info) {
                        /** @var StatusValue $val */
                        return $val->getSuccess();
                    }
                ],
                'message' => [
                    'type' => Type::string(),
                    'description' => 'Optional message to go along with success/failure.',
                    'resolve' => function($val, $args, $context, ResolveInfo $info) {
                        /** @var StatusValue $val */
                        return $val->getMessage();
                    }
                ],
            ],
        ];

        parent::__construct($config);
    }

    /**
     * @return StatusType
     */
    public static function singleton() {
        if (self::$type == null) {
            self::$type = new StatusType();
        }
        return self::$type;
    }
}
