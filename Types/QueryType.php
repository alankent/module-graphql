<?php

namespace AlanKent\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class QueryType extends ObjectType
{
    /** @var \AlanKent\GraphQL\App\EntityManager */
    private $entityManager;

    /** @var \AlanKent\GraphQL\Types\TypeRegistry */
    private $typeRegistry;

    /**
     * Constructor.
     * @param \AlanKent\GraphQL\Types\AutoEntitiesTypeFactory $autoFactory
     * @param \AlanKent\GraphQL\Types\EntitiesTypeFactory $entityTypeFactory
     * @param \AlanKent\GraphQL\App\EntityManager $entityManager
     * @param TypeRegistry $typeRegistry
     */
    public function __construct(
        \AlanKent\GraphQL\Types\AutoEntitiesTypeFactory $autoFactory,
        \AlanKent\GraphQL\Types\EntitiesTypeFactory $entityTypeFactory,
        \AlanKent\GraphQL\App\EntityManager $entityManager,
        \AlanKent\GraphQL\Types\TypeRegistry $typeRegistry
    ) {
        $this->entityManager = $entityManager;
        $this->typeRegistry = $typeRegistry;

        $config = [
            'name' => 'Query',
            'description' => 'Query type for all supported queries.',
            'fields' => [
                'hello' => [
                    'type' => Type::string(),
                    'description' => 'Returns a simple greeting (Hellow World!) message.',
                    'resolve' => function() {
                        return 'Your graphql-php endpoint is ready now! Use GraphiQL to browse API';
                    }
                ],
                //'me' => new UserType()
                /*
                'catalog' => [
                    'type' => new MagentoCatalogType(),
                    'description' => 'Magento Catalog module type',
                    'resolve' => function() { return 'dummy'; }
                ],
                */
//                'entities' => [
//                    'type' => $entityTypeFactory->create(),
//                    'description' => 'All entities exposed for querying.',
//                    'resolve' => function() { return 'dummy'; }
//                ],
                //'auto' => [
                    //'type' => $autoFactory->create(),
                    //'description' => 'Service contract entities.',
                    //'resolve' => function() { return 'dummy'; }
                //],
                'customerData' => [
                    'type' => $this->typeRegistry->makeType('Customer'),
                    'description' => 'Retrieve customer data',
                    'resolve' => function() {
                        return null; // TODO: CUSTOMER DATA
                    }
                ],
                'products' => [
                    'type' => $this->typeRegistry->makeType('[Product!]!'),
                    'description' => 'Retrieve customer data',
                    'resolve' => function() {
                        return null; // TODO: PRODUCT SEARCH DATA
                    }
                ],
            ],
//            'resolveField' => function($val, $args, $context, ResolveInfo $info) {
//                return $this->{$info->fieldName}($val, $args, $context, $info);
//            }
        ];

//        foreach ($this->entityManager->getNames() as $entityName) {
//            $entitySchema = $this->entityManager->getEntitySchema($entityName);
//            $config[] = [
//                'type' => $this->typeRegistry->makeType($entitySchema['type']),
//                'description' => $entitySchema['description'],
//                //'resolve' => function() { return 'dummy'; }
//            ];
//        }
        parent::__construct($config);
    }
//    public function hello()
//    {
//        return 'Your graphql-php endpoint is ready now! Use GraphiQL to browse API';
//    }

//    public function me()
//    {
//        $me = new UserType();
//        $me->id = '1';
//        $me->firstName = 'Alan';
//        $me->lastName = 'Kent';
//        return $me;
//    }
}
