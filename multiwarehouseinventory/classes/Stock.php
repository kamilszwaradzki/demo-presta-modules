<?php

class Stock extends ObjectModel
{
    public $id_stock;
    public $id_warehouse;
    public $id_product;
    public $quantity;
    public $reserved_quantity;
    public $min_quantity;

    public static $definition = [
        'table' => 'mwi_stock',
        'primary' => 'id_stock',
        'fields' => [
            'id_warehouse'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'quantity'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'reserved_quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'min_quantity'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
        ],
    ];
}
