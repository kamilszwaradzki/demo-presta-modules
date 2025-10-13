<?php

class BundleItem extends ObjectModel
{
    public $id_bundle_item;
    public $id_bundle;
    public $id_product;
    public $quantity;
    public $position;

    public static $definition = [
        'table' => 'product_bundle_item',
        'primary' => 'id_bundle_item',
        'fields' => [
            'id_bundle' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'id_product' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'quantity' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true
            ],
            'position' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt'
            ]
        ]
    ];

    /**
     * Get product object
     */
    public function getProduct()
    {
        return new Product($this->id_product, false, Context::getContext()->language->id);
    }

    /**
     * Get product name
     */
    public function getProductName()
    {
        $product = $this->getProduct();
        return $product->name;
    }

    /**
     * Get product price
     */
    public function getProductPrice($with_tax = true)
    {
        $product = new Product($this->id_product);
        return $product->getPrice($with_tax);
    }

    /**
     * Get total price for this item
     */
    public function getTotalPrice($with_tax = true)
    {
        return $this->getProductPrice($with_tax) * $this->quantity;
    }

    /**
     * Get all items by bundle ID
     */
    public static function getByBundleId($id_bundle)
    {
        $results = Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'product_bundle_item` 
            WHERE id_bundle = '.(int)$id_bundle.' 
            ORDER BY position ASC'
        );

        $items = [];
        foreach ($results as $row) {
            $item = new self();
            $item->hydrate($row);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Delete all items for a bundle
     */
    public static function deleteByBundleId($id_bundle)
    {
        return Db::getInstance()->delete(
            'product_bundle_item',
            'id_bundle = '.(int)$id_bundle
        );
    }

    /**
     * Update positions
     */
    public static function updatePositions($items)
    {
        $position = 0;
        foreach ($items as $id_item) {
            Db::getInstance()->update(
                'product_bundle_item',
                ['position' => $position++],
                'id_bundle_item = '.(int)$id_item
            );
        }
        return true;
    }

    /**
     * Check if product is available
     */
    public function isAvailable()
    {
        $stock = StockAvailable::getQuantityAvailableByProduct($this->id_product);
        return $stock >= $this->quantity;
    }

    /**
     * Get formatted item info
     */
    public function getFormattedInfo()
    {
        $product = $this->getProduct();
        
        return [
            'id' => $this->id_bundle_item,
            'id_product' => $this->id_product,
            'name' => $product->name,
            'quantity' => $this->quantity,
            'price' => $this->getProductPrice(),
            'total' => $this->getTotalPrice(),
            'stock' => StockAvailable::getQuantityAvailableByProduct($this->id_product),
            'available' => $this->isAvailable()
        ];
    }
}