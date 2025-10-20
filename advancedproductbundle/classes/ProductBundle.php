<?php

class ProductBundle extends ObjectModel
{
    public $id_bundle;
    public $name;
    public $discount_type;
    public $discount_value;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'product_bundle',
        'primary' => 'id_bundle',
        'fields' => [
            'name' => [
                'type' => self::TYPE_STRING, 
                'validate' => 'isGenericName', 
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

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
    }

    public function add($auto_date = true, $null_values = false)
    {
        $return = parent::add($auto_date, $null_values);
        
        if ($return) {
            $this->saveBundleProductsAfterAdd();
        }
        
        return $return;
    }

    public function update($null_values = false)
    {
        $return = parent::update($null_values);
        
        if ($return) {
            $this->saveBundleProductsAfterUpdate();
        }
        
        return $return;
    }

    protected function saveBundleProductsAfterAdd()
    {
        $bundle_products = Tools::getValue('bundle_products');
        
        if (!empty($bundle_products) && is_array($bundle_products)) {
            $position = 0;
            foreach ($bundle_products as $product) {
                $position++;
                $id_product = (int)$product['id_product'];
                if ($id_product > 0) {
                    Db::getInstance()->insert('product_bundle_item', [
                        'id_bundle' => (int)$this->id,
                        'id_product' => $id_product,
                        'quantity' => $product['quantity'],
                        'position' => (int)$position
                    ]);
                }
            }
        }
    }

    protected function saveBundleProductsAfterUpdate()
    {
        $this->saveBundleProductsAfterAdd();
    }

    public function getItems()
    {
        if (!class_exists('BundleItem')) {
            require_once _PS_MODULE_DIR_ . 'advancedproductbundle/classes/BundleItem.php';
        }
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

    public static function getByProductId($id_product)
    {
        $id_bundle = Db::getInstance()->getValue(
            'SELECT pb.id_bundle FROM `'._DB_PREFIX_.'product_bundle` pb
            INNER JOIN `'._DB_PREFIX_.'product_bundle_item` pbi on pb.id_bundle = pbi.id_bundle
            WHERE id_product = '.(int)$id_product
        );

        return $id_bundle ? new self($id_bundle) : false;
    }
}