<?php

namespace AlanKent\GraphQL\Persistence;


/**
 * (Not in use - this is some sample code to work out what attributes exist.
 * Eventually the entity manager should work out all the attributes defined
 * automatically using code like this. For now, the list of attributes is hard
 * coded into the EntityManager implementation.
 */
class EntityAttributeDiscovery
{
    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\Config;
     */
    private $extensionAttrConfig;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $entityMetadataPool;

    /**
     * @var \Magento\Framework\Reflection\MethodsMap
     */
    private $classMethodMap;

    /**
     * @var \Magento\Framework\Reflection\FieldNamer
     */
    private $fieldNameResolver;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $customAttrRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * ObjectMetadata constructor.
     * @param \Magento\Framework\Api\ExtensionAttribute\Config $extensionAttrConfig
     * @param \Magento\Framework\EntityManager\MetadataPool $entityMetadataPool
     * @param \Magento\Framework\Reflection\MethodsMap $classMethodMap
     * @param \Magento\Framework\Reflection\FieldNamer $fieldNameResolver
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $customAttrRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        \Magento\Framework\Api\ExtensionAttribute\Config $extensionAttrConfig,
        \Magento\Framework\EntityManager\MetadataPool $entityMetadataPool,
        \Magento\Framework\Reflection\MethodsMap $classMethodMap,
        \Magento\Framework\Reflection\FieldNamer $fieldNameResolver,
        \Magento\Eav\Api\AttributeRepositoryInterface $customAttrRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
   ) {
        $this->extensionAttrConfig = $extensionAttrConfig;
        $this->entityMetadataPool = $entityMetadataPool;
        $this->classMethodMap = $classMethodMap;
        $this->fieldNameResolver = $fieldNameResolver;
        $this->customAttrRepository = $customAttrRepository;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * Retrieve entity field metadata by interface name.
     *
     * Return value:
     * [
     *   'fieldName1' => 'FieldType',
     *   'fieldName2' => 'FieldType'
     * ]
     *
     * @param string $dataInterfaceName
     * @return array
     */
    public function getMetadata($dataInterfaceName)
    {
        // TODO: Still a work in progress. Needs to return additional metadata such as "can this be used in a filter?"

        $fields = [];

        // TODO: Does this pick up too much? But needed to pick up the 'id' field. However you cannot query on these ones!
        $methodsToSkip = ['getCustomAttribute', 'getCustomAttributes'];
        foreach ($this->classMethodMap->getMethodsMap($dataInterfaceName) as $methodName => $methodMetadata) {
            if (in_array($methodName, $methodsToSkip)) {
                continue;
            }
            $field = $this->fieldNameResolver->getFieldNameForMethodName($methodName);
            if ($field) {
                $fields[$field] = $methodMetadata['type'];
            }
        }

//        $extensionAttrs = $this->extensionAttrConfig->get($dataInterfaceName);
//        if ($extensionAttrs !== null) {
//            foreach ($extensionAttrs as $extensionAttrName => $extensionAttr) {
//                $fields[$extensionAttrName] = $extensionAttr['type'];
//            }
//        }

        // Work out custom attributes.  TODO: Actually, I think all attributes may be in the table, not only custom attributes.
        if ($this->entityMetadataPool->hasConfiguration($dataInterfaceName)) {
            $eavEntityType = $this->entityMetadataPool->getMetadata($dataInterfaceName)->getEavEntityType();
            if ($eavEntityType) {
                $searchCriteria = $this->criteriaBuilder->create();
                $searchResult = $this->customAttrRepository->getList($eavEntityType, $searchCriteria);
                foreach ($searchResult->getItems() as $customAttr) {
                    $fields[$customAttr->getAttributeCode()] = $customAttr->getBackendType();
                }
            }
        }

        $fields2 = [];
        foreach ($fields as $fieldName => $fieldType) {
            // TODO: This table is not complete - attributes with unknown types are just dropped.
            if ($fieldType === 'int') $fields2[$fieldName] = 'Int';
            if ($fieldType === 'decimal') $fields2[$fieldName] = 'Float';
            if ($fieldType === 'varchar') $fields2[$fieldName] = 'String';
            if ($fieldType === 'text') $fields2[$fieldName] = 'String';
            if ($fieldType === 'datetime') $fields2[$fieldName] = 'String';
            if ($fieldType === 'static') $fields2[$fieldName] = 'String';
//            if ($fieldType === 'int[]') $fields2[$fieldName] = '[Int!]';
//            'extension_attributes' => string '\Magento\Catalog\Api\Data\ProductExtensionInterface' (length=51)
//  'product_links' => string '\Magento\Catalog\Api\Data\ProductLinkInterface[]' (length=48)
//  'options' => string '\Magento\Catalog\Api\Data\ProductCustomOptionInterface[]' (length=56)
//  'media_gallery_entries' => string '\Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface[]' (length=70)
//  'tier_prices' => string '\Magento\Catalog\Api\Data\ProductTierPriceInterface[]' (length=53)
//  'website_ids' => string 'int[]' (length=5)
//  'category_links' => string 'Magento\Catalog\Api\Data\CategoryLinkInterface[]' (length=48)
//  'bundle_product_options' => string 'Magento\Bundle\Api\Data\OptionInterface[]' (length=41)
//  'stock_item' => string 'Magento\CatalogInventory\Api\Data\StockItemInterface' (length=52)
//  'downloadable_product_links' => string 'Magento\Downloadable\Api\Data\LinkInterface[]' (length=45)
//  'downloadable_product_samples' => string 'Magento\Downloadable\Api\Data\SampleInterface[]' (length=47)
//  'configurable_product_options' => string 'Magento\ConfigurableProduct\Api\Data\OptionInterface[]' (length=54)
        }
//        var_dump($fields);
//        var_dump($fields2);
        return $fields2;
    }
}
