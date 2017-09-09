<?php

namespace AlanKent\GraphQL\Persistence;

use AlanKent\GraphQL\PersistenceAttributeDefinition;
use AlanKent\GraphQL\PersistenceEntityDefinition;
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
        $this->schemas = [];
        foreach ([
            EntityDefinition::make('Customer', 'Customer entity.', [
                AttributeDefinition::makeScalar('id', 'Customer id', 'ID', false),
                AttributeDefinition::makeScalar('name', 'Customer name', 'String', false),
                AttributeDefinition::makeScalar('email', 'Customer email address', 'String', false),
                AttributeDefinition::makeEntity('addresses', 'Customer addresses', 'Address', true, false),
                AttributeDefinition::makeEntity('quotes', 'Quotes for this customer', 'Quote', true, false),
                AttributeDefinition::makeEntity('wishlists', 'Wishlists for this customer', 'Wishlist', true, false),
            ]),
            EntityDefinition::make('Address', 'Address entity.', [
                AttributeDefinition::makeScalar('id', 'Address id', 'ID', false),
                AttributeDefinition::makeScalar('country', 'Country', 'String', false),
                AttributeDefinition::makeScalar('city', 'City', 'String', false),
                AttributeDefinition::makeScalar('street', 'Street', 'String', false),
                AttributeDefinition::makeScalar('zip', 'Street', 'String', false),
            ]),
            EntityDefinition::make('Quote', 'Quote entity.', [
                AttributeDefinition::makeScalar('id', 'Quote id', 'ID', false),
                AttributeDefinition::makeScalar('name', 'Quote name', 'String', false),
                AttributeDefinition::makeEntity('items', 'Items in quote', 'QuoteItem', true, false),
                AttributeDefinition::makeScalar('isDefault', 'True if the default', 'Boolean', false),
            ]),
            EntityDefinition::make('QuoteItem', 'Quote item entity.', [
                AttributeDefinition::makeScalar('id', 'Quote id', 'ID', false),
                AttributeDefinition::makeEntity('product', 'Product added to quote', 'Product', false, false),
                AttributeDefinition::makeScalar('qty', 'Quantity of item', 'Float', false),
                AttributeDefinition::makeEntity('options', 'Product options for item in quote', 'ProductOption', true, false),
            ]),
            EntityDefinition::make('Wishlist', 'Wishlist entity.', [
                AttributeDefinition::makeScalar('id', 'Wishlist id', 'ID', false),
                AttributeDefinition::makeScalar('name', 'Wishlist name', 'String', false),
                AttributeDefinition::makeEntity('items', 'Items in wishlist', 'WishlistItem', true, false),
                AttributeDefinition::makeScalar('isDefault', 'True if the default', 'Boolean', false),
            ]),
            EntityDefinition::make('WishlistItem', 'Wishlist item entity.', [
                AttributeDefinition::makeScalar('id', 'Wishlist item id', 'ID', false),
                AttributeDefinition::makeEntity('product', 'Product added to wishlist', 'Product', false, true),
                AttributeDefinition::makeScalar('qty', 'Quantity of item', 'Float', false),
                AttributeDefinition::makeEntity('options', 'Product options for item in wishlist', 'ProductOption', true, false),
            ]),
            EntityDefinition::make('Order', 'Order entity.', [
                AttributeDefinition::makeScalar('id', 'Order id', 'ID', false),
                AttributeDefinition::makeEntity('items', 'Items in wishlist', 'OrderItem', true, false),
                AttributeDefinition::makeEntity('billingAddress', 'Billing address', 'Address', false, true),
                AttributeDefinition::makeEntity('shippingAddress', 'Shipping address', 'Address', false, true),
                AttributeDefinition::makeScalar('shippingMethod', 'Shipping method', 'String', false),
                AttributeDefinition::makeEntity('paymentInfo', 'Payment information', 'PaymentInfo', true, false),
            ]),
            EntityDefinition::make('OrderItem', 'Order item entity.', [
                AttributeDefinition::makeScalar('id', 'Order item id', 'ID', false),
                AttributeDefinition::makeEntity('product', 'Product added to Order', 'Product', false, true),
                AttributeDefinition::makeScalar('qty', 'Quantity of item', 'Float', false),
                AttributeDefinition::makeEntity('options', 'Product options for item in order', 'ProductOption', true, false),
            ]),
            EntityDefinition::make('PaymentInfo', 'Order item entity.', [
                AttributeDefinition::makeScalar('id', 'Payment info id', 'ID', false),
                AttributeDefinition::makeScalar('paymentMethodCode', 'Payment method', 'String', false),
                AttributeDefinition::makeScalar('amount', 'Payment amount', 'Float', false),
            ]),
            EntityDefinition::make('Return', 'Order item entity.', [
                AttributeDefinition::makeScalar('id', 'Return id', 'ID', false),
                AttributeDefinition::makeScalar('orderId', 'Order that was returned', 'String', false),
                AttributeDefinition::makeEntity('items', 'Returned items', 'OrderItem', true, false),
            ]),
            EntityDefinition::make('Product', 'Product.', [
                AttributeDefinition::makeScalar('id', 'Product id', 'ID', false),
                AttributeDefinition::makeScalar('sku', 'SKU', 'String', false),
                AttributeDefinition::makeScalar('description', 'Product description.', 'String', false),
            ]),
            EntityDefinition::make('ProductOption', 'Product option.', [
                AttributeDefinition::makeScalar('attribute', 'Product attribute.', 'String', false),
                AttributeDefinition::makeScalar('value', 'Value selected when product was ordered', 'String', false),
            ]),
            EntityDefinition::make('Comment', 'Product option.', [
                AttributeDefinition::makeScalar('author', 'Author of comment.', 'String', false),
                AttributeDefinition::makeScalar('text', 'Comment text.', 'String', false),
            ]),
        ] as $entityDef) {
            $this->schemas[$entityDef->getName()] = $entityDef;
        }

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

//        ];
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
        $entityDefinition = $this->schemas[$name];
//TODO        return $this->entityFactory->create($name, $entitySchema, $dataEntity);
        return new Entity($name, $entityDefinition, $dataEntity);
    }

    public function getEntityDefinition($name): EntityDefinition
    {
        return isset($this->schemas[$name]) ? $this->schemas[$name] : null;
    }
}
