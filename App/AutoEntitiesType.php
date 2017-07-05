<?php

namespace AlanKent\GraphQL\App;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\NonNull;

use Magento\Webapi\Model\ServiceMetadata;

/**
 * Object type for querying the avaialble entities (*RepositoryInterface service contracts).
 */
class AutoEntitiesType extends ObjectType
{
    /**
     * Constructor.
     * @param \Magento\Webapi\Model\ServiceMetadata $serviceMetadata
     */
    public function __construct(\Magento\Webapi\Model\ServiceMetadata $serviceMetadata)
    {
        $s = $serviceMetadata->getServicesConfig();
        //file_put_contents('/tmp/svc.json', print_r($s));

        $config = [
            'name' => 'AutoEntities',
            'description' => 'All repository interfaces exposed via GraphQL.',
            'fields' => [
                //'product' => $this->generateRepositorySchema($serviceMetadata->getServiceConfig('catalogProductRepositoryV1'))
            ],
        ];
        parent::__construct($config);
    }

    //private function generateRepositorySchema($service) {
    //}
}
