<?php

namespace AlanKent\GraphQL\App;


/**
 * This class provides access to all available entities.
 */
class EntityManager
{
    // /** @var EntityFactory */
    // private $entityFactory;

    /**
     * Constructor.
     * @param \AlanKent\GraphQL\App\EntityFactory $entityFactory
     */
    public function __construct(
        // \AlanKent\GraphQL\App\EntityFactory $entityFactory
    ) {
        // $this->entityFactory = $entityFactory;
    }

    /**
     * Return a list of all supported entity names.
     */
    public function listEntities(): array {
        return ['Product']; // TODO: Hard coded for now.
    }

    /**
     * Return a handle to the specified entity name, or null if the entity name is not known.
     */
    public function getEntity(string $name): Entity {
        if ($name != 'Product') { // TODO: Hard coded for now.
            return null;
        }
        // return $this->entityFactory->create($name, \Magento\Catalog\Api\Data\ProductInterface::class);
        return new Entity('Product', 'Product entity');
    }
}
