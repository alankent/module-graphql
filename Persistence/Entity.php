<?php

namespace AlanKent\GraphQL\Persistence;

use \AlanKent\GraphQL\Persistence\EntityDefinition;

/**
 * Used to access the contents and schema of an entity, such as "Product" or "Customer".
 * This is class exists until the new Magento Persistence Layer is implemented, at
 * which time this class would be replaced by an "Attribute Value Set" returned by
 * query operations.
 */
class Entity
{
    /**@ var string */
    private $name;

    /**@ var array */
    private $schema;

    /**@ var Object */
    private $dataEntity;

    /**
     * Constructor.
     * @param string $name
     * @param EntityDefinition $schema
     * @param Object $dataEntity
     */
    public function __construct(
        string $name,
        EntityDefintion $schema,
        $dataEntity
    ) {
        $this->name = $name;
        $this->schema = $schema;
        $this->dataEntity = $dataEntity;
    }

    /**
     * Return the entity name.
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Return the entity description.
     */
    public function getDescription(): string {
        return $this->schema['description'];
    }

    /**
     * Given an data entity, fetch the specified attribute.
     */
    public function getAttribute($code) {

        // Look up attribute in schema.
        if (!isset($this->schema['fields'][$code])) {
            // TODO: Throw exception?
            return null;
        }
        $fieldSchema = $this->schema['fields'][$code];

        // See if custom function defined.
        if (isset($fieldSchema['resolve'])) {
            $func = $fieldSchema['resolve'];
            return $func($this->dataEntity, $code, $fieldSchema);
        }

        // Try as a direct attribute.
        $getFn = 'get' . ucfirst($code);
        if (method_exists($this->dataEntity, $getFn)) {
            return $this->dataEntity->$getFn();
        }

        // Try as an extension attribute
        if (method_exists($this->dataEntity, 'getExtensionAttributes')) {
            $ext = $this->dataEntity->getExtensionAttributes($code);
            if (method_exists($ext, $getFn)) {
                return $ext->$getFn();
            }
        }

        // Try as a custom attribute
        if ($this->dataEntity instanceof \Magento\Framework\Api\CustomAttributesDataInterface) {
            return $this->dataEntity->getCustomAttribute($code)->getValue();
        }

        return null;
    }
}
