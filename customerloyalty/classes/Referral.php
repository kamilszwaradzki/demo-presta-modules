<?php
class Referral extends ObjectModel
{
    public $id_referral;
    public $id_customer_referrer;
    public $id_customer_referred;
    public $status;
    public $date_add;

    public static $definition = [
        'table' => 'loyalty_referrals',
        'primary' => 'id_referral',
        'fields' => [
            'id_customer_referrer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer_referred' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'status'               => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'date_add'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
