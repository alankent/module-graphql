<?php

namespace AlanKent\GraphQL\App;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class ProductType extends ObjectType
{
    /**
     * Constructor.
     * @param \AlanKent\GraphQL\App\EntityManager $entityManager
     */
    public function __construct(\AlanKent\GraphQL\App\EntityManager $entityManager)
    {
        $entity = $entityManager->getEntity('Product');

        $fields = [];
        foreach ($entity->getSchema() as $attributeName => $fieldType) {
            $fields[$attributeName] = [
                'type' => $this->mapType($fieldType['type']),
                'description' => $fieldType['description'],
            ];
        }

        $config = [
            'name' => 'Product',
            'fields' => $fields,
            'resolveField' => function($val, $args, $context, ResolveInfo $info) use ($entity) {
                return $entity->getAttribute($val, $info->fieldName);
            }
        ];

        parent::__construct($config);
    }

    private function mapType($magentoType) {
        switch ($magentoType) {
            case 'int':      return Type::int();
            case 'decimal':  return Type::float();
            case 'string':   return Type::string(); 
            case 'varchar':  return Type::string(); 
            case 'text':     return Type::string();
            case 'datetime': return Type::string();
            case 'static':   return Type::string(); // TODO: STRANGE!
            //case 'int[]':
            //case '\Magento\Catalog\Api\Data\ProductExtensionInterface':
            //case '\Magento\Catalog\Api\Data\ProductLinkInterface[]':
            //case '\Magento\Catalog\Api\Data\ProductCustomOptionInterface[]':
            //case '\Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface[]':
            //case '\Magento\Catalog\Api\Data\ProductTierPriceInterface[]':
            //case 'Magento\Bundle\Api\Data\OptionInterface[]':
            //case 'Magento\Downloadable\Api\Data\LinkInterface[]':
            //case 'Magento\Downloadable\Api\Data\SampleInterface[]':
            //case 'Magento\CatalogInventory\Api\Data\StockItemInterface':
            //case 'Magento\ConfigurableProduct\Api\Data\OptionInterface[]':
        }
        return null;
    }
}

