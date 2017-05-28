<?php

namespace AlanKent\GraphQL\App;

use Magento\Framework\ObjectManagerInterface;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Object type for querying the Magento_Catalog service contracts.
 */
class Context
{
    /** $var ObjectManagerInterface */
    private $objectManager;

    /**
     * Constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager) {
        $this->objectManager = $objectManager;
    }

    /**
     * Return the service contract class instance so we can call a method on it.
     * This will lazily load the service contract. If the classes used dependency injection
     * of the constructor, they would force load all the classes at startup, even if not needed.
     */
    public function getServiceContract($name) {
        return $this->objectManager->get($name);
    }
}
