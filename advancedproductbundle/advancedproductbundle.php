<?php
if (!defined('_PS_VERSION_')) exit;

class AdvancedProductBundle extends Module
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'advancedproductbundle';
        $this->author = 'Kamil Szwaradzki';
        $this->version = '1.0.0';
        $this->displayName = $this->l('Advanced Product Bundle Creator');
    }

    public function install()
    {
        $sql = [];
        
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_bundle` (
            `id_bundle` int(11) AUTO_INCREMENT PRIMARY KEY,
            `id_product` int(11) NOT NULL UNIQUE,
            `discount_type` enum("percentage","fixed") DEFAULT "percentage",
            `discount_value` decimal(10,2) DEFAULT 0.00,
            `active` tinyint(1) DEFAULT 1,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL
        ) ENGINE='._MYSQL_ENGINE_;

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_bundle_item` (
            `id_bundle_item` int(11) AUTO_INCREMENT PRIMARY KEY,
            `id_bundle` int(11) NOT NULL,
            `id_product` int(11) NOT NULL,
            `quantity` int(11) DEFAULT 1,
            `position` int(11) DEFAULT 0,
            KEY `id_bundle` (`id_bundle`)
        ) ENGINE='._MYSQL_ENGINE_;

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) return false;
        }

        return parent::install() &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('displayProductPriceBlock') &&
            $this->registerHook('actionValidateOrder');
    }

    public function uninstall()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'product_bundle`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'product_bundle_item`');
        return parent::uninstall();
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int)Tools::getValue('id_product');
        $bundle = $this->getBundleByProductId($id_product);
        $items = $bundle ? $this->getBundleItems($bundle['id_bundle']) : [];

        $this->context->smarty->assign([
            'bundle' => $bundle,
            'bundle_items' => $items,
            'id_product' => $id_product,
            'all_products' => Product::getSimpleProducts($this->context->language->id)
        ]);

        return $this->display(__FILE__, 'views/templates/admin/bundle_form.tpl');
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if ($params['type'] !== 'before_price') return;

        $bundle = $this->getBundleByProductId($params['product']['id_product']);
        if (!$bundle || !$bundle['active']) return;

        $items = $this->getBundleItems($bundle['id_bundle']);
        $bundle_price = $this->calculateBundlePrice($bundle['id_bundle']);
        $original_price = $this->getOriginalPrice($bundle['id_bundle']);

        $this->context->smarty->assign([
            'bundle' => $bundle,
            'bundle_items' => $items,
            'bundle_price' => $bundle_price,
            'original_price' => $original_price,
            'savings' => $original_price - $bundle_price
        ]);

        return $this->display(__FILE__, 'views/templates/hook/bundle_info.tpl');
    }

    public function hookActionValidateOrder($params)
    {
        $cart = $params['cart'];
        $products = $cart->getProducts();

        foreach ($products as $product) {
            $bundle = $this->getBundleByProductId($product['id_product']);
            
            if ($bundle) {
                $items = $this->getBundleItems($bundle['id_bundle']);
                
                foreach ($items as $item) {
                    StockAvailable::updateQuantity(
                        $item['id_product'],
                        0,
                        -($item['quantity'] * $product['quantity'])
                    );
                }
            }
        }
    }

    public function getBundleByProductId($id_product)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'product_bundle` 
            WHERE id_product = '.(int)$id_product
        );
    }

    public function getBundleItems($id_bundle)
    {
        $items = Db::getInstance()->executeS(
            'SELECT bi.*, pl.name 
            FROM `'._DB_PREFIX_.'product_bundle_item` bi
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (bi.id_product = pl.id_product)
            WHERE bi.id_bundle = '.(int)$id_bundle.'
            AND pl.id_lang = '.(int)$this->context->language->id.'
            ORDER BY bi.position ASC'
        );
        
        return $items;
    }

    public function calculateBundlePrice($id_bundle)
    {
        $bundle = Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'product_bundle` WHERE id_bundle = '.(int)$id_bundle
        );
        
        if (!$bundle) return 0;

        $items = $this->getBundleItems($id_bundle);
        $total = 0;

        foreach ($items as $item) {
            $product = new Product($item['id_product'], false, $this->context->language->id);
            $total += $product->getPrice(true) * $item['quantity'];
        }

        if ($bundle['discount_type'] == 'percentage') {
            $total = $total * (1 - $bundle['discount_value'] / 100);
        } else {
            $total = $total - $bundle['discount_value'];
        }

        return max(0, $total);
    }

    public function getOriginalPrice($id_bundle)
    {
        $items = $this->getBundleItems($id_bundle);
        $total = 0;

        foreach ($items as $item) {
            $product = new Product($item['id_product'], false, $this->context->language->id);
            $total += $product->getPrice(true) * $item['quantity'];
        }

        return $total;
    }

    public function processBundleSave()
    {
        $id_product = (int)Tools::getValue('id_product');
        $discount_type = pSQL(Tools::getValue('discount_type'));
        $discount_value = (float)Tools::getValue('discount_value');
        $active = (int)Tools::getValue('active');
        $products = Tools::getValue('bundle_products');

        $existing = $this->getBundleByProductId($id_product);

        if ($existing) {
            Db::getInstance()->update('product_bundle', [
                'discount_type' => $discount_type,
                'discount_value' => $discount_value,
                'active' => $active,
                'date_upd' => date('Y-m-d H:i:s')
            ], 'id_product = '.(int)$id_product);
            
            $id_bundle = $existing['id_bundle'];
        } else {
            Db::getInstance()->insert('product_bundle', [
                'id_product' => $id_product,
                'discount_type' => $discount_type,
                'discount_value' => $discount_value,
                'active' => $active,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s')
            ]);
            
            $id_bundle = Db::getInstance()->Insert_ID();
        }

        Db::getInstance()->delete('product_bundle_item', 'id_bundle = '.(int)$id_bundle);

        if ($products && is_array($products)) {
            $position = 0;
            foreach ($products as $id_product_item => $data) {
                Db::getInstance()->insert('product_bundle_item', [
                    'id_bundle' => $id_bundle,
                    'id_product' => (int)$id_product_item,
                    'quantity' => (int)$data['quantity'],
                    'position' => $position++
                ]);
            }
        }

        return true;
    }
}