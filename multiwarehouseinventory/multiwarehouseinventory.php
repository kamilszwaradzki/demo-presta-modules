<?php
if (!defined('_PS_VERSION_')) exit;

class MultiWarehouseInventory extends Module
{
    public function __construct()
    {
        $this->name = 'multiwarehouseinventory';
        $this->version = '1.0.0';
        $this->author = 'Kamil Szwaradzki';
        parent::__construct();
        $this->displayName = $this->l('Multi-Warehouse Inventory Manager');
    }

    public function install()
    {
        $sql = [];
        
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mwi_warehouse` (
            `id_warehouse` int(11) AUTO_INCREMENT PRIMARY KEY,
            `name` varchar(255) NOT NULL,
            `address` text,
            `city` varchar(100),
            `postcode` varchar(20),
            `country` varchar(100),
            `phone` varchar(50),
            `priority` int(11) DEFAULT 0,
            `active` tinyint(1) DEFAULT 1,
            `latitude` decimal(10,8),
            `longitude` decimal(11,8),
            `date_add` datetime NOT NULL
        ) ENGINE='._MYSQL_ENGINE_;

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mwi_stock` (
            `id_stock` int(11) AUTO_INCREMENT PRIMARY KEY,
            `id_warehouse` int(11) NOT NULL,
            `id_product` int(11) NOT NULL,
            `quantity` int(11) DEFAULT 0,
            `reserved_quantity` int(11) DEFAULT 0,
            `min_quantity` int(11) DEFAULT 0,
            UNIQUE KEY `warehouse_product` (`id_warehouse`,`id_product`)
        ) ENGINE='._MYSQL_ENGINE_;

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mwi_stock_movement` (
            `id_movement` int(11) AUTO_INCREMENT PRIMARY KEY,
            `id_warehouse_from` int(11),
            `id_warehouse_to` int(11),
            `id_product` int(11) NOT NULL,
            `quantity` int(11) NOT NULL,
            `movement_type` enum("in","out","transfer","adjustment") NOT NULL,
            `reference` varchar(50),
            `note` text,
            `date_add` datetime NOT NULL,
            KEY `date_add` (`date_add`)
        ) ENGINE='._MYSQL_ENGINE_;

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) return false;
        }

        return parent::install() &&
            $this->registerHook('actionValidateOrder') &&
            $this->installTabs()
            && Configuration::updateValue('MWI_API_TOKEN', bin2hex(random_bytes(16)));
    }

    public function uninstall()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'mwi_warehouse`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'mwi_stock`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'mwi_stock_movement`');
        $this->uninstallTabs();
        return parent::uninstall();
    }

    private function installTabs()
    {
        $parentTabId = (int)Tab::getIdFromClassName('AdminParentWarehouses');
        if (!$parentTabId) {
            $parentTab = new Tab();
            $parentTab->class_name = 'AdminParentWarehouses';
            $parentTab->name = [];
            foreach (Language::getLanguages(false) as $lang)
                $parentTab->name[$lang['id_lang']] = 'Warehouses';
            $parentTab->id_parent = 0;
            $parentTab->module = $this->name;
            $parentTab->add();
            $parentTabId = (int)$parentTab->id;
        }

        $tabs = [
            ['AdminWarehouse', 'Warehouses list'],
            ['AdminStock', 'Stock transfer'],
            ['AdminStockMovement', 'Dashboard'],
        ];

        foreach ($tabs as [$class, $name]) {
            $tab = new Tab();
            $tab->class_name = $class;
            $tab->id_parent = $parentTabId;
            $tab->module = $this->name;
            $tab->name = [];
            foreach (Language::getLanguages(false) as $lang)
                $tab->name[$lang['id_lang']] = $name;
            $tab->add();
        }
        return true;
    }

    private function uninstallTabs()
    {
        $tabs = [
            ['AdminWarehouse', 'Warehouses list'],
            ['AdminStock', 'Stock transfer'],
            ['AdminStockMovement', 'Dashboard'],
        ];

        foreach ($tabs as [$class, $name]) {
            $id_tab = (int)Tab::getIdFromClassName($class);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }
        return true;
    }

    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $cart = $params['cart'];
        
        $warehouse = $this->findNearestWarehouse($cart->id_address_delivery);
        
        if (!$warehouse) return;

        $products = $cart->getProducts();
        
        foreach ($products as $product) {
            $this->reduceStock(
                $warehouse['id_warehouse'],
                $product['id_product'],
                $product['quantity']
            );
            
            $this->logMovement(
                $warehouse['id_warehouse'],
                null,
                $product['id_product'],
                $product['quantity'],
                'out',
                'Order #'.$order->id
            );
        }
    }

    public function findNearestWarehouse($id_address)
    {
        $address = new Address($id_address);
        
        $warehouses = Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'mwi_warehouse` 
            WHERE active = 1 
            ORDER BY priority DESC 
            LIMIT 1'
        );

        return $warehouses ? $warehouses[0] : null;
    }

    public function getStock($id_warehouse, $id_product)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'mwi_stock` 
            WHERE id_warehouse = '.(int)$id_warehouse.' 
            AND id_product = '.(int)$id_product
        );
    }

    public function updateStock($id_warehouse, $id_product, $quantity)
    {
        $existing = $this->getStock($id_warehouse, $id_product);

        if ($existing) {
            return Db::getInstance()->update('mwi_stock', [
                'quantity' => (int)$quantity
            ], 'id_warehouse = '.(int)$id_warehouse.' AND id_product = '.(int)$id_product);
        } else {
            return Db::getInstance()->insert('mwi_stock', [
                'id_warehouse' => (int)$id_warehouse,
                'id_product' => (int)$id_product,
                'quantity' => (int)$quantity,
                'reserved_quantity' => 0,
                'min_quantity' => 0
            ]);
        }
    }

    public function reduceStock($id_warehouse, $id_product, $quantity)
    {
        $stock = $this->getStock($id_warehouse, $id_product);
        
        if (!$stock || $stock['quantity'] < $quantity) {
            return false;
        }

        return Db::getInstance()->execute(
            'UPDATE `'._DB_PREFIX_.'mwi_stock` 
            SET quantity = quantity - '.(int)$quantity.' 
            WHERE id_warehouse = '.(int)$id_warehouse.' 
            AND id_product = '.(int)$id_product
        );
    }

    public function transferStock($id_from, $id_to, $id_product, $quantity)
    {
        $stock_from = $this->getStock($id_from, $id_product);
        
        if (!$stock_from || $stock_from['quantity'] < $quantity) {
            return ['success' => false, 'error' => 'Insufficient stock'];
        }

        $this->reduceStock($id_from, $id_product, $quantity);
        
        $stock_to = $this->getStock($id_to, $id_product);
        if ($stock_to) {
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'mwi_stock` 
                SET quantity = quantity + '.(int)$quantity.' 
                WHERE id_warehouse = '.(int)$id_to.' 
                AND id_product = '.(int)$id_product
            );
        } else {
            $this->updateStock($id_to, $id_product, $quantity);
        }

        $this->logMovement($id_from, $id_to, $id_product, $quantity, 'transfer');

        return ['success' => true];
    }

    public function logMovement($id_from, $id_to, $id_product, $quantity, $type, $reference = null, $note = null)
    {
        return Db::getInstance()->insert('mwi_stock_movement', [
            'id_warehouse_from' => $id_from ? (int)$id_from : null,
            'id_warehouse_to' => $id_to ? (int)$id_to : null,
            'id_product' => (int)$id_product,
            'quantity' => (int)$quantity,
            'movement_type' => pSQL($type),
            'reference' => $reference ? pSQL($reference) : null,
            'note' => $note ? pSQL($note) : null,
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }

    public function getLowStockProducts($id_warehouse = null)
    {
        $where = $id_warehouse ? 'WHERE s.id_warehouse = '.(int)$id_warehouse : '';
        
        return Db::getInstance()->executeS(
            'SELECT s.*, pl.name, w.name as warehouse_name 
            FROM `'._DB_PREFIX_.'mwi_stock` s
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (s.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'mwi_warehouse` w ON (s.id_warehouse = w.id_warehouse)
            '.$where.'
            HAVING s.quantity <= s.min_quantity
            ORDER BY s.quantity ASC'
        );
    }

    public function getWarehouses($active_only = true)
    {
        $where = $active_only ? 'WHERE active = 1' : '';
        
        return Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'mwi_warehouse` 
            '.$where.' 
            ORDER BY priority DESC, name ASC'
        );
    }

    public function getMovements($id_warehouse = null, $limit = 50)
    {
        $where = $id_warehouse ? 
            'WHERE (m.id_warehouse_from = '.(int)$id_warehouse.' OR m.id_warehouse_to = '.(int)$id_warehouse.')' : '';
        
        return Db::getInstance()->executeS(
            'SELECT m.*, pl.name as product_name,
            w1.name as warehouse_from_name,
            w2.name as warehouse_to_name
            FROM `'._DB_PREFIX_.'mwi_stock_movement` m
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (m.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.')
            LEFT JOIN `'._DB_PREFIX_.'mwi_warehouse` w1 ON (m.id_warehouse_from = w1.id_warehouse)
            LEFT JOIN `'._DB_PREFIX_.'mwi_warehouse` w2 ON (m.id_warehouse_to = w2.id_warehouse)
            '.$where.'
            ORDER BY m.date_add DESC
            LIMIT '.(int)$limit
        );
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitWarehouse')) {
            $output .= $this->processWarehouseSave();
        }

        $this->context->smarty->assign([
            'warehouses' => $this->getWarehouses(false),
            'low_stock' => $this->getLowStockProducts(),
            'recent_movements' => $this->getMovements(null, 20)
        ]);

        return $output.$this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    private function processWarehouseSave()
    {
        $name = Tools::getValue('warehouse_name');
        $address = Tools::getValue('warehouse_address');
        $city = Tools::getValue('warehouse_city');
        $priority = (int)Tools::getValue('warehouse_priority');

        if (!$name) {
            return $this->displayError($this->l('Warehouse name is required'));
        }

        $result = Db::getInstance()->insert('mwi_warehouse', [
            'name' => pSQL($name),
            'address' => pSQL($address),
            'city' => pSQL($city),
            'priority' => $priority,
            'active' => 1,
            'date_add' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            return $this->displayConfirmation($this->l('Warehouse created successfully'));
        }

        return $this->displayError($this->l('Error creating warehouse'));
    }
}