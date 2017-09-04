<?php

namespace AlanKent\GraphQL\Types;

use AlanKent\GraphQL\App\Entity;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputType;

class MutationType extends ObjectType
{
    /** @var \AlanKent\GraphQL\App\EntityManager */
    private $entityManager;

    /** @var \AlanKent\GraphQL\Types\TypeRegistry */
    private $typeRegistry;

    /** @var \Magento\Framework\Api\SearchCriteriaInterfaceFactory */
    private $searchCriteriaFactory;

    /**
     * Constructor.
     * @param \AlanKent\GraphQL\Types\EntitiesTypeFactory $entityTypeFactory
     * @param \AlanKent\GraphQL\App\EntityManager $entityManager
     * @param TypeRegistry $typeRegistry
     */
    public function __construct(
        \AlanKent\GraphQL\Types\EntitiesTypeFactory $entityTypeFactory,
        \AlanKent\GraphQL\App\EntityManager $entityManager,
        \AlanKent\GraphQL\Types\TypeRegistry $typeRegistry
    ) {
        $this->entityManager = $entityManager;
        $this->typeRegistry = $typeRegistry;

//var_dump($this->typeRegistry->makeInputType('Order'));
//        var_dump($this->typeRegistry->makeInputType('Order')->getFields());
//$it = $this->typeRegistry->makeInputType('Order');
//var_dump($it instanceof InputType);

        $config = [
            'name' => 'Mutation',
            'description' => 'Mutation class for all mutation methods.',
            'fields' => [
                'hello' => [
                    'type' => Type::string(),
                    'description' => 'Returns a simple greeting (Hellow World!) message.',
                    'resolve' => function() {
                        return 'Your graphql-php endpoint is ready now! Use GraphiQL to browse API';
                    }
                ],
                'placeOrder' => [
                    'type' => $this->typeRegistry->makeOutputType('Order'), // TODO: Should be "Order!"
                    'description' => 'Place an order.',
                    'args' => [
                        'order' => [
                            'type' => $this->typeRegistry->makeInputType('Order!'),
                            'description' => 'The order to be placed.'
                        ]
                    ],
                    'resolve' => function() {
                        return null; // TODO
                    }
                ],
            ],
//            'resolveField' => function($val, $args, $context, ResolveInfo $info) {
//                return $this->{$info->fieldName}($val, $args, $context, $info);
//            }
        ];

        parent::__construct($config);
    }
}
