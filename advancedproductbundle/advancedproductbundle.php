<?php
if (!defined('_PS_VERSION_')) exit;

class AdvancedProductBundle extends Module
{
    public function __construct()
    {
        $this->name = 'advancedproductbundle';
        $this->author = 'Kamil Szwaradzki';
        $this->version = '1.0.0';
        $this->displayName = $this->l('Advanced Product Bundle Creator');
        parent::__construct();
        require_once __DIR__ . '/classes/ProductBundle.php';
    }

    public function install()
    {
        try {
        $sql = [];
        
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_bundle` (
            `id_bundle` int(11) AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,  -- nazwa bundle\'a
            `discount_type` enum("percentage","fixed") DEFAULT "percentage",
            `discount_value` decimal(10,2) DEFAULT 0.00,
            `active` tinyint(1) DEFAULT 1,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL
        ) ENGINE='._MYSQL_ENGINE_;

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_bundle_pack` (
            `id_bundle_pack` int(11) NOT NULL AUTO_INCREMENT,
            `id_bundle` int(11) NOT NULL,
            `id_product` int(11) NOT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_bundle_pack`),
            KEY `id_bundle` (`id_bundle`),
            KEY `id_product` (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

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
            $this->registerHook('actionValidateOrder')
            && $this->installTabs();
        } catch (Exception $e) {
            PrestaShopLogger::addLog('AdvancedProductBundle install error: '.$e->getMessage());
            return false;
        }
    }

    private function installTabs()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminProductBundle';
        $tab->name = [];

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Product Bundles';
        }
        $parentTabId = (int)Tab::getIdFromClassName('AdminCatalog');
        if (!$parentTabId) {
            $parentTab = new Tab();
            $parentTab->class_name = 'AdminCatalog';
            $parentTab->name = [];
            foreach (Language::getLanguages(false) as $lang)
                $parentTab->name[$lang['id_lang']] = 'Admin Catalog';
            $parentTab->id_parent = 0;
            $parentTab->module = $this->name;
            $parentTab->add();
            $parentTabId = (int)$parentTab->id;
        }
        $tab->id_parent = $parentTabId;
        $tab->module = $this->name;

        return $tab->add();
    }

    public function uninstall()
    {
        $idTab = (int)Tab::getIdFromClassName('AdminProductBundle');
        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
        }

        if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_bundle_pack`')) return false;
        if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'product_bundle`')) return false;
        if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'product_bundle_item`')) return false;
        return parent::uninstall() && Configuration::deleteByName('advancedproductbundle');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int)Tools::getValue('id_product');
        $bundle = ProductBundle::getByProductId($id_product);
        if (!$bundle || !$bundle->active) return;
        $items = $bundle ? $this->getBundleItems($bundle->id_bundle) : [];

        $this->context->smarty->assign([
            'bundle' => $bundle,
            'bundle_items' => $items,
            'id_product' => $id_product,
            'all_products' => Product::getSimpleProducts($this->context->language->id),
            'currency' => Currency::getDefaultCurrency(),
            'link' => Context::getContext()->link,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/bundle_form.tpl');
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if ($params['type'] !== 'before_price') return;

        $id_product = $params['product']['id_product'];
        
        // Sprawdź czy ten produkt jest już packiem
        if (Pack::isPack($id_product)) {
            return $this->displayPackInfo($id_product);
        }

        // Sprawdź czy produkt należy do jakiegoś bundle'a
        $bundle = ProductBundle::getByProductId($id_product);
        if (!$bundle || !$bundle->active) return;

        return $this->displayBundleAsPack($bundle, $id_product);
    }

    protected function displayPackInfo($id_product)
    {
        $pack_items = Pack::getItems($id_product, $this->context->language->id);
        $pack_price = Product::getPriceStatic($id_product);
        $original_price = 0;
        
        foreach ($pack_items as $item) {
            $original_price += $item->price * $item->pack_quantity;
        }
        
        $savings = $original_price - $pack_price;
        
        $this->context->smarty->assign([
            'pack_items' => $pack_items,
            'pack_price' => $this->formatPrice($pack_price),
            'original_price' => $this->formatPrice($original_price),
            'savings' => $this->formatPrice($savings)
        ]);
        
        return $this->display(__FILE__, 'views/templates/hook/pack_info.tpl');
    }

    protected function displayBundleAsPack($bundle, $trigger_product_id)
    {
        $pack_product_id = $this->getOrCreatePackFromBundle($bundle);
        
        if (!$pack_product_id) {
            return '';
        }

        $pack_items = Pack::getItems($pack_product_id, $this->context->language->id);
        $pack_price = $this->calculateBundlePrice($bundle->id_bundle);
        $original_price = $this->getOriginalPrice($bundle->id_bundle);

        $this->context->smarty->assign([
            'bundle' => (array)$bundle,
            'pack_product_id' => $pack_product_id,
            'pack_items' => $pack_items,
            'pack_price' => $this->formatPrice($pack_price),
            'original_price' => $this->formatPrice($original_price),
            'savings' => $this->formatPrice($original_price - $pack_price),
            'trigger_product_id' => $trigger_product_id
        ]);

        return $this->display(__FILE__, 'views/templates/hook/bundle_as_pack.tpl');
    }

    protected function getOrCreatePackFromBundle($bundle)
    {
        $pack_product_id = $this->getPackIdByBundle($bundle->id_bundle);
        
        if ($pack_product_id && Pack::isPack($pack_product_id)) {
            $this->updatePackFromBundle($pack_product_id, $bundle);
            return $pack_product_id;
        }
        
        return $this->createPackFromBundle($bundle);
    }

    protected function updatePackFromBundle($pack_product_id, $bundle)
    {
        $product = new Product($pack_product_id);
        
        if (Validate::isLoadedObject($product)) {
            $product->name = array_fill_keys(
                Language::getIDs(false), 
                $bundle->name . ' (Bundle)'
            );
            $product->price = $this->calculateBundlePrice($bundle->id);
            $product->active = $bundle->active;
            $product->save();
            
            Pack::deleteItems($pack_product_id);
            foreach($this->getBundleProductsForPack($bundle->id) as $id => $qty) {
                Pack::addItem(
                    $pack_product_id,
                    $id,
                    $qty
                );
            }
        }
    }

    protected function getPackIdByBundle($id_bundle)
    {
        $sql = 'SELECT id_product FROM `' . _DB_PREFIX_ . 'product` 
                WHERE reference = "BUNDLE_' . (int)$id_bundle . '"';
        return Db::getInstance()->getValue($sql);
    }

    protected function createPackFromBundle($bundle)
    {
        $product = new Product();
        $product->name = array_fill_keys(
            Language::getIDs(false), 
            $bundle->name . ' (Bundle)'
        );
        $product->reference = 'BUNDLE_' . $bundle->id_bundle;
        $product->ean13 = '';
        $product->upc = '';
        $product->redirect_type = '404';
        $product->show_price = 1;
        $product->price = $this->calculateBundlePrice($bundle->id_bundle);
        $product->active = $bundle->active;
        $product->is_virtual = 0;
        $product->id_tax_rules_group = 0;
        $product->cache_is_pack = 1;
        
        if ($product->add()) {
            foreach($this->getBundleProductsForPack($bundle->id_bundle) as $id_prod => $qty)
            {
                Pack::addItem(
                    $product->id,
                    $id_prod,
                    $qty
                );
            }
            
            $this->saveBundlePackRelation($bundle->id_bundle, $product->id);
            
            return $product->id;
        }
        
        return false;
    }

    protected function getBundleProductsForPack($id_bundle)
    {
        $sql = 'SELECT id_product, quantity 
                FROM `' . _DB_PREFIX_ . 'product_bundle_item` 
                WHERE id_bundle = ' . (int)$id_bundle;
        
        $products = Db::getInstance()->executeS($sql);
        $pack_items = [];
        
        foreach ($products as $product) {
            $pack_items[$product['id_product']] = $product['quantity'];
        }
        
        return $pack_items;
    }

    protected function saveBundlePackRelation($id_bundle, $id_product)
    {
        return Db::getInstance()->insert('product_bundle_pack', [
            'id_bundle' => (int)$id_bundle,
            'id_product' => (int)$id_product,
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }

    public function hookActionValidateOrder($params)
    {
        $cart = $params['cart'];
        $products = $cart->getProducts();

        foreach ($products as $product) {
            $bundle = ProductBundle::getByProductId($product['id_product']);
            
            if ($bundle) {
                $items = $this->getBundleItems($bundle->id_bundle);
                
                foreach ($items as $item) {
                    StockAvailable::updateQuantity(
                        $item->id_product,
                        0,
                        -($item->quantity * $product['quantity'])
                    );
                }
            }
        }
    }

    protected function formatPrice($price)
    {
        return Context::getContext()->getCurrentLocale()->formatPrice($price, $this->context->currency->iso_code);
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
}