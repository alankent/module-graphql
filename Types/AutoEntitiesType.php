<?php

namespace AlanKent\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\NonNull;

use Magento\Webapi\Model\ServiceMetadata;

/**
 * Object type for querying the available entities (*RepositoryInterface service contracts).
 * This class might go away.
 */
class AutoEntitiesType extends ObjectType
{
    /**
     * Constructor.
     * param \Magento\Webapi\Model\ServiceMetadata $serviceMetadata
     * @param \AlanKent\GraphQL\App\EntityManager $entityManager
     */
    public function __construct(
        \AlanKent\GraphQL\App\EntityManager $entityManager
    ) {
        $fields = [];
        foreach ($entityManager->listEntities() as $entityName) {
            $entity = $entityManager->getEntity($entityName);
            $fields[$entityName] = [
                'type' => $entity->getType(),
                'description' => $entity->getDescription(),
            ];
        }

        $config = [
            'name' => 'AutoEntities',
            'description' => 'All repository interfaces exposed via GraphQL.',
            'fields' => $fields,
        ];

        parent::__construct($config);
    }
}
