<?php

namespace AlanKent\GraphQL\Persistence;

use Magento\Customer\Api\Data\CustomerInterface;


/**
 * This class provides access to all available entities.
 * It is used as a pretend entity manager until the new Magento Persistence Layer
 * is implemented. Until then, this is a shim between the current entity APIs
 * and repository interfaces and the GraphQL layer.
 */
class EntityManager
{
    /** @var \AlanKent\GraphQL\App\EntityFactory */
    private $entityFactory;

    private $schemas;

    /**
     * Constructor.
     * @param \AlanKent\GraphQL\Persistence\EntityFactory $entityFactory
     */
    public function __construct(
        \AlanKent\GraphQL\Persistence\EntityFactory $entityFactory
    ) {
        $this->entityFactory = $entityFactory;

        // TODO: Hard coded for now.
        $this->schemas = [
            'Customer' => [
                'description' => 'Customer entity.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Customer id'
                    ],
                    'name' => [
                        'type' => 'String!',
                        'description' => 'Customer id'
                    ],
                    'email' => [
                        'type' => 'String!',
                        'description' => 'Customer email address'
                    ],
                    'addresses' => [
                        'type' => '[Address!]!',
                        'description' => 'Customer addresses'
                    ],
                    'quotes' => [
                        'type' => '[Quote!]!',
                        'description' => 'Quotes for this customer'
                    ],
                    'wishlists' => [
                        'type' => '[Wishlist!]!',
                        'description' => 'Wishlists for this customer'
                    ],
                ],
            ],
            'Address' => [
                'description' => 'Address entity.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Address id'
                    ],
                    'country' => [
                        'type' => 'String!',
                        'description' => 'Country'
                    ],
                    'city' => [
                        'type' => 'String!',
                        'description' => 'City'
                    ],
                    'street' => [
                        'type' => 'String!',
                        'description' => 'Street'
                    ],
                    'zip' => [
                        'type' => 'String!',
                        'description' => 'Street'
                    ],
                ],
            ],
            'Quote' => [
                'description' => 'Quote entity.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Quote id'
                    ],
                    'name' => [
                        'type' => 'String!',
                        'description' => 'Quote name'
                    ],
                    'items' => [
                        'type' => '[QuoteItem!]!',
                        'description' => 'Items in quote',
                        'args' => [
                            'first' => [
                                'type' => 'Int!'
                            ],
                            'offset' => [
                                'type' => 'Int!'
                            ],
                        ],
                    ],
                    'isDefault' => [
                        'type' => 'Boolean!',
                        'description' => 'True if the default'
                    ],
                ],
            ],
            'QuoteItem' => [
                'description' => 'Quote item entity.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Quote id'
                    ],
                    'product' => [
                        'type' => 'Product!',
                        'description' => 'Product added to quote'
                    ],
                    'qty' => [
                        'type' => 'Float!',
                        'description' => 'Quantity of item',
                    ],
                    'options' => [
                        'type' => '[ProductOption!]',
                        'description' => 'Product options for item in quote'
                    ],
                ],
            ],
            'Wishlist' => [
                'description' => 'Wishlist entity.',
    //                'resolve' => function($name, $schema, $id) {
    //                    // TODO: Fetch CustomerInterface instance
    //                    return null;
    //                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Wishlist id'
                    ],
                    'name' => [
                        'type' => 'String!',
                        'description' => 'Wishlist name'
                    ],
                    'items' => [
                        'type' => '[WishlistItem!]!',
                        'description' => 'Items in wishlist',
                        'args' => [
                            'first' => [
                                'type' => 'Int!'
                            ],
                            'offset' => [
                                'type' => 'Int!'
                            ],
                        ],
                    ],
                    'isDefault' => [
                        'type' => 'Boolean!',
                        'description' => 'True if the default'
                    ],
                ],
            ],
            'WishlistItem' => [
                'description' => 'Wishlist item entity.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Wishlist item id'
                    ],
                    'product' => [
                        'type' => 'Product!',
                        'description' => 'Product added to wishlist'
                    ],
                    'qty' => [
                        'type' => 'Float!',
                        'description' => 'Quantity of item',
                    ],
                    'options' => [
                        'type' => '[ProductOption!]',
                        'description' => 'Product options for item in wishlist'
                    ],
                ],
            ],
            'Order' => [
                'description' => 'Order entity.',
                //                'resolve' => function($name, $schema, $id) {
                //                    // TODO: Fetch CustomerInterface instance
                //                    return null;
                //                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Order id'
                    ],
                    'items' => [
                        'type' => '[OrderItem!]!',
                        'description' => 'Items in wishlist',
                        'args' => [
                            'first' => [
                                'type' => 'Int!'
                            ],
                            'offset' => [
                                'type' => 'Int!'
                            ],
                        ],
                    ],
                    'billingAddress' => [
                        'type' => 'Address!',
                        'description' => 'Billing address'
                    ],
                    'shippingAddress' => [
                        'type' => 'Address!',
                        'description' => 'Shipping address'
                    ],
                    'shippingMethod' => [
                        'type' => 'String!',
                        'description' => 'Shipping method'
                    ],
                    'paymentInfo' => [
                        'type' => '[PaymentInfo!]!',
                        'description' => 'Payment information'
                    ],
                ],
            ],
            'OrderItem' => [
                'description' => 'Order item entity.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Order item id'
                    ],
                    'product' => [
                        'type' => 'Product!',
                        'description' => 'Product added to Order'
                    ],
                    'qty' => [
                        'type' => 'Float!',
                        'description' => 'Quantity of item',
                    ],
                    'options' => [
                        'type' => '[ProductOption!]',
                        'description' => 'Product options for item in order'
                    ],
                ],
            ],
            'PaymentInfo' => [
                'description' => 'Order item entity.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Payment info id'
                    ],
                    'paymentMethodCode' => [
                        'type' => 'String!',
                        'description' => 'Payment method'
                    ],
                    'amount' => [
                        'type' => 'Float!',
                        'description' => 'Payment amount',
                    ],
                ],
            ],
            'Return' => [
                'description' => 'Order item entity.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Return id'
                    ],
                    'orderId' => [
                        'type' => 'String!',
                        'description' => 'Order that was returned'
                    ],
                    'items' => [
                        'type' => '[OrderItem!]!',
                        'description' => 'Returned items',
                    ],
                ],
            ],
            'Product' => [
                'description' => 'Product.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'id' => [
                        'type' => 'ID!',
                        'description' => 'Product id'
                    ],
                    'sku' => [
                        'type' => 'String!',
                        'description' => 'SKU'
                    ],
                    'description' => [
                        'type' => 'String!',
                        'description' => 'Product description.',
                    ],
                ],
            ],
            'ProductOption' => [
                'description' => 'Product option.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'attribute' => [
                        'type' => 'String!',
                        'description' => 'Product attribute.'
                    ],
                    'value' => [
                        'type' => 'String!',
                        'description' => 'Value selected when product was ordered'
                    ],
                ],
            ],
            'Comment' => [
                'description' => 'Product option.',
//                'resolve' => function($name, $schema, $id) {
//                    // TODO: Fetch CustomerInterface instance
//                    return null;
//                },
                'fields' => [
                    'author' => [
                        'type' => 'String!',
                        'description' => 'Author of comment.'
                    ],
                    'text' => [
                        'type' => 'String!',
                        'description' => 'Comment text.'
                    ],
                ],
            ],

//        [
//            'id' =>             [ 'type' => 'int',     'description' => '' ],
//            'sku' =>            [ 'type' => 'string',  'description' => 'Stock Keeping Unit (SKU).' ],
//            'name' =>           [ 'type' => 'string',  'description' => '' ],
//            'attributeSetId' => [ 'type' => 'int',     'description' => '' ],
//            'price' =>          [ 'type' => 'decimal', 'description' => '' ],
//            'status' =>         [ 'type' => 'int',     'description' => '' ],
//            'visibility' =>     [ 'type' => 'int',     'description' => '' ],
//            'typeId' =>         [ 'type' => 'string',  'description' => '' ],
//            'createdAt' =>      [ 'type' => 'string',  'description' => '' ],
//            'updatedAt' =>      [ 'type' => 'string',  'description' => '' ],
//            'weight' =>         [ 'type' => 'decimal', 'description' => '' ],
//            'material' =>       [ 'type' => 'string',  'description' => '' ],
//            'cust_attr' =>      [ 'type' => 'string',  'description' => 'Demo custom attribute I added by hand' ],
//        ];

        ];
    }

    /**
     * Return a list of all supported entity names.
     */
    public function getNames(): array {
        return array_keys($this->schemas);
    }

    /**
     * Return a handle to the specified entity name, or null if the entity name is not known.
     */
    public function getEntity(string $name, $dataEntity): Entity {
        if (!isset($this->schemas[$name])) {
            throw new \Exception("Cannot create Entity for unknown type '$name'.");
        }
        $entitySchema = $this->schemas[$name];
//TODO        return $this->entityFactory->create($name, $entitySchema, $dataEntity);
        return new Entity($name, $entitySchema, $dataEntity);
    }

    public function getEntitySchema($name)
    {
        if (!isset($this->schemas[$name])) {
            return null;
        }
        $this->schemas[$name]['name'] = $name;
        return $this->schemas[$name];
    }
}
