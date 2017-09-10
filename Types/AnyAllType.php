<?php

namespace AlanKent\GraphQL\Types;

use AlanKent\GraphQL\Persistence\Entity;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputType;

class AnyAllType extends EnumType
{
    /** @var AnyAllType */
    private static $type;

    /**
     * Constructor.
     */
    public function __construct() {
        $config = [
            'name' => 'AnyAllEnum',
            'description' => 'Used for Any/All (OR or AND, defaults to AND) for filters.',
            'values' => [
                [ 'name' => 'Any' ],
                [ 'name' => 'All' ],
            ],
        ];

        parent::__construct($config);
    }

    /**
     * Return the Any/All enumerated type.
     */
    public static function singleton(): AnyAllType
    {
        if (self::$type == null) {
            self::$type = new AnyAllType();
        }
        return self::$type;
    }
}
