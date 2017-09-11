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

    //TODO: Why is FilterBuilder in Api but FilterGroupBuilder in Search\Api?
    /** @var \Magento\Framework\Api\FilterBuilder */
    private $filterBuilder;

    /** @var \Magento\Framework\Api\Search\FilterGroupBuilder */
    private $filterGroupBuilder;

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
        \Magento\Framework\Api\SearchCriteriaInterfaceFactory $searchCriteriaFactory,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->entityManager = $entityManager;
        $this->typeRegistry = $typeRegistry;
        $this->searchCriteriaFactory = $searchCriteriaFactory;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;

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
                        'filter' => [
                            'type' => $this->typeRegistry->makeFilterType('Product'),
                            'description' => 'Only return products matching these filter conditions'
                        ],
                        'start' => [
                            'type' => Type::int(),
                            'description' => 'Index of first product to return (default is 0).'
                        ],
                        'limit' => [
                            'type' => Type::int(),
                            'description' => 'How many to try and return (default is all)'
                        ],
    //                        'storeId' => [
    //                            'type' => Type::int(),
    //                            'description' => 'The store view id, or omitted for the default store view.',
    //                            //'defaultValue' => 0  // Default should be null? Is null same as zero?
    //                        ]
                    ],
                    'resolve' => function($val, $args, $context, $info) {

                        $req = $this->parseRequest($info);

                        // Lazy load at runtime.
                        /** @var Context $context */
                        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $sc */
                        $sc = $context->getServiceContract(\Magento\Catalog\Api\ProductRepositoryInterface::class);

//                        $storeId = isset($args['storeId']) ? $args['storeId'] : null;

                        $pageSize = 100;
                        $start = 0;
                        if (isset($args['start'])) {
                            $start = $args['start'];
                        }
                        $limit = null;
                        if (isset($args['limit'])) {
                            $limit = $args['limit'];
                            $pageSize = $start + $limit;
                        }
                        $searchCriteria = $this->searchCriteriaFactory->create();
                        if (isset($args['filter'])) {
                            $filterGroups = $this->convertToFilterGroups($args['filter']);
                            $searchCriteria->setFilterGroups($filterGroups);
                        }
                        // TODO: CANNOT DO OFFSET AND COUNT CORRECTLY! $searchCriteria->setPageCount($count);
                        $searchCriteria->setPageSize($pageSize);
                        $searchCriteria->setCurrentPage(0);
                        $items = $sc->getList($searchCriteria)->getItems();
                        $val = [];
                        foreach ($items as $item) {
                            if ($start-- > 0) {
                                continue;
                            }
                            if ($limit !== null && $limit-- <= 0) {
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

        parent::__construct($config);
    }

    private function parseRequest(ResolveInfo $info): EntityRequest
    {
        // TODO: Path is an array, should navigate from root down full path, not just take last name in path.
        $startingFieldName = $info->path[count($info->path) - 1];
        $entityName = (string)$info->returnType;

        // Type could be "[Customer!]!" - strip all the decorations.
        if (substr($entityName, -1) == '!') {
            $entityName = substr($entityName, 0, -1);
        }
        if (substr($entityName, -1) == ']') {
            $entityName = substr($entityName, 1, -1);
        }
        if (substr($entityName, -1) == '!') {
            $entityName = substr($entityName, 0, -1);
        }

        // We need to find the selection node for the current query field.
        foreach ($info->fieldNodes as $fieldNode) {
            $fieldName = $fieldNode->name->value;
            if ($fieldName === $startingFieldName) {

                // We found the current field. Now walk the tree of field requests,
                // building up the EntityRequest object.
                $entity = $this->entityManager->getEntityDefinition($entityName);
                if ($entity == null) {
                    throw new \Exception("Field '$fieldName' could not find entity '$entityName'.");
                }
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

    /**
     * Convert GraphQL input type for filters to Magento search criteria
     * filter groups.
     * TODO: This should move to separate class
     * @return \Magento\Framework\Api\Search\FilterGroup[]
     */
    private function convertToFilterGroups($filter)
    {
        // Build up an AND/OR tree of filter conditions.
        $ast = $this->parseFilters($filter);

        // Convert to single AND above OR nodes (filter groups above filters),
        // reorganizating the tree as required.
        $ast = $this->normalizeFilterTree($ast);

        // Convert tree to array of filter groups above arrays of filters.
        $ast = $this->convertAstToFilterGroups($ast);

        return $ast;
    }

    /**
     * Parse the data structure listing all attribute contraints and convert
     * into an AND/OR tree above Filter instances.
     * @param $filter
     * @return array
     */
    private function parseFilters($filter)
    {
        $op = 'AND';
        $children = [];
        foreach ($filter as $name => $value) {
            if ($name === '_children') {
                foreach ($value as $child) {
                    $children[] = $this->parseFilters($child);
                }
            } else if ($name === '_join') {
                $op = ($value == 'ANY') ? 'OR' : 'AND';
            } else {
                $children[] = $this->parseConstraint($name, $value);
            }
        }
        return [ 'operator' => $op, 'children' => $children ];
    }

    /**
     * Parse {eq:3} or {like:"%06"} and convert to a Filter
     * @param $name
     * @param $value
     * @return \Magento\Framework\Api\Filter
     * @throws \Exception
     */
    private function parseConstraint($name, $value): \Magento\Framework\Api\Filter
    {
        if (count($value) == 2) {
            throw new \Exception("TODO: from/to filter parsing not implemented yet.");
        }
        //TODO: No checking for entity reference attributes - this code assumes attributes are all atomic.
        if (count($value) != 1) {
            throw new \Exception("Only one condition can be specified at a time in filter constraint.");
        }
        foreach ($value as $n => $v) {
            return $this->filterBuilder
                ->setField($name)
                ->setValue($v)
                ->setConditionType($n)
                ->create();
        }
        return null; // Quieten warnings, should never be reached
    }

    /**
     * Normalize the filter tree so we end up with AND above OR nodes.
     * @param $ast
     * @return array
     * @throws \Exception
     */
    private function normalizeFilterTree($ast)
    {
        // TODO: This needs to convert "(A AND B) OR C" to "(A OR C) AND (B OR C)"
        // TODO: For now, just inject extra AND or OR node if missing, and give up on complicated queries.

        $checkAgain = true;
        while ($checkAgain) {
            $checkAgain = false;

            // Insert AND node at root of tree if required.
            if (!is_array($ast) || $ast['operator'] !== 'AND') {
                $ast = ['operator' => 'AND', 'children' => [$ast]];
                $checkAgain = true;
                continue;
            }

            // If AND above AND, merge them.
            $newChildren = [];
            foreach ($ast['children'] as $idx => $andChild) {
                if (is_array($andChild) && $andChild['operator'] === 'AND') {
                    foreach ($andChild['children'] as $c) {
                        $newChildren[] = $c;
                        $checkAgain = true;
                    }
                } else {
                    $newChildren[] = $andChild;
                }
            }
            if ($checkAgain) {
                $ast['children'] = $newChildren;
                continue;
            }

            // We know we don't have AND above AND, so non-array children must be filters.
            // Wrap any nested filters in OR nodes.
            foreach ($ast['children'] as $idx => $andChild) {
                if (!is_array($andChild)) {
                    $ast['children'][$idx] = ['operator' => 'OR', 'children' => [$andChild]];
                }
            }
            // TODO: This code is not merging OR above OR yet.
            foreach ($ast['children'] as $andIndex => $andChild) {
                foreach ($andChild['children'] as $orIndex => $orChild) {
                    if (is_array($orChild)) {
                        throw new \Exception('TODO: Filter condition too complex - must be ALL above ANY only.');
                    }
                }
            }
        }
        return $ast;
    }

    /**
     * Assuming a perfect tree of AND above OR above constraints, convert to
     * Magento search criteria.
     * @param $ast
     * @return \Magento\Framework\Api\Search\FilterGroup[]
     */
    private function convertAstToFilterGroups($ast)
    {
        $filterGroups = [];
        foreach ($ast['children'] as $andNode) {
            foreach ($andNode['children'] as $orNode) {
                $this->filterGroupBuilder->addFilter($orNode);
            }
            $filterGroups[] = $this->filterGroupBuilder->create();
        }
        return $filterGroups;
    }
}
