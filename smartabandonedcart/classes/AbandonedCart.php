<?php

class AbandonedCart extends ObjectModel
{
    public $id_abandoned_cart;
    public $id_cart;
    public $id_customer;
    public $email;
    public $cart_total;
    public $abandoned_date;
    public $reminder_count;
    public $recovered;
    public $recovery_date;

    public static $definition = [
        'table' => 'abandoned_cart',
        'primary' => 'id_abandoned_cart',
        'fields' => [
            'id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 255],
            'cart_total' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'abandoned_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'reminder_count' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'recovered' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'recovery_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];

    public function getCustomer()
    {
        return new Customer($this->id_customer);
    }

    public function getCart()
    {
        return new Cart($this->id_cart);
    }

    public function getProducts()
    {
        $cart = $this->getCart();
        return $cart->getProducts();
    }

    public static function getByCartId($id_cart)
    {
        $id = Db::getInstance()->getValue(
            'SELECT id_abandoned_cart FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE id_cart = '.(int)$id_cart
        );
        return $id ? new self($id) : false;
    }

    public static function getPendingCarts($limit = 100)
    {
        $results = Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE recovered = 0 
            ORDER BY abandoned_date DESC 
            LIMIT '.(int)$limit
        );

        $carts = [];
        foreach ($results as $row) {
            $cart = new self();
            $cart->hydrate($row);
            $carts[] = $cart;
        }
        return $carts;
    }

    public function markAsRecovered()
    {
        $this->recovered = 1;
        $this->recovery_date = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function incrementReminderCount()
    {
        $this->reminder_count++;
        return $this->save();
    }
}