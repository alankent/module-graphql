<?php

namespace AlanKent\GraphQL\Persistence;

use AlanKent\GraphQL\Persistence\AttributeDefinition as AD;
use AlanKent\GraphQL\Persistence\EntityDefinition as ED;


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

    /** @var \AlanKent\GraphQL\Persistence\EntityAttributeDiscovery */
    private $discovery;

    private $schemas;

    /**
     * Constructor.
     * @param \AlanKent\GraphQL\Persistence\EntityFactory $entityFactory
     * @param \AlanKent\GraphQL\Persistence\EntityAttributeDiscovery $discovery
     */
    public function __construct(
        \AlanKent\GraphQL\Persistence\EntityFactory $entityFactory,
        \AlanKent\GraphQL\Persistence\EntityAttributeDiscovery $discovery
    ) {
        $this->entityFactory = $entityFactory;
        $this->discovery = $discovery;

        // TODO: Hard coded for now.
        $this->schemas = [];
        foreach ([
            ED::make('Customer', 'Customer entity.', [
                AD::make('id', 'Customer id', 'ID', false, false),
                AD::make('name', 'Customer name', 'String', false, false),
                AD::make('email', 'Customer email address', 'String', false, false),
                AD::make('addresses', 'Customer addresses', 'Address', true, false),
                AD::make('quotes', 'Quotes for this customer', 'Quote', true, false),
                AD::make('wishlists', 'Wishlists for this customer', 'Wishlist', true, false),
            ]),
            ED::make('Address', 'Address entity.', [
                AD::make('id', 'Address id', 'ID', false, false),
                AD::make('country', 'Country', 'String', false, false),
                AD::make('city', 'City', 'String', false, false),
                AD::make('street', 'Street', 'String', false, false),
                AD::make('zip', 'Street', 'String', false, false),
            ]),
            ED::make('Quote', 'Quote entity.', [
                AD::make('id', 'Quote id', 'ID', false, false),
                AD::make('name', 'Quote name', 'String', false, false),
                AD::make('items', 'Items in quote', 'QuoteItem', true, false),
                AD::make('isDefault', 'True if the default', 'Boolean', false, false),
            ]),
            ED::make('QuoteItem', 'Quote item entity.', [
                AD::make('id', 'Quote id', 'ID', false, false),
                AD::make('product', 'Product added to quote', 'Product', false, false),
                AD::make('qty', 'Quantity of item', 'Float', false, false),
                AD::make('options', 'Product options for item in quote', 'ProductOption', true, false),
            ]),
            ED::make('Wishlist', 'Wishlist entity.', [
                AD::make('id', 'Wishlist id', 'ID', false, false),
                AD::make('name', 'Wishlist name', 'String', false, false),
                AD::make('items', 'Items in wishlist', 'WishlistItem', true, false),
                AD::make('isDefault', 'True if the default', 'Boolean', false, false),
            ]),
            ED::make('WishlistItem', 'Wishlist item entity.', [
                AD::make('id', 'Wishlist item id', 'ID', false, false),
                AD::make('product', 'Product added to wishlist', 'Product', false, true),
                AD::make('qty', 'Quantity of item', 'Float', false, false),
                AD::make('options', 'Product options for item in wishlist', 'ProductOption', true, false),
            ]),
            ED::make('Order', 'Order entity.', [
                AD::make('id', 'Order id', 'ID', false, false),
                AD::make('items', 'Items in wishlist', 'OrderItem', true, false),
                AD::make('billingAddress', 'Billing address', 'Address', false, true),
                AD::make('shippingAddress', 'Shipping address', 'Address', false, true),
                AD::make('shippingMethod', 'Shipping method', 'String', false, false),
                AD::make('paymentInfo', 'Payment information', 'PaymentInfo', false, true, false),
            ]),
            ED::make('OrderItem', 'Order item entity.', [
                AD::make('id', 'Order item id', 'ID', false, false),
                AD::make('product', 'Product added to Order', 'Product', false, true),
                AD::make('qty', 'Quantity of item', 'Float', false, false),
                AD::make('options', 'Product options for item in order', 'ProductOption', true, false),
            ]),
            ED::make('PaymentInfo', 'Order item entity.', [
                AD::make('id', 'Payment info id', 'ID', false, false),
                AD::make('paymentMethodCode', 'Payment method', 'String', false, false),
                AD::make('amount', 'Payment amount', 'Float', false, false),
            ]),
            ED::make('Return', 'Order item entity.', [
                AD::make('id', 'Return id', 'ID', false, false),
                AD::make('orderId', 'Order that was returned', 'String', false, false),
                AD::make('items', 'Returned items', 'OrderItem', true, false),
            ]),
            ED::make('ProductOption', 'Product option.', [
                AD::make('attribute', 'Product attribute.', 'String', false, false),
                AD::make('value', 'Value selected when product was ordered', 'String', false, false),
            ]),
            ED::make('Comment', 'Product option.', [
                AD::make('author', 'Author of comment.', 'String', false, false),
                AD::make('text', 'Comment text.', 'String', false, false),
            ]),
//             ED::make('Product', 'Product.', [
//                 AD::make('id', 'Product id', 'ID', false, false),
//                 AD::make('sku', 'SKU', 'String', false, false),
//                 AD::make('description', 'Product description.', 'String', false, false),
//             ]),
        ] as $entityDef) {
            $this->schemas[$entityDef->getName()] = $entityDef;
        }

        $fields = [];
        foreach ($this->discovery->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class) as $name => $type) {
            $fields[] = AD::make($name, $name, $type, false, true);
        }
        $this->schemas['Product'] = ED::make('Product', 'Product.', $fields);

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
    public function getEntity(EntityRequest $request, $dataEntity): Entity
    {
        $entityDef = $request->getEntityDefinition();
//TODO        return $this->entityFactory->create($name, $entitySchema, $dataEntity);
        return new Entity($entityDef, $dataEntity);
    }

    /**
     * Return the entity definition for the specified entity name.
     * @param $name The entity name being looked for.
     * @return EntityDefinition|null The entity definition, or null if not found.
     */
    public function getEntityDefinition($name)
    {
        return isset($this->schemas[$name]) ? $this->schemas[$name] : null;
    }
}
