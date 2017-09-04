<?php

namespace AlanKent\GraphQL\Types;

use AlanKent\GraphQL\App\Context;
use AlanKent\GraphQL\App\Entity;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputType;
use Magento\Customer\Api\AccountManagementInterface;

class MutationType extends ObjectType
{
    /** @var \AlanKent\GraphQL\App\EntityManager */
    private $entityManager;

    /** @var \AlanKent\GraphQL\Types\TypeRegistry */
    private $typeRegistry;

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
                    'resolve' => function($val, $args, $context, ResolveInfo $info) {
                        return null; // TODO
                    }
                ],
                'requestPasswordReset' => [
                    'type' => StatusType::singleton(),
                    'description' => 'Request a password reset',
                    'args' => [
                        'email' => [
                            'type' => Type::string(),
                            'description' => 'Email address of the customer to request the password reset for.'
                        ]
                    ],
                    'resolve' => function($val, $args, $context, ResolveInfo $info) {
                        /** @var Context $context */
                        /** @var AccountManagementInterface $ami */
                        $ami = $context->getServiceContract(AccountManagementInterface::class);
                        //$ami->TODO - not sure which method to call!
                        return new StatusValue(false, 'Reset method not implemented yet.');
                    }
                ],
            ],
        ];

        parent::__construct($config);
    }
}
