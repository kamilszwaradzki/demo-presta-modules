<?php
class LoyaltyHistory extends ObjectModel
{
    public $id_history;
    public $id_customer;
    public $points;
    public $action_type;
    public $id_order;
    public $date_add;

    public static $definition = [
        'table' => 'loyalty_history',
        'primary' => 'id_history',
        'fields' => [
            'id_customer'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'points'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'action_type'  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'id_order'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'date_add'     => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
