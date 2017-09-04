<?php

namespace AlanKent\GraphQL\Types;

use AlanKent\GraphQL\App\EntityManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Converts the entity manager format for type strings into GraphQL objects. For example,
 * the entity manager may return "[Customer!]!" which would convert into nonNull(listOf(nonNull(object)))
 * where the object type is the Customer type. This class guarantees to only create object types once,
 * sharing them between callers. This is complicated by the fact that type A can have a field returning
 * type B, and type B can have a field returning type A (cyclic dependency). A closure is used to defer
 * working out the field types to as late as possible, so all the classes can be registered first.
 */
class TypeRegistry
{
    // Map of type names seen so far to GraphQL types.
    private $types;

    /** @var EntityManager */
    private $entityManager;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;

        // Built in types.
        $this->types = [
            'String' => Type::string(),
            'Int' => Type::int(),
            'Float' => Type::float(),
            'ID' => Type::id(),
            'Boolean' => Type::boolean(),
        ];
    }

    /**
     * Convert a string such as "String" or "[Customer!]!" to the
     * corresponding GraphQL type. The entity manager returns types
     * as strings, which this function converts to the GraphQL form.
     * @param string $typeString The string encoding of the type.
     * @return Type The GraphQL 'type' construct.
     */
    public function makeType(string $typeString): Type {

        // Trim training '!' if present.
        $isRequired = false;
        if (substr($typeString, -1) === '!') {
            $isRequired = true;
            $typeString = substr($typeString, 0, -1);
        }

        // Remove surrounding '[' and ']' if an array.
        $isArray = false;
        $isArrayRequired = false;
        if (substr($typeString, 0, 1) === '[') {
            $isArray = true;
            $isArrayRequired = $isRequired;
            $isRequired = false;
            $typeString = substr($typeString, 1, -1);

            // Trim nested '!' inside array.
            if (substr($typeString, -1) === '!') {
                $typeString = substr($typeString, 0, -1);
                $isRequired = true;
            }
        }

        // We are left with the type name. Look up cache, otherwise create a new type.
        if (isset($this->types[$typeString])) {
            $type = $this->types[$typeString];
        } else {
            $entityName = $typeString;
            $schema = $this->entityManager->getEntitySchema($entityName);
            if ($schema == null) {
                throw new \Exception("Unknown type '$entityName'.");
            }
            $type = new ObjectType($this->convertEntitySchemaToConfig($entityName, $schema));
            $this->types[$typeString] = $type;
        }

        // Add all the nonNull and listOf wrappers as required.
        if ($isRequired) {
            $type = Type::nonNull($type);
        }
        if ($isArray) {
            $type = Type::listOf($type);
        }
        if ($isArrayRequired) {
            $type = Type::nonNull($type);
        }

        return $type;
    }

    /**
     * Converts the schema returned by the entity manager into config
     * required by the GraphQL type system. These are close, but not
     * identical.
     */
    private function convertEntitySchemaToConfig($entityName, $schema) {
        $config = [
            'name' => $entityName,
            'description' => $schema['description'],
//            'resolve' => $schema['resolve'],
            'fields' => function() use($schema) {
                $fields = [];
                foreach ($schema['fields'] as $fn => $fv) {
                    $fields[$fn] = [
                        'type' => $this->makeType(($fv['type'])),
                        'description' => $fv['description'],
                        'resolve' => isset($fv['resolve'])
                            ? $resolveFunc = $fv['resolve']
                            : function($val, $args, $context, $info) use ($fn) {
                                /** @var \AlanKent\GraphQL\App\Entity $val */
                                return $val->getAttribute($fn);
                            },
                    ];
                }
                return $fields;
            }
        ];
        return $config;
    }
}
