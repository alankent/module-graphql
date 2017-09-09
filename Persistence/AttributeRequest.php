<?php

namespace AlanKent\GraphQL\Persistence;

/**
 * A tree of attribute requests for an entity, where any 'entity' attributes
 * always have the attributes to retrieve from that entity.
 * If the value is an array, start and limit specify what subrange of the array to retrieve.
 */
class AttributeRequest
{
    /** @var AttributeDefinition */
    private $attribute;

    /** @var EntityRequest */
    private $entityRequest;

    /** @var int */
    private $start;

    /** @var int */
    private $limit;

    public function __construct(AttributeDefinition $attribute, EntityRequest $entity = null, $start = null, $limit = null)
    {
        $this->attribute = $attribute;
        $this->entityRequest = $entity;
        $this->start = $start;
        $this->limit = $limit;
    }

    public function __toString()
    {
        $out = $this->attribute->getName();
        if ($this->attribute->isRepeating()) {
            $out .= '(' . $this->start . ',' . $this->limit . ')';
        }
        if ($this->entityRequest != null) {
            $out .= ':' . (string)$this->entityRequest;
        }
        return $out;
    }
}
