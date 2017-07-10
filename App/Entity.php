<?php

namespace AlanKent\GraphQL\App;


/**
 * Used to access the contents and schema of an entity, suchas "Product" or "Customer".
 */
class Entity
{
    /**@ var array */
    private $schema;

    /**@ var string */
    private $name;

    /**@ var string */
    private $description;

    /**
     * Constructor.
     * @param string $name
     * @param string description
     * TODO: Will need more arguments - for now we hard code the schema.
     */
    public function __construct(
        string $name,
        string $description
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->schema = [
            // TODO: Temporary until we can compute it automatically.
            'id' =>             [ 'type' => 'int',     'description' => '' ],
            'sku' =>            [ 'type' => 'string',  'description' => 'Stock Keeping Unit (SKU).' ],
            'name' =>           [ 'type' => 'string',  'description' => '' ],
            'attributeSetId' => [ 'type' => 'int',     'description' => '' ],
            'price' =>          [ 'type' => 'decimal', 'description' => '' ],
            'status' =>         [ 'type' => 'int',     'description' => '' ],
            'visibility' =>     [ 'type' => 'int',     'description' => '' ],
            'typeId' =>         [ 'type' => 'string',  'description' => '' ],
            'createdAt' =>      [ 'type' => 'string',  'description' => '' ],
            'updatedAt' =>      [ 'type' => 'string',  'description' => '' ],
            'weight' =>         [ 'type' => 'decimal', 'description' => '' ],
            'material' =>       [ 'type' => 'string',  'description' => '' ],
            'cust_attr' =>      [ 'type' => 'string',  'description' => 'Demo custom attribute I added by hand' ],
        ];
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
        return $this->description;
    }

    /**
     * Return the schema (all the attributes) of the entity.
     */
    public function getSchema(): array {
        return $this->schema;
    }

    /**
     * Given an data entity, fetch the specified attribute.
     */
    public function getAttribute($dataEntity, $code) {

        // Try as a direct attribute.
        $getFn = 'get' . ucfirst($code);
        if (method_exists($dataEntity, $getFn)) {
            return $dataEntity->$getFn();
        }

        // Try as an extension attribute
        if (method_exists($dataEntity, 'getExtensionAttributes')) {
            $ext = $dataEntity->getExtensionAttributes($code);
            if (method_exists($ext, $getFn)) {
                return $ext->$getFn();
            }
        }

        // Try as a custom attribute
        if ($dataEntity instanceof \Magento\Framework\Api\CustomAttributesDataInterface) {
            return $dataEntity->getCustomAttribute($code)->getValue();
        }

        return null;
    }
}
