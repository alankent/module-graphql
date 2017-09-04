<?php

namespace AlanKent\GraphQL\App;


/**
 * (Not in use - this is some sample code to work out what attributes exist.
 * Eventually the entity manager should work out all the attributes defined
 * automatically using code like this. For now, the list of attributes is hard
 * coded into the EntityManager implementation.
 */
class ObjectMetadata
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
        $fields = [
            'core_attributes' => [],
            'extension_attributes' => [],
            'custom_attributes' => []
        ];

        $methodsToSkip = ['getCustomAttribute', 'getCustomAttributes'];
        foreach ($this->classMethodMap->getMethodsMap($dataInterfaceName) as $methodName => $methodMetadata) {
            if (in_array($methodName, $methodsToSkip)) {
                continue;
            }
            $field = $this->fieldNameResolver->getFieldNameForMethodName($methodName);
            if ($field) {
                $fields['core_attributes'][$field] = $methodMetadata['type'];
            }
        }
        $extensionAttrs = $this->extensionAttrConfig->get($dataInterfaceName);
        if ($extensionAttrs !== null) {
            foreach ($extensionAttrs as $extensionAttrName => $extensionAttr) {
                $fields['extension_attributes'][$extensionAttrName] = $extensionAttr['type'];
            }
        }

        if ($this->entityMetadataPool->hasConfiguration($dataInterfaceName)) {
            $eavEntityType = $this->entityMetadataPool->getMetadata($dataInterfaceName)->getEavEntityType();
            if ($eavEntityType) {
                $searchCriteria = $this->criteriaBuilder->create();
                $searchResult = $this->customAttrRepository->getList($eavEntityType, $searchCriteria);
                foreach ($searchResult->getItems() as $customAttr) {
                    $fields['custom_attributes'][$customAttr->getAttributeCode()] = $customAttr->getBackendType();
                }
            }
        }
        return $fields;
    }
}
