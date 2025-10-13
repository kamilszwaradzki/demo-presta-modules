<?php

class Bundle extends ObjectModel
{
    public $id_bundle;
    public $id_product;
    public $discount_type;
    public $discount_value;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'product_bundle',
        'primary' => 'id_bundle',
        'fields' => [
            'id_product' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'discount_type' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'values' => ['percentage', 'fixed'],
                'required' => true
            ],
            'discount_value' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => true
            ],
            'active' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool'
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ]
        ]
    ];

    public function getProduct()
    {
        return new Product($this->id_product, false, Context::getContext()->language->id);
    }

    public function getItems()
    {
        return BundleItem::getByBundleId($this->id_bundle);
    }

    public function calculatePrice($with_tax = true)
    {
        $items = $this->getItems();
        $total = 0;

        foreach ($items as $item) {
            $product = new Product($item->id_product);
            $total += $product->getPrice($with_tax) * $item->quantity;
        }

        if ($this->discount_type == 'percentage') {
            $total = $total * (1 - $this->discount_value / 100);
        } else {
            $total = $total - $this->discount_value;
        }

        return max(0, $total);
    }

    public function getOriginalPrice($with_tax = true)
    {
        $items = $this->getItems();
        $total = 0;

        foreach ($items as $item) {
            $product = new Product($item->id_product);
            $total += $product->getPrice($with_tax) * $item->quantity;
        }

        return $total;
    }

    public function getSavings($with_tax = true)
    {
        return $this->getOriginalPrice($with_tax) - $this->calculatePrice($with_tax);
    }

    public function hasStock($quantity = 1)
    {
        $items = $this->getItems();

        foreach ($items as $item) {
            $stock = StockAvailable::getQuantityAvailableByProduct($item->id_product);
            
            if ($stock < ($item->quantity * $quantity)) {
                return false;
            }
        }

        return true;
    }

    public static function getByProductId($id_product)
    {
        $id_bundle = Db::getInstance()->getValue(
            'SELECT id_bundle FROM `'._DB_PREFIX_.'product_bundle` 
            WHERE id_product = '.(int)$id_product
        );

        return $id_bundle ? new self($id_bundle) : false;
    }

    public static function getActiveBundles()
    {
        $results = Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'product_bundle` 
            WHERE active = 1 
            ORDER BY date_add DESC'
        );

        $bundles = [];
        foreach ($results as $row) {
            $bundle = new self();
            $bundle->hydrate($row);
            $bundles[] = $bundle;
        }

        return $bundles;
    }

    public static function getStatistics($days = 30)
    {
        // TODO: add fetch product_bundle by order's date
        $date_from = date('Y-m-d', strtotime('-'.$days.' days'));

        $stats = [];

    
        $stats['total_bundles'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'product_bundle` WHERE active = 1'
        );

        $stats['total_sales'] = 0;
        $stats['total_revenue'] = 0;

        return $stats;
    }

    public function delete()
    {
        Db::getInstance()->delete(
            'product_bundle_item',
            'id_bundle = '.(int)$this->id_bundle
        );

        return parent::delete();
    }
}