<?php

namespace AlanKent\GraphQL\Persistence;

/**
 * Describes an attribute of an entity. Attributes have a name and reasonably complex type information.
 *
 * If an attribute has a scalar type, it has a scalar type name and a nullable flag.
 * If an attribute has an entity type, it has an entity name, a repeating flag, and if not repeating an optional flag.
 */
class AttributeDefinition
{
    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var string */
    private $typeName;

    /** @var bool */
    private $nullable;

    /** @var bool */
    private $repeating;

    /** @var callable */
    private $computeFunc;

    /**
     * Create a attribute definition referencing another entity.
     * @param string $attributeName The name of the attribute.
     * @param string $description The attribute description for developers to read. Should never be null.
     * @param string $entityTypeName The name of the entity this attribute returns.
     * @param bool $repeating True if the attribute can return zero or more values, false if at most one value.
     * @param bool $nullable True if the attribute can be null.
     */
    public static function make(string $attributeName, string $description, string $entityTypeName, bool $repeating, bool $nullable, $computeFunc = null)
    {
        /** @var AttributeDefinition $def */
        $def = new self();
        $def->name = $attributeName;
        $def->description = $description;
        $def->typeName = $entityTypeName;
        $def->repeating = $repeating;
        $def->nullable = $nullable;
        $def->computeFunc = $computeFunc;
        return $def;
    }

    /**
     * Return the name of the attribute being described.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the entity description.
     * @return string The entity description. Never null.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Returns the attribute type name.
     * @return string The scalar or entity type name.
     */
    public function getTypeName(): string
    {
        return $this->typeName;
    }

    /**
     * Returns true if fetching this attribute value can ever be null.
     * @return bool If scalar, true if can be null, false if always present. If an entity type, true if the
     * attribute is not repeating and can be null, otherwise false.
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Returns true if this is a repeating entity value attribute.
     * @return bool If an entity type, returns true if repeating (zero or more values), false otherwise. Behavior is
     * undefined for scalar attributes.
     */
    public function isRepeating(): bool
    {
        return $this->repeating;
    }

    /**
     * Return true if this attribute is computed from other attributes, not stored in the database.
     * @return bool True if the attibute is computed (read only).
     */
    public function isComputed(): bool
    {
        return $this->computeFunc != null;
    }

    public function compute($val)
    {
        return $this->computeFunc($val);
    }
}

