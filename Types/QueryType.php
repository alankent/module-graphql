<?php

namespace AlanKent\GraphQL\Types;

use AlanKent\GraphQL\App\Context;
use AlanKent\GraphQL\Persistence\Entity;
use AlanKent\GraphQL\Persistence\EntityRequest;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class QueryType extends ObjectType
{
    /** @var \AlanKent\GraphQL\Persistence\EntityManager */
    private $entityManager;

    /** @var \AlanKent\GraphQL\Types\TypeRegistry */
    private $typeRegistry;

    /** @var \Magento\Framework\Api\SearchCriteriaInterfaceFactory */
    private $searchCriteriaFactory;

    /**
     * Constructor.
     * @param \AlanKent\GraphQL\Types\AutoEntitiesTypeFactory $autoFactory
     * @param \AlanKent\GraphQL\Types\EntitiesTypeFactory $entityTypeFactory
     * @param \AlanKent\GraphQL\Persistence\EntityManager $entityManager
     * @param TypeRegistry $typeRegistry
     */
    public function __construct(
        \AlanKent\GraphQL\Types\AutoEntitiesTypeFactory $autoFactory,
        \AlanKent\GraphQL\Types\EntitiesTypeFactory $entityTypeFactory,
        \AlanKent\GraphQL\Persistence\EntityManager $entityManager,
        \AlanKent\GraphQL\Types\TypeRegistry $typeRegistry,
        \Magento\Framework\Api\SearchCriteriaInterfaceFactory $searchCriteriaFactory
    ) {
        $this->entityManager = $entityManager;
        $this->typeRegistry = $typeRegistry;
        $this->searchCriteriaFactory = $searchCriteriaFactory;

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
                    'type' => $this->typeRegistry->makeOutputType('Customer'),
                    'description' => 'Retrieve customer data',
                    'resolve' => function($val, $args, $context, $info) {

                        // Parse request to work out requested attributes.
                        $req = $this->parseRequest($info);
                        throw new \Exception((string)$req); // TODO: Debugging.

                        return null; // TODO: CUSTOMER DATA
                    }
                ],
                'product' => [
                    'type' => $this->typeRegistry->makeOutputType('Product'),
                    'description' => 'Retrieve one product (or null).',
                    'args' => [
                        // TODO: One recommendation was to always have one input arg with an InputType, not multiple args like is done here.
                        'sku' => [
                            'type' => Type::string(),
                            'description' => 'The SKU of the product to search for. (Supply SKU or id.)'
                        ],
                        'id' => [
                            'type' => Type::int(),
                            'description' => 'The id of the product to search for. (Supply SKU or id.)'
                        ],
//                        'storeId' => [
//                            'type' => Type::int(),
//                            'description' => 'The store view id, or omitted for the default store view.',
//                            //'defaultValue' => 0  // Default should be null? Is null same as zero?
//                        ]
                    ],
                    'resolve' => function($val, $args, $context, $info) {

                        // Parse request to work out requested attributes.
                        $req = $this->parseRequest($info);

                        // Lazy load at runtime.
                        /** @var Context $context */
                        /** @var  $sc \Magento\Catalog\Api\ProductRepositoryInterface */
                        $sc = $context->getServiceContract(\Magento\Catalog\Api\ProductRepositoryInterface::class);

//                        $storeId = isset($args['storeId']) ? $args['storeId'] : null;
                        $storeId = null;

                        if (isset($args['id']) && isset($args['sku'])) {
                            throw new \Exception('Specify either "sku" or "id", not both.');
                        }
                        if (isset($args['id'])) {
                            $val = $sc->getById($args['id'], false, $storeId);
                            if ($val == null) {
                                return null;
                            }
                            if (!($val instanceof \Magento\Catalog\Api\Data\ProductInterface)) { var_dump($val); throw new \Exception("Sky is falling"); }
                            return $this->entityManager->getEntity($req, $val);
                        }
                        if (isset($args['sku'])) {
                            $val = $sc->get($args['sku'], false, $storeId);
                            if ($val == null) {
                                return null;
                            }
                            return $this->entityManager->getEntity($req, $val);
                        }
                        throw new \Exception('You must specify either "sku" or "id".');
                    }
                ],
                'products' => [
                    'type' => $this->typeRegistry->makeOutputType('[Product!]!'),
                    'description' => 'Retrieve customer data',
                    'args' => [
                        // TODO: One recommendation was to always have one input arg with an InputType, not multiple args like is done here.
                        'first' => [
                            'type' => Type::int(),
                            'description' => 'Index of first product to return (default is 0).'
                        ],
                        'count' => [
                            'type' => Type::int(),
                            'description' => 'How many to return (default is all)'
                        ],
    //                        'storeId' => [
    //                            'type' => Type::int(),
    //                            'description' => 'The store view id, or omitted for the default store view.',
    //                            //'defaultValue' => 0  // Default should be null? Is null same as zero?
    //                        ]
                    ],
                    'resolve' => function($val, $args, $context, $info) {

                        $req = $this->parseRequest($info, 'Product');

                        // Lazy load at runtime.
                        /** @var Context $context */
                        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $sc */
                        $sc = $context->getServiceContract(\Magento\Catalog\Api\ProductRepositoryInterface::class);

    //                        $storeId = isset($args['storeId']) ? $args['storeId'] : null;

                        $first = 0;
                        if (isset($args['first'])) {
                            $first = $args['first'];
                        }
                        $count = 0;
                        if (isset($args['count'])) {
                            $first = $args['count'];
                        }
                        $searchCriteria = $this->searchCriteriaFactory->create();
                        $searchCriteria->setPageSize(1);
                        $searchCriteria->setCurrentPage($first);
                        // TODO: CANNOT DO OFFSET AND COUNT: $searchCriteria->setPageCount($count);
                        $items = $sc->getList($searchCriteria)->getItems();
                        $val = [];
                        foreach ($items as $item) {
                            if ($count-- <= 0) {
                                break;
                            }
                            $val[] = $this->entityManager->getEntity($req, $item);
                        }
                        return $val;
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

    private function parseRequest(ResolveInfo $info): EntityRequest
    {
        // TODO: Path is an array, should navigate from root down full path, not just take last name in path.
        $startingFieldName = $info->path[count($info->path) - 1];
        $entityName = (string)$info->returnType;

        // We need to find the selection node for the current query field.
        foreach ($info->fieldNodes as $fieldNode) {
            $fieldName = $fieldNode->name->value;
            if ($fieldName === $startingFieldName) {

                // We found the current field. Now walk the tree of field requests,
                // building up the EntityRequest object.
                $entity = $this->entityManager->getEntityDefinition($entityName);
                $entityReq = new EntityRequest($entity);
                $this->walk($info, $entityReq, $fieldNode->selectionSet);
                return $entityReq;

            }
        }

        throw new \Exception("Query field '$startingFieldName' not found!");
    }

    private function walk(ResolveInfo $info, EntityRequest $entityRequest, SelectionSetNode $selectionSet)
    {
        $entityDefinition = $entityRequest->getEntityDefinition();
        foreach ($selectionSet->selections as $selectionNode) {

            // This if/elseif/elseif was copied from ResolveInfo code that
            // similarly walks the tree.
            if ($selectionNode instanceof FieldNode) {
                $attributeDefinition = $entityDefinition->getAttribute($selectionNode->name->value);

                $start = null;
                $limit = null;
                foreach ($selectionNode->arguments as $argument) {
                    if ($argument->name->value === 'start') {
                        $start = $argument->value->value;
                    }
                    if ($argument->name->value === 'limit') {
                        $limit = $argument->value->value;
                    }
                }

                $nestedEntityReq = null;
                if (!$attributeDefinition->isScalar()) {
                    $attributeEntityDefinition = $this->entityManager->getEntityDefinition($attributeDefinition->getTypeName());
                    $nestedEntityReq = new EntityRequest($attributeEntityDefinition);
                    $this->walk($info, $nestedEntityReq, $selectionNode->selectionSet);
                }
                $entityRequest->addAttribute($attributeDefinition, $nestedEntityReq, $start, $limit);
            } else if ($selectionNode instanceof FragmentSpreadNode) {
                // TODO: untested
                $spreadName = $selectionNode->name->value;
                if (isset($info->fragments[$spreadName])) {
                    /** @var FragmentDefinitionNode $fragment */
                    $fragment = $info->fragments[$spreadName];
                    $this->walk($info, $entityRequest, $fragment->selectionSet);
                }
            } else if ($selectionNode instanceof InlineFragmentNode) {
                // TODO: Untested
                $this->walk($info, $entityRequest, $selectionNode->selectionSet);
            }
        }
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
