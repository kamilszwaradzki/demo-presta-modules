<?php

class Warehouse extends ObjectModel
{
    public $id_warehouse;
    public $name;
    public $address;
    public $city;
    public $postcode;
    public $country;
    public $phone;
    public $priority;
    public $active;
    public $latitude;
    public $longitude;
    public $date_add;

    public static $definition = [
        'table' => 'mwi_warehouse',
        'primary' => 'id_warehouse',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'address' => ['type' => self::TYPE_STRING],
            'city' => ['type' => self::TYPE_STRING, 'size' => 100],
            'postcode' => ['type' => self::TYPE_STRING, 'size' => 20],
            'country' => ['type' => self::TYPE_STRING, 'size' => 100],
            'phone' => ['type' => self::TYPE_STRING, 'size' => 50],
            'priority' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'latitude' => ['type' => self::TYPE_FLOAT],
            'longitude' => ['type' => self::TYPE_FLOAT],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    public function getStock($id_product)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'mwi_stock` 
            WHERE id_warehouse = '.(int)$this->id.' 
            AND id_product = '.(int)$id_product
        );
    }

    public function getAllStock()
    {
        return Db::getInstance()->executeS(
            'SELECT s.*, pl.name 
            FROM `'._DB_PREFIX_.'mwi_stock` s
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (s.id_product = pl.id_product)
            WHERE s.id_warehouse = '.(int)$this->id.'
            AND pl.id_lang = '.(int)Context::getContext()->language->id
        );
    }

    public function getTotalValue()
    {
        $stock = $this->getAllStock();
        $total = 0;

        foreach ($stock as $item) {
            $product = new Product($item['id_product']);
            $total += $product->getPrice() * $item['quantity'];
        }

        return $total;
    }

    public static function getActiveWarehouses()
    {
        $results = Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'mwi_warehouse` 
            WHERE active = 1 
            ORDER BY priority DESC'
        );

        $warehouses = [];
        foreach ($results as $row) {
            $wh = new self();
            $wh->hydrate($row);
            $warehouses[] = $wh;
        }
        return $warehouses;
    }
}