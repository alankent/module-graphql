<?php

namespace AlanKent\GraphQL\Persistence;

/**
 * Entities have a name an a set of named attributes.
 */
class EntityDefinition
{
    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var AttributeDefinition[] */
    private $attributes;

    /**
     * Construct an entity definition. Note that a make() function is used to be symetric with
     * the makeScalar() and makeEntity() functions of AttributeDefinition.
     * @param string $entityName The name of the entity.
     * @param string $description Description of the entity, suitable for developers to use.
     * @param AttributeDefinition[] $attributes An array of attribute definitions.
     * @return EntityDefinition The newly create entity definition.
     */
    public static function make(string $entityName, string $description, $attributes)
    {
        $def = new self();
        $def->name = $entityName;
        $def->description = $description;
        $def->attributes = [];
        foreach ($attributes as $attribute) {
            $def->attributes[$attribute->getName()] = $attribute;
        }
        return $def;
    }

    /**
     * Returns the name of the entity being defined.
     * @return string The entity name. Never null.
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
     * Returns the requested attribute name, or null if not defined.
     * @param string $name The requested attribute name.
     * @return AttributeDefinition|null The attribute definition, or null if not recognized.
     */
    public function getAttribute(string $name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * Return map of attribute names to defintions.
     * @return AttributeDefinition[] Returns array of attribute definitions where index is the attribute name.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
