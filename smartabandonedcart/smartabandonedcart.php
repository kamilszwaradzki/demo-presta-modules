<?php
if (!defined('_PS_VERSION_')) exit;

class SmartAbandonedCart extends Module
{
    private $config_keys = [
        'SAC_ENABLED',
        'SAC_REMINDER_1_HOURS',
        'SAC_REMINDER_2_HOURS', 
        'SAC_REMINDER_3_HOURS',
        'SAC_DISCOUNT_1',
        'SAC_DISCOUNT_2',
        'SAC_MIN_CART_VALUE',
        'SAC_EMAIL_FROM_NAME'
    ];

    public function __construct()
    {
        $this->name = 'smartabandonedcart';
        $this->author = 'Kamil Szwaradzki';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.0';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Smart Abandoned Cart Recovery');
        $this->description = $this->l('Recover abandoned carts with intelligent email campaigns');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        Configuration::updateValue('SAC_ENABLED', 1);
        Configuration::updateValue('SAC_REMINDER_1_HOURS', 1);
        Configuration::updateValue('SAC_REMINDER_2_HOURS', 24);
        Configuration::updateValue('SAC_REMINDER_3_HOURS', 72);
        Configuration::updateValue('SAC_DISCOUNT_1', 10);
        Configuration::updateValue('SAC_DISCOUNT_2', 15);
        Configuration::updateValue('SAC_MIN_CART_VALUE', 50);
        Configuration::updateValue('SAC_EMAIL_FROM_NAME', Configuration::get('PS_SHOP_NAME'));

        return parent::install() &&
            $this->installDb() &&
            $this->registerHook('actionCartSave') &&
            $this->registerHook('actionValidateOrder') &&
            $this->installTab();
    }

    public function uninstall()
    {
        foreach ($this->config_keys as $key) {
            Configuration::deleteByName($key);
        }

        $sql = [
            'DROP TABLE IF EXISTS `'._DB_PREFIX_.'abandoned_cart`',
            'DROP TABLE IF EXISTS `'._DB_PREFIX_.'abandoned_cart_email`'
        ];

        foreach ($sql as $query) {
            Db::getInstance()->execute($query);
        }

        $this->uninstallTab();
        return parent::uninstall();
    }

    public function installDb()
    {
        $sql = [];

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'abandoned_cart` (
            `id_abandoned_cart` int(11) NOT NULL AUTO_INCREMENT,
            `id_cart` int(11) NOT NULL,
            `id_customer` int(11) NOT NULL,
            `id_shop` int(11) NOT NULL,
            `email` varchar(255) NOT NULL,
            `firstname` varchar(100),
            `lastname` varchar(100),
            `cart_total` decimal(10,2) DEFAULT 0.00,
            `products_count` int(11) DEFAULT 0,
            `abandoned_date` datetime NOT NULL,
            `last_reminder_sent` datetime DEFAULT NULL,
            `reminder_count` int(11) DEFAULT 0,
            `recovered` tinyint(1) DEFAULT 0,
            `recovery_date` datetime DEFAULT NULL,
            `discount_code` varchar(50) DEFAULT NULL,
            `ip_address` varchar(45),
            PRIMARY KEY (`id_abandoned_cart`),
            UNIQUE KEY `id_cart` (`id_cart`),
            KEY `id_customer` (`id_customer`),
            KEY `abandoned_date` (`abandoned_date`),
            KEY `recovered` (`recovered`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'abandoned_cart_email` (
            `id_email` int(11) NOT NULL AUTO_INCREMENT,
            `id_abandoned_cart` int(11) NOT NULL,
            `email_type` enum("reminder_1","reminder_2","reminder_3") NOT NULL,
            `sent_date` datetime NOT NULL,
            `opened` tinyint(1) DEFAULT 0,
            `opened_date` datetime DEFAULT NULL,
            `clicked` tinyint(1) DEFAULT 0,
            `clicked_date` datetime DEFAULT NULL,
            PRIMARY KEY (`id_email`),
            KEY `id_abandoned_cart` (`id_abandoned_cart`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAbandonedCart';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Abandoned Carts');
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminOrders');
        $tab->module = $this->name;
        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminAbandonedCart');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function hookActionCartSave()
    {
        if (!Configuration::get('SAC_ENABLED')) {
            return;
        }

        $cart = $this->context->cart;

        if (!$cart->id || !$cart->id_customer) {
            return;
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer) || !$customer->email) {
            return;
        }

        $products = $cart->getProducts();
        if (empty($products)) {
            $this->deleteAbandonedCart($cart->id);
            return;
        }

        $cart_total = $cart->getOrderTotal(true, Cart::BOTH);
        $min_value = (float)Configuration::get('SAC_MIN_CART_VALUE');

        if ($cart_total < $min_value) {
            return;
        }

        $existing = $this->getAbandonedCartByCartId($cart->id);
        
        if ($existing) {
            $this->updateAbandonedCart($existing['id_abandoned_cart'], $cart, $customer);
        } else {
            $this->createAbandonedCart($cart, $customer);
        }
    }

    public function hookActionValidateOrder($params)
    {
        $cart = $params['cart'];
        $abandoned = $this->getAbandonedCartByCartId($cart->id);

        if ($abandoned && !$abandoned['recovered']) {
            Db::getInstance()->update(
                'abandoned_cart',
                [
                    'recovered' => 1,
                    'recovery_date' => date('Y-m-d H:i:s')
                ],
                '`id_abandoned_cart` = '.(int)$abandoned['id_abandoned_cart']
            );
        }
    }

    private function createAbandonedCart($cart, $customer)
    {
        $products = $cart->getProducts();
        $cart_total = $cart->getOrderTotal(true, Cart::BOTH);

        return Db::getInstance()->insert(
            'abandoned_cart',
            [
                'id_cart' => (int)$cart->id,
                'id_customer' => (int)$customer->id,
                'id_shop' => (int)$this->context->shop->id,
                'email' => pSQL($customer->email),
                'firstname' => pSQL($customer->firstname),
                'lastname' => pSQL($customer->lastname),
                'cart_total' => (float)$cart_total,
                'products_count' => count($products),
                'abandoned_date' => date('Y-m-d H:i:s'),
                'reminder_count' => 0,
                'recovered' => 0,
                'ip_address' => pSQL(Tools::getRemoteAddr())
            ]
        );
    }

    private function updateAbandonedCart($id_abandoned_cart, $cart, $customer)
    {
        $products = $cart->getProducts();
        $cart_total = $cart->getOrderTotal(true, Cart::BOTH);

        return Db::getInstance()->update(
            'abandoned_cart',
            [
                'email' => pSQL($customer->email),
                'firstname' => pSQL($customer->firstname),
                'lastname' => pSQL($customer->lastname),
                'cart_total' => (float)$cart_total,
                'products_count' => count($products),
                'abandoned_date' => date('Y-m-d H:i:s')
            ],
            '`id_abandoned_cart` = '.(int)$id_abandoned_cart
        );
    }

    private function deleteAbandonedCart($id_cart)
    {
        return Db::getInstance()->delete(
            'abandoned_cart',
            '`id_cart` = '.(int)$id_cart
        );
    }

    private function getAbandonedCartByCartId($id_cart)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE `id_cart` = '.(int)$id_cart
        );
    }

    public function processAbandonedCarts()
    {
        if (!Configuration::get('SAC_ENABLED')) {
            return ['processed' => 0, 'sent' => 0];
        }

        $config = [
            'reminder_1' => (int)Configuration::get('SAC_REMINDER_1_HOURS'),
            'reminder_2' => (int)Configuration::get('SAC_REMINDER_2_HOURS'),
            'reminder_3' => (int)Configuration::get('SAC_REMINDER_3_HOURS')
        ];

        $abandoned_carts = $this->getAbandonedCartsForReminders();
        $processed = 0;
        $sent = 0;

        foreach ($abandoned_carts as $abandoned) {
            $processed++;
            $hours_since_abandoned = (time() - strtotime($abandoned['abandoned_date'])) / 3600;
            
            if ($abandoned['reminder_count'] == 0 && $hours_since_abandoned >= $config['reminder_1']) {
                if ($this->sendReminderEmail($abandoned, 'reminder_1', false)) {
                    $sent++;
                }
            }
            elseif ($abandoned['reminder_count'] == 1 && $hours_since_abandoned >= $config['reminder_2']) {
                if ($this->sendReminderEmail($abandoned, 'reminder_2', true)) {
                    $sent++;
                }
            }
            elseif ($abandoned['reminder_count'] == 2 && $hours_since_abandoned >= $config['reminder_3']) {
                if ($this->sendReminderEmail($abandoned, 'reminder_3', true)) {
                    $sent++;
                }
            }
        }

        return ['processed' => $processed, 'sent' => $sent];
    }

    private function getAbandonedCartsForReminders()
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE `recovered` = 0 
            AND `reminder_count` < 3
            AND DATE_ADD(`abandoned_date`, INTERVAL 1 HOUR) < NOW()
            ORDER BY `abandoned_date` DESC
            LIMIT 100'
        );
    }

    private function sendReminderEmail($abandoned, $type, $with_discount = false)
    {
        $customer = new Customer($abandoned['id_customer']);
        $cart = new Cart($abandoned['id_cart']);
        
        if (!Validate::isLoadedObject($customer) || !Validate::isLoadedObject($cart)) {
            return false;
        }

        $discount_code = null;
        $discount_value = 0;

        if ($with_discount) {
            $discount_value = ($type === 'reminder_2') ? 
                Configuration::get('SAC_DISCOUNT_1') : 
                Configuration::get('SAC_DISCOUNT_2');
            $discount_code = $this->generateDiscountCode($abandoned, $type, $discount_value);
        }

        $recovery_link = $this->context->link->getPageLink('cart', true, null, [
            'action' => 'show',
            'sac_token' => $this->generateRecoveryToken($abandoned['id_abandoned_cart'])
        ]);

        $template_vars = [
            '{customer_firstname}' => $customer->firstname,
            '{customer_lastname}' => $customer->lastname,
            '{cart_total}' => Tools::displayPrice($abandoned['cart_total']),
            '{products_count}' => $abandoned['products_count'],
            '{cart_url}' => $recovery_link,
            '{discount_code}' => $discount_code ?: '',
            '{discount_value}' => $discount_value,
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{shop_url}' => $this->context->link->getPageLink('index', true),
            '{products_html}' => $this->getCartProductsHtml($cart)
        ];

        $sent = Mail::Send(
            (int)$this->context->language->id,
            $type,
            $this->getEmailSubject($type),
            $template_vars,
            $abandoned['email'],
            $customer->firstname.' '.$customer->lastname,
            Configuration::get('PS_SHOP_EMAIL'),
            Configuration::get('SAC_EMAIL_FROM_NAME'),
            null,
            null,
            _PS_MODULE_DIR_.$this->name.'/mails/',
            false,
            (int)$this->context->shop->id
        );

        if ($sent) {
            Db::getInstance()->update(
                'abandoned_cart',
                [
                    'reminder_count' => $abandoned['reminder_count'] + 1,
                    'last_reminder_sent' => date('Y-m-d H:i:s'),
                    'discount_code' => $discount_code ? pSQL($discount_code) : null
                ],
                '`id_abandoned_cart` = '.(int)$abandoned['id_abandoned_cart']
            );

            Db::getInstance()->insert(
                'abandoned_cart_email',
                [
                    'id_abandoned_cart' => (int)$abandoned['id_abandoned_cart'],
                    'email_type' => pSQL($type),
                    'sent_date' => date('Y-m-d H:i:s'),
                    'opened' => 0,
                    'clicked' => 0
                ]
            );
        }

        return $sent;
    }

    private function getEmailSubject($type)
    {
        $subjects = [
            'reminder_1' => $this->l('You left something in your cart'),
            'reminder_2' => $this->l('Special offer: Complete your order now!'),
            'reminder_3' => $this->l('Last chance: Your cart is expiring soon!')
        ];

        return $subjects[$type] ?? $this->l('Your cart is waiting');
    }

    private function generateDiscountCode($abandoned, $type, $discount_value)
    {
        $code = 'SAC'.$abandoned['id_abandoned_cart'].'_'.strtoupper(substr($type, -1));

        $existing = new CartRule(CartRule::getIdByCode($code));
        if (Validate::isLoadedObject($existing)) {
            return $code;
        }

        $cart_rule = new CartRule();
        $cart_rule->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $cart_rule->name[$lang['id_lang']] = 'Abandoned Cart Recovery - '.$code;
        }
        $cart_rule->code = $code;
        $cart_rule->id_customer = $abandoned['id_customer'];
        $cart_rule->reduction_percent = $discount_value;
        $cart_rule->reduction_tax = 1;
        $cart_rule->quantity = 1;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->minimum_amount = 0;
        $cart_rule->minimum_amount_tax = 1;
        $cart_rule->minimum_amount_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        $cart_rule->minimum_amount_shipping = 0;
        $cart_rule->date_from = date('Y-m-d H:i:s');
        $cart_rule->date_to = date('Y-m-d H:i:s', strtotime('+7 days'));
        $cart_rule->active = 1;
        $cart_rule->highlight = 1;
        
        if ($cart_rule->add()) {
            return $code;
        }

        return null;
    }

    private function generateRecoveryToken($id_abandoned_cart)
    {
        return md5($id_abandoned_cart.'_'.Configuration::get('PS_SHOP_NAME').'_sac_recovery');
    }

    private function getCartProductsHtml($cart)
    {
        $products = $cart->getProducts();
        $html = '<table style="width:100%; border-collapse:collapse;">';
        
        foreach ($products as $product) {
            $image = Image::getCover($product['id_product']);
            $image_url = $this->context->link->getImageLink(
                $product['link_rewrite'],
                $image['id_image'],
                'small_default'
            );

            $html .= '<tr style="border-bottom:1px solid #ddd;">';
            $html .= '<td style="padding:10px;"><img src="'.$image_url.'" alt="" style="max-width:80px;"></td>';
            $html .= '<td style="padding:10px;">';
            $html .= '<strong>'.$product['name'].'</strong><br>';
            $html .= $this->l('Quantity').': '.$product['quantity'].'<br>';
            $html .= Tools::displayPrice($product['price_wt']);
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }

    public function getStatistics($days = 30)
    {
        $date_from = date('Y-m-d H:i:s', strtotime('-'.$days.' days'));

        $stats = [];

        $stats['total_abandoned'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE `abandoned_date` >= "'.pSQL($date_from).'"'
        );

        $stats['total_recovered'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE `recovered` = 1 
            AND `abandoned_date` >= "'.pSQL($date_from).'"'
        );

        $stats['pending'] = $stats['total_abandoned'] - $stats['total_recovered'];

        $stats['recovery_rate'] = $stats['total_abandoned'] > 0 ? 
            round(($stats['total_recovered'] / $stats['total_abandoned']) * 100, 2) : 0;

        $stats['total_value_abandoned'] = (float)Db::getInstance()->getValue(
            'SELECT SUM(cart_total) FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE `abandoned_date` >= "'.pSQL($date_from).'"'
        );

        $stats['total_value_recovered'] = (float)Db::getInstance()->getValue(
            'SELECT SUM(cart_total) FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE `recovered` = 1 
            AND `abandoned_date` >= "'.pSQL($date_from).'"'
        );

        $stats['avg_cart_value'] = $stats['total_abandoned'] > 0 ?
            $stats['total_value_abandoned'] / $stats['total_abandoned'] : 0;

        $stats['emails_sent'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'abandoned_cart_email` e
            INNER JOIN `'._DB_PREFIX_.'abandoned_cart` a ON (e.id_abandoned_cart = a.id_abandoned_cart)
            WHERE a.abandoned_date >= "'.pSQL($date_from).'"'
        );

        $stats['emails_opened'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'abandoned_cart_email` e
            INNER JOIN `'._DB_PREFIX_.'abandoned_cart` a ON (e.id_abandoned_cart = a.id_abandoned_cart)
            WHERE e.opened = 1
            AND a.abandoned_date >= "'.pSQL($date_from).'"'
        );

        $stats['open_rate'] = $stats['emails_sent'] > 0 ?
            round(($stats['emails_opened'] / $stats['emails_sent']) * 100, 2) : 0;

        $stats['avg_recovery_time'] = Db::getInstance()->getValue(
            'SELECT AVG(TIMESTAMPDIFF(HOUR, abandoned_date, recovery_date)) 
            FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE `recovered` = 1
            AND `abandoned_date` >= "'.pSQL($date_from).'"'
        );

        return $stats;
    }

    public function getRecentAbandonedCarts($limit = 10)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'abandoned_cart` 
            WHERE `recovered` = 0
            ORDER BY `abandoned_date` DESC
            LIMIT '.(int)$limit
        );
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitSACConfig')) {
            $output .= $this->postProcess();
        }

        if (Tools::isSubmit('testCron')) {
            $result = $this->processAbandonedCarts();
            $output .= $this->displayConfirmation(
                sprintf($this->l('Processed: %d carts, Sent: %d emails'), 
                $result['processed'], $result['sent'])
            );
        }

        $stats = $this->getStatistics(30);
        $this->context->smarty->assign([
            'stats' => $stats,
            'recent_carts' => $this->getRecentAbandonedCarts(10),
            'cron_url' => _PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.'/cron/process_carts.php'
        ]);

        $output .= $this->context->smarty->fetch(
            _PS_MODULE_DIR_.$this->name.'/views/templates/admin/dashboard.tpl'
        );

        return $output.$this->renderForm();
    }

    private function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable module'),
                        'name' => 'SAC_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')]
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('First reminder (hours)'),
                        'name' => 'SAC_REMINDER_1_HOURS',
                        'suffix' => 'hours',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Send first reminder after X hours')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Second reminder (hours)'),
                        'name' => 'SAC_REMINDER_2_HOURS',
                        'suffix' => 'hours',
                        'class' => 'fixed-width-sm'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Third reminder (hours)'),
                        'name' => 'SAC_REMINDER_3_HOURS',
                        'suffix' => 'hours',
                        'class' => 'fixed-width-sm'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Second email discount (%)'),
                        'name' => 'SAC_DISCOUNT_1',
                        'suffix' => '%',
                        'class' => 'fixed-width-sm'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Third email discount (%)'),
                        'name' => 'SAC_DISCOUNT_2',
                        'suffix' => '%',
                        'class' => 'fixed-width-sm'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Minimum cart value'),
                        'name' => 'SAC_MIN_CART_VALUE',
                        'prefix' => Currency::getDefaultCurrency()->sign,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Track only carts above this value')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Email sender name'),
                        'name' => 'SAC_EMAIL_FROM_NAME',
                        'class' => 'fixed-width-lg'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ],
                'buttons' => [
                    [
                        'type' => 'submit',
                        'title' => $this->l('Test Cron'),
                        'icon' => 'process-icon-refresh',
                        'name' => 'testCron',
                        'class' => 'btn btn-default pull-right',
                        'js' => 'return confirm("'.$this->l('Process abandoned carts now?').'");'
                    ]
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSACConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([$fields_form]);
    }

    private function getConfigFormValues()
    {
        $values = [];
        foreach ($this->config_keys as $key) {
            $values[$key] = Configuration::get($key);
        }
        return $values;
    }

    private function postProcess()
    {
        foreach ($this->config_keys as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        return $this->displayConfirmation($this->l('Settings updated successfully'));
    }
}