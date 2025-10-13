<?php
class LoyaltyPoints extends ObjectModel
{
    public $id_loyalty;
    public $id_customer;
    public $points;
    public $points_spent;
    public $membership_level;
    public $referral_code;

    public static $definition = [
        'table' => 'loyalty_points',
        'primary' => 'id_loyalty',
        'fields' => [
            'id_customer'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'points'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'points_spent'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'membership_level' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'referral_code'    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
        ],
    ];

    public function getByCustomer($id_customer)
    {
        $row = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'loyalty_points` WHERE `id_customer`='.(int)$id_customer);
        if ($row) {
            foreach ($row as $key => $value) {
                $this->{$key} = $value;
            }
        }
        return $this;
    }
}
