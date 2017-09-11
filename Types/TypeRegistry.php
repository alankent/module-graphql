<?php

namespace AlanKent\GraphQL\Types;

use AlanKent\GraphQL\Persistence\EntityDefinition;
use AlanKent\GraphQL\Persistence\EntityManager;
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

    // Map of type names seen so far to GraphQL types.
    /** @var InputType[] */
    private $filterTypes;

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

        $this->filterTypes = [
            'String' => new InputObjectType([
                'name' => 'StringFilter',
                'description' => 'String value filter constraints',
                'fields' => [
                    // TODO: Use 'from' and 'to' or 'ge' and 'le' combined? or 'between' with array of size 2
                    // TODO: I am more used to eq, ne, gt, ge, lt, le,
                    // TODO: Then in/nin, null/notnull? Why wasn't it "notin"?
                    // TODO: How does "moreq" differ from "gteq"?
                    'from' => [ 'type' => Type::string(), 'description' => "Attribute from value (used with 'to').'" ],
                    'to' => [ 'type' => Type::string(), 'description' => "Attribute to value (used with 'from')." ],
                    'eq' => [ 'type' => Type::string(), 'description' => 'Attribute equals constant.' ],
                    'neq' => [ 'type' => Type::string(), 'description' => 'Attribute does not equal constant.' ],
                    'like' => [ 'type' => Type::string(), 'description' => 'Attribute value matches LIKE pattern.' ],
                    'in' => [ 'type' => Type::listOf(Type::nonNull(Type::string())), 'description' => 'Attribute in list of values.' ],
                    'nin' => [ 'type' => Type::listOf(Type::nonNull(Type::string())), 'description' => 'Attribute not in list of values.' ],
                    'notnull' => [ 'type' => Type::boolean(), 'description' => 'Attribute is not null (value MUST be "true").' ],
                    'null' => [ 'type' => Type::boolean(), 'description' => 'Attribute not null (value MUST be "true").' ],
                    'moreq' => [ 'type' => Type::string(), 'description' => 'Attribute more or equal.' ], // TODO: SAME AS gteq???
                    'gt' => [ 'type' => Type::string(), 'description' => 'Attribute greater than value.' ],
                    'lt' => [ 'type' => Type::string(), 'description' => 'Attribute less than value.' ],
                    'gteq' => [ 'type' => Type::string(), 'description' => 'Attribute greater than or equal to value.' ],
                    'lteq' => [ 'type' => Type::string(), 'description' => 'Attribute less than or equal to value.' ],
                    'finset' => [ 'type' => Type::string(), 'description' => 'Attribute in set of comma separated values.' ],
                ]
            ]),
            'Int' => new InputObjectType([
                'name' => 'IntFilter',
                'description' => 'Integer value filter constraints',
                'fields' => [
                    'from' => [ 'type' => Type::int(), 'description' => "Attribute from value (used with 'to').'" ],
                    'to' => [ 'type' => Type::int(), 'description' => "Attribute to value (used with 'from')." ],
                    'eq' => [ 'type' => Type::int(), 'description' => 'Attribute equals constant.' ],
                    'neq' => [ 'type' => Type::int(), 'description' => 'Attribute does not equal constant.' ],
                    'like' => [ 'type' => Type::int(), 'description' => 'Attribute value matches LIKE pattern.' ],
                    'in' => [ 'type' => Type::listOf(Type::nonNull(Type::int())), 'description' => 'Attribute in list of values.' ],
                    'nin' => [ 'type' => Type::listOf(Type::nonNull(Type::int())), 'description' => 'Attribute not in list of values.' ],
                    'notnull' => [ 'type' => Type::boolean(), 'description' => 'Attribute is not null (value MUST be "true").' ],
                    'null' => [ 'type' => Type::boolean(), 'description' => 'Attribute not null (value MUST be "true").' ],
                    'moreq' => [ 'type' => Type::int(), 'description' => 'Attribute more or equal.' ], // TODO: SAME AS gteq???
                    'gt' => [ 'type' => Type::int(), 'description' => 'Attribute greater than value.' ],
                    'lt' => [ 'type' => Type::int(), 'description' => 'Attribute less than value.' ],
                    'gteq' => [ 'type' => Type::int(), 'description' => 'Attribute greater than or equal to value.' ],
                    'lteq' => [ 'type' => Type::int(), 'description' => 'Attribute less than or equal to value.' ],
                    'finset' => [ 'type' => Type::string(), 'description' => 'Attribute in set of comma separated values.' ],
                ]
            ]),
            'Float' => new InputObjectType([
                'name' => 'FloatFilter',
                'description' => 'Float value filter constraints',
                'fields' => [
                    'from' => [ 'type' => Type::float(), 'description' => "Attribute from value (used with 'to').'" ],
                    'to' => [ 'type' => Type::float(), 'description' => "Attribute to value (used with 'from')." ],
                    'eq' => [ 'type' => Type::float(), 'description' => 'Attribute equals constant.' ],
                    'neq' => [ 'type' => Type::float(), 'description' => 'Attribute does not equal constant.' ],
                    'like' => [ 'type' => Type::float(), 'description' => 'Attribute value matches LIKE pattern.' ],
                    'in' => [ 'type' => Type::listOf(Type::nonNull(Type::float())), 'description' => 'Attribute in list of values.' ],
                    'nin' => [ 'type' => Type::listOf(Type::nonNull(Type::float())), 'description' => 'Attribute not in list of values.' ],
                    'notnull' => [ 'type' => Type::boolean(), 'description' => 'Attribute is not null (value MUST be "true").' ],
                    'null' => [ 'type' => Type::boolean(), 'description' => 'Attribute not null (value MUST be "true").' ],
                    'moreq' => [ 'type' => Type::float(), 'description' => 'Attribute more or equal.' ], // TODO: SAME AS gteq???
                    'gt' => [ 'type' => Type::float(), 'description' => 'Attribute greater than value.' ],
                    'lt' => [ 'type' => Type::float(), 'description' => 'Attribute less than value.' ],
                    'gteq' => [ 'type' => Type::float(), 'description' => 'Attribute greater than or equal to value.' ],
                    'lteq' => [ 'type' => Type::float(), 'description' => 'Attribute less than or equal to value.' ],
                    'finset' => [ 'type' => Type::string(), 'description' => 'Attribute in set of comma separated values.' ],
                ]
            ]),
            'ID' => new InputObjectType([
                'name' => 'IDFilter',
                'description' => 'ID value filter constraints',
                'fields' => [
                    'eq' => [ 'type' => Type::id(), 'description' => 'Attribute equals constant.' ],
                    'neq' => [ 'type' => Type::id(), 'description' => 'Attribute does not equal constant.' ],
                    'in' => [ 'type' => Type::listOf(Type::nonNull(Type::id())), 'description' => 'Attribute in list of values.' ],
                    'nin' => [ 'type' => Type::listOf(Type::nonNull(Type::id())), 'description' => 'Attribute not in list of values.' ],
                    'notnull' => [ 'type' => Type::boolean(), 'description' => 'Attribute is not null (value MUST be "true").' ],
                    'null' => [ 'type' => Type::boolean(), 'description' => 'Attribute not null (value MUST be "true").' ],
                    'finset' => [ 'type' => Type::string(), 'description' => 'Attribute in set of comma separated values.' ],
                ]
            ]),
            'Boolean' => new InputObjectType([
                'name' => 'BooleanFilter',
                'description' => 'Boolean value filter constraints',
                'fields' => [
                    'eq' => [ 'type' => Type::boolean(), 'description' => 'Attribute equals constant.' ],
                    'neq' => [ 'type' => Type::boolean(), 'description' => 'Attribute does not equal constant.' ],
                    'in' => [ 'type' => Type::listOf(Type::nonNull(Type::boolean())), 'description' => 'Attribute in list of values.' ],
                    'nin' => [ 'type' => Type::listOf(Type::nonNull(Type::boolean())), 'description' => 'Attribute not in list of values.' ],
                    'notnull' => [ 'type' => Type::boolean(), 'description' => 'Attribute is not null (value MUST be "true").' ],
                    'null' => [ 'type' => Type::boolean(), 'description' => 'Attribute not null (value MUST be "true").' ],
                    'finset' => [ 'type' => Type::string(), 'description' => 'Attribute in set of comma separated values.' ],
                ]
            ]),
        ];
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
    public function getOutputType(string $objectName): OutputType
    {
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
    public function makeOutputType(string $typeString): OutputType
    {
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
    public function makeInputType(string $typeString): InputType
    {
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
     * Convert a string such as "String" or "[Customer!]!" to the
     * corresponding GraphQL type. The entity manager returns types
     * as strings, which this function converts to the GraphQL form.
     * @param string $typeName The string encoding of the type.
     * @return OutputType The GraphQL 'type' construct.
     */
    public function makeFilterType(string $typeName): InputType
    {
        if (!isset($this->filterTypes[$typeName])) {
            $this->compileType($typeName);
        }
        return $this->filterTypes[$typeName];
    }

    /**
     * Converts the schema returned by the entity manager into config
     * required by the GraphQL type system. An "InputType" and "OutputType"
     * are created. These are close, but not identical.
     */
    private function compileType(string $entityName)
    {
        /** @var EntityDefinition $entityDef */
        $entityDef = $this->entityManager->getEntityDefinition($entityName);
        if ($entityDef == null) {
            throw new \Exception("Unknown type '$entityName'.");
        }

        $this->outputTypes[$entityName] = new ObjectType([
            'name' => $entityName,
            'description' => $entityDef->getDescription(),
            'fields' => function() use($entityDef) {
                // Use a function here so all types can be declared before field types are resolved.
                $fields = [];
                foreach ($entityDef->getAttributes() as $attribute) {
                    $type = $this->makeOutputType($attribute->getTypeName());
                    if ($attribute->isRepeating()) {
                        $type = Type::listOf(Type::nonNull($type));
                    }
                    if (!$attribute->isNullable()) {
                        $type = Type::nonNull($type);
                    }
                    $fieldDef = [
                        'type' => $type,
                        'description' => $attribute->getDescription(),
                        'resolve' => function($val, $args, $context, $info) use ($attribute) {
                                /** @var \AlanKent\GraphQL\Persistence\Entity $val */
                                return $val->getAttribute($attribute->getName());
                            },
                    ];
                    if ($attribute->isRepeating()) {
                        $fieldDef['args'] = [
                            'start' => [
                                'type' => Type::int(),
                                'defaultValue' => 0,
                                'description' => 'Offset of first value to return.'
                            ],
                            'limit' => [
                                'type' => Type::int(),
                                'description' => 'Maximum values to return from array (default is all values).'
                            ],
                            'filter' => [
                                'type' => $this->makeFilterType($attribute->getTypeName()),
                                'description' => 'Only return values matching filter constraint.'
                            ],
                        ];
                    } else if (!$attribute->isScalar()) {
                        $fieldDef['args'] = [
                            'filter' => [
                                'type' => $this->makeFilterType($attribute->getTypeName()),
                                'description' => 'Only return values matching filter constraint.'
                            ],
                        ];
                    }
                    $fields[$attribute->getName()] = $fieldDef;
                }
                return $fields;
            }
        ]);

        // Skip over fields of type "ID", and computed fields as they are not store in database.
        $this->inputTypes[$entityName] = new InputObjectType([
            'name' => $entityName . 'Input',
            'description' => $entityDef->getDescription(),
            'fields' => function() use($entityDef) {
                $fields = [];
                foreach ($entityDef->getAttributes() as $attribute) {
                    if ($attribute->getTypeName() !== 'ID' && !$attribute->isComputed()) {
                        $type = $this->makeInputType($attribute->getTypeName());
                        if ($attribute->isRepeating()) {
                            $type = Type::listOf(Type::nonNull($type));
                        }
                        if (!$attribute->isNullable()) {
                            $type = Type::nonNull($type);
                        }
                        $fields[$attribute->getName()] = [
                            'type' => $type,
                            'description' => $attribute->getDescription(),
                        ];
                    }
                }
                if (count($fields) == 0) {
                    $entityName = $entityDef->getName();
                    throw new \Exception("Type '$entityName' has no input fields.'");
                }
                return $fields;
            }
        ]);

        // Filters can specify all attributes.
        $this->filterTypes[$entityName] = new InputObjectType([
            'name' => $entityName . 'Filter',
            'description' => 'Filter conditions for ' . $entityName . ' instances.',
            'fields' => function() use($entityDef) {
                $fields = [];

                $fields['_and'] = [
                    'type' => Type::listOf(Type::nonNull($this->makeFilterType($entityDef->getName()))),
                    'description' => 'Join multiple conditions via AND.'
                ];

                $fields['_or'] = [
                    'type' => Type::listOf(Type::nonNull($this->makeFilterType($entityDef->getName()))),
                    'description' => 'Join multiple conditions via OR.'
                ];

                foreach ($entityDef->getAttributes() as $attribute) {
                    $fields[$attribute->getName()] = [
                        'type' => $this->makeFilterType($attribute->getTypeName()),
                        'description' => $attribute->getName() . ' constraints.',
                    ];
                }

                return $fields;
            }
        ]);
    }
}
