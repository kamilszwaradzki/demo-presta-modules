<?php

class StockMovement extends ObjectModel
{
    public $id_movement;
    public $id_warehouse_from;
    public $id_warehouse_to;
    public $id_product;
    public $quantity;
    public $movement_type;
    public $reference;
    public $note;
    public $date_add;

    public static $definition = [
        'table' => 'mwi_stock_movement',
        'primary' => 'id_movement',
        'fields' => [
            'id_warehouse_from' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_warehouse_to' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'quantity' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'movement_type' => ['type' => self::TYPE_STRING, 'required' => true],
            'reference' => ['type' => self::TYPE_STRING, 'size' => 50],
            'note' => ['type' => self::TYPE_STRING],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    public function getProduct()
    {
        return new Product($this->id_product, false, Context::getContext()->language->id);
    }

    public function getWarehouseFrom()
    {
        return $this->id_warehouse_from ? new Warehouse($this->id_warehouse_from) : null;
    }

    public function getWarehouseTo()
    {
        return $this->id_warehouse_to ? new Warehouse($this->id_warehouse_to) : null;
    }

    public static function getByWarehouse($id_warehouse, $limit = 100)
    {
        $results = Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'mwi_stock_movement` 
            WHERE id_warehouse_from = '.(int)$id_warehouse.' 
            OR id_warehouse_to = '.(int)$id_warehouse.' 
            ORDER BY date_add DESC 
            LIMIT '.(int)$limit
        );

        $movements = [];
        foreach ($results as $row) {
            $mv = new self();
            $mv->hydrate($row);
            $movements[] = $mv;
        }
        return $movements;
    }

    public static function getByProduct($id_product, $limit = 50)
    {
        $results = Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'mwi_stock_movement` 
            WHERE id_product = '.(int)$id_product.' 
            ORDER BY date_add DESC 
            LIMIT '.(int)$limit
        );

        $movements = [];
        foreach ($results as $row) {
            $mv = new self();
            $mv->hydrate($row);
            $movements[] = $mv;
        }
        return $movements;
    }
}