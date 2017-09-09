<?php

namespace AlanKent\GraphQL\Persistence;

/**
 * A tree of attribute requests for an entity, where any 'entity' attributes
 * always have the attributes to retrieve from that entity.
 * If the value is an array, start and limit specify what subrange of the array to retrieve.
 */
class EntityRequest
{
    /** @var EntityDefinition */
    private $entity;

    /** @var AttributeRequest[] - key is attribute name, value is AttribueReequest instance. */
    private $attributes;

    public function __construct(EntityDefinition $entity)
    {
        $this->entity = $entity;
        $this->attributes = [];
    }

    /**
     * Request the specified attribute.
     * @param AttributeDefinition $attribute The attribute to request.
     * @param EntityRequest $entity If the attribute is not a scalar type, an EntityRequest instance
     * must be supplied. This contains the attributes to retrieve from the requested entity.
     * @param int|null $start If a repeating attribute, the zero based start offset.
     * @param int|null $limit If a repeating attribute, the max values to return after the start offset.
     * (Can be less if the array is smaller than the requested number of values.)
     */
    public function addAttribute(AttributeDefinition $attribute, EntityRequest $entity = null, $start = null, $limit = null)
    {
        $this->attributes[$attribute->getName()] = new AttributeRequest($attribute, $entity, $start, $limit);
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->entity;
    }

    public function __toString()
    {
        $out = $this->entity->getName();
        $out .= '[';
        $sep = '';
        foreach ($this->attributes as $attribute) {
            $out .= $sep . (string)$attribute;
            $sep = ' ';
        }
        $out .= ']';
        return $out;
    }
}
