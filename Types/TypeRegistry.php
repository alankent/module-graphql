<?php

namespace AlanKent\GraphQL\Types;

use AlanKent\GraphQL\App\EntityManager;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\OutputType;

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
    // Map of type names seen so far to GraphQL input types.
    /** @var InputType[] */
    private $inputTypes;

    // Map of type names seen so far to GraphQL types.
    /** @var OutputType[] */
    private $outputTypes;

    /** @var EntityManager */
    private $entityManager;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;

        // Built in types.
        $scalarTypes = [
            'String' => Type::string(),
            'Int' => Type::int(),
            'Float' => Type::float(),
            'ID' => Type::id(),
            'Boolean' => Type::boolean(),
        ];

        // Clones the arrays.
        $this->inputTypes = $scalarTypes;
        $this->outputTypes = $scalarTypes;
    }

    /**
     * Return the GraphQL input type for the specified entity/scalar name.
     * @param string $objectName
     * @return InputType
     * @throws \Exception
     */
    public function getInputType(string $objectName): InputType {
        if (isset($this->inputTypes[$objectName])) {
            return $this->inputTypes[$objectName];
        }
        throw new \Exception("Unknown type '$objectName'.");
    }

    /**
     * Return the GraphQL output type for the specified entity/scalar name.
     * @param string $objectName
     * @return OutputType
     * @throws \Exception
     */
    public function getOutputType(string $objectName): OutputType {
        if (isset($this->outputTypes[$objectName])) {
            return $this->outputTypes[$objectName];
        }
        throw new \Exception("Unknown type '$objectName'.");
    }

    /**
     * Convert a string such as "String" or "[Customer!]!" to the
     * corresponding GraphQL type. The entity manager returns types
     * as strings, which this function converts to the GraphQL form.
     * @param string $typeString The string encoding of the type.
     * @return OutputType The GraphQL 'type' construct.
     */
    public function makeOutputType(string $typeString): OutputType {

        // TODO: Should share the parsing code better with input & output functions.

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
        if (!isset($this->outputTypes[$typeString])) {
            $this->compileType($typeString);
        }
        $outputType = $this->outputTypes[$typeString];

        // Add all the nonNull and listOf wrappers as required.
        if ($isRequired) {
            $outputType = Type::nonNull($outputType);
        }
        if ($isArray) {
            $outputType = Type::listOf($outputType);
        }
        if ($isArrayRequired) {
            $outputType = Type::nonNull($outputType);
        }

        return $outputType;
    }

    /**
     * Convert a string such as "String" or "[Customer!]!" to the
     * corresponding GraphQL type. The entity manager returns types
     * as strings, which this function converts to the GraphQL form.
     * @param string $typeString The string encoding of the type.
     * @return InputType The GraphQL 'type' construct.
     */
    public function makeInputType(string $typeString): InputType {

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
        if (!isset($this->inputTypes[$typeString])) {
            $this->compileType($typeString);
        }
        $inputType = $this->inputTypes[$typeString];

        // Add all the nonNull and listOf wrappers as required.
        if ($isRequired) {
            $inputType = Type::nonNull($inputType);
        }
        if ($isArray) {
            $inputType = Type::listOf($inputType);
        }
        if ($isArrayRequired) {
            $inputType = Type::nonNull($inputType);
        }

        return $inputType;
    }

    /**
     * Converts the schema returned by the entity manager into config
     * required by the GraphQL type system. An "InputType" and "OutputType"
     * are created. These are close, but not identical.
     */
    private function compileType(string $entityName) {
        $schema = $this->entityManager->getEntitySchema($entityName);
        if ($schema == null) {
            throw new \Exception("Unknown type '$entityName'.");
        }

        $this->outputTypes[$entityName] = new ObjectType([
            'name' => $entityName,
            'description' => $schema['description'],
//            'resolve' => $schema['resolve'],
            'fields' => function() use($schema) {
                $fields = [];
                foreach ($schema['fields'] as $fn => $fv) {
                    $fields[$fn] = [
                        'type' => $this->makeOutputType(($fv['type'])),
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
        ]);

        // Skip over fields of type "ID"/"ID!", and fields with custom 'resolve' functions
        // as they are "computed" attributes (not store in database).
        $this->inputTypes[$entityName] = new InputObjectType([
            'name' => $entityName . 'Input',
            'description' => $schema['description'],
            'fields' => function() use($schema, $entityName) {
                $fields = [];
                foreach ($schema['fields'] as $fn => $fv) {
                    if (!isset($fv['resolve']) && $fv['type'] !== 'ID' && $fv['type'] !== 'ID!') {
                        $fields[$fn] = [
                            'type' => $this->makeInputType(($fv['type'])),
                            'description' => $fv['description'],
                        ];
                    }
                }
                if (count($fields) == 0) {
                    throw new \Exception("Type '$entityName' has no input fields.'");
                }
                return $fields;
            }
        ]);
    }
}
