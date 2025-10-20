<?php
if (!defined('_PS_VERSION_')) exit;

class CustomerLoyalty extends Module
{
    public function __construct()
    {
        $this->name = 'customerloyalty';
        $this->version = '1.0.0';
        $this->author = 'Kamil Szwaradzki';
        $this->bootstrap = true;
        
        parent::__construct();
        
        $this->displayName = $this->trans('Customer Loyalty & Rewards');
        $this->description = $this->trans('Complete loyalty program with points and rewards');
    }

    public function install()
    {
        // Konfiguracja
        Configuration::updateValue('LOYALTY_POINTS_PER_EURO', 10);
        Configuration::updateValue('LOYALTY_WELCOME_BONUS', 100);
        Configuration::updateValue('LOYALTY_REFERRAL_BONUS', 50);

        $sql = [];
        
        // Tabela punktów
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'loyalty_points` (
            `id_loyalty` int(11) NOT NULL AUTO_INCREMENT,
            `id_customer` int(11) NOT NULL,
            `points` int(11) DEFAULT 0,
            `points_spent` int(11) DEFAULT 0,
            `membership_level` int(11) DEFAULT 1,
            `referral_code` varchar(50),
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_loyalty`),
            UNIQUE KEY `id_customer` (`id_customer`),
            UNIQUE KEY `referral_code` (`referral_code`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        // Tabela historii - ZMIANA: description -> note
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'loyalty_history` (
            `id_history` int(11) NOT NULL AUTO_INCREMENT,
            `id_customer` int(11) NOT NULL,
            `points` int(11) NOT NULL,
            `action_type` varchar(50) NOT NULL,
            `id_order` int(11) DEFAULT NULL,
            `note` text,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_history`),
            KEY `id_customer` (`id_customer`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        // Tabela nagród
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'loyalty_rewards` (
            `id_reward` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `points_required` int(11) NOT NULL,
            `reward_type` enum("discount","voucher","free_shipping") NOT NULL,
            `reward_value` decimal(10,2) DEFAULT NULL,
            `min_level` int(11) DEFAULT 1,
            `active` tinyint(1) DEFAULT 1,
            PRIMARY KEY (`id_reward`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        // Tabela poleceń
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'loyalty_referrals` (
            `id_referral` int(11) NOT NULL AUTO_INCREMENT,
            `id_customer_referrer` int(11) NOT NULL,
            `id_customer_referred` int(11) NOT NULL,
            `status` enum("pending","completed") DEFAULT "pending",
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_referral`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        // Wykonaj SQL
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        // Instalacja bez Tab (żeby nie blokować)
        return parent::install() &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('displayHeader')
            && $this->installTabs();
    }

    private function installTabs()
    {
        $tabs = [
            [
                'class_name' => 'AdminLoyalty',
                'parent_class' => 'AdminParentCustomer', // lub 0 – wtedy pojawi się na najwyższym poziomie
                'name' => 'Loyalty',
            ],
            [
                'class_name' => 'AdminRewards',
                'parent_class' => 'AdminParentCustomer',
                'name' => 'Rewards',
            ],
        ];

        foreach ($tabs as $tabData) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $tabData['class_name'];
            $tab->module = $this->name;
            $tab->id_parent = Tab::getIdFromClassName($tabData['parent_class']);

            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $tabData['name'];
            }

            $tab->add();
        }

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('LOYALTY_POINTS_PER_EURO');
        Configuration::deleteByName('LOYALTY_WELCOME_BONUS');
        Configuration::deleteByName('LOYALTY_REFERRAL_BONUS');

        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'loyalty_points`';
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'loyalty_history`';
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'loyalty_rewards`';
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'loyalty_referrals`';

        foreach ($sql as $query) {
            Db::getInstance()->execute($query);
        }

        return $this->uninstallTabs() && parent::uninstall();
    }

    private function uninstallTabs()
    {
        $tabs = ['AdminLoyalty', 'AdminRewards'];
        foreach ($tabs as $className) {
            $idTab = (int)Tab::getIdFromClassName($className);
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }
        return true;
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $customer = $params['newCustomer'];
        $this->createLoyaltyAccount($customer->id);
        $this->addPoints(
            $customer->id,
            Configuration::get('LOYALTY_WELCOME_BONUS'),
            'registration',
            null,
            'Welcome bonus'
        );
    }

    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $customer = $params['customer'];
        
        $points = $this->calculatePointsForOrder($order);
        $this->addPoints(
            $customer->id,
            $points,
            'order',
            $order->id,
            'Order #'.$order->id
        );

        $this->checkLevelUp($customer->id);
    }

    public function hookDisplayCustomerAccount()
    {
        return $this->display(__FILE__, 'views/templates/front/my_account_link.tpl');
    }

    public function hookDisplayHeader()
    {
        if (file_exists(_PS_MODULE_DIR_.$this->name.'/views/css/loyalty.css')) {
            $this->context->controller->addCSS($this->_path.'views/css/loyalty.css');
        }
    }

    public function createLoyaltyAccount($id_customer)
    {
        $referral_code = $this->generateReferralCode($id_customer);

        return Db::getInstance()->insert('loyalty_points', [
            'id_customer' => (int)$id_customer,
            'points' => 0,
            'points_spent' => 0,
            'membership_level' => 1,
            'referral_code' => pSQL($referral_code),
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }

    public function addPoints($id_customer, $points, $type, $id_order = null, $note = null)
    {
        if ($points <= 0) return false;

        Db::getInstance()->execute(
            'UPDATE `'._DB_PREFIX_.'loyalty_points` 
            SET points = points + '.(int)$points.' 
            WHERE id_customer = '.(int)$id_customer
        );

        return Db::getInstance()->insert('loyalty_history', [
            'id_customer' => (int)$id_customer,
            'points' => (int)$points,
            'action_type' => pSQL($type),
            'id_order' => $id_order ? (int)$id_order : null,
            'note' => $note ? pSQL($note) : null,
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }

    public function calculatePointsForOrder($order)
    {
        $total = $order->total_paid;
        $points_per_euro = (int)Configuration::get('LOYALTY_POINTS_PER_EURO');
        return floor($total * $points_per_euro);
    }

    public function getLoyaltyAccount($id_customer)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'loyalty_points` 
            WHERE id_customer = '.(int)$id_customer
        );
    }

    public function generateReferralCode($id_customer)
    {
        return 'REF'.strtoupper(substr(md5($id_customer.time()), 0, 8));
    }

    public function checkLevelUp($id_customer)
    {
        $account = $this->getLoyaltyAccount($id_customer);
        if (!$account) return;

        $total_points = $account['points'] + $account['points_spent'];

        $levels = [
            1 => 0,
            2 => 500,
            3 => 1500,
            4 => 3000,
            5 => 5000
        ];

        $new_level = 1;
        foreach ($levels as $level => $required_points) {
            if ($total_points >= $required_points) {
                $new_level = $level;
            }
        }

        if ($new_level > $account['membership_level']) {
            Db::getInstance()->update('loyalty_points', [
                'membership_level' => $new_level
            ], 'id_customer = '.(int)$id_customer);

            $this->addPoints(
                $id_customer,
                $new_level * 50,
                'level_up',
                null,
                'Level '.$new_level.' bonus'
            );
        }
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitLoyaltyConfig')) {
            Configuration::updateValue('LOYALTY_POINTS_PER_EURO', Tools::getValue('LOYALTY_POINTS_PER_EURO'));
            Configuration::updateValue('LOYALTY_WELCOME_BONUS', Tools::getValue('LOYALTY_WELCOME_BONUS'));
            Configuration::updateValue('LOYALTY_REFERRAL_BONUS', Tools::getValue('LOYALTY_REFERRAL_BONUS'));
            $output .= $this->displayConfirmation($this->trans('Settings updated'));
        }

        $stats = [
            'total_members' => Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `'._DB_PREFIX_.'loyalty_points`'
            ),
            'total_points_issued' => Db::getInstance()->getValue(
                'SELECT SUM(points) FROM `'._DB_PREFIX_.'loyalty_history` WHERE points > 0'
            )
        ];

        $this->context->smarty->assign(['stats' => $stats]);

        return $output.$this->display(__FILE__, 'views/templates/admin/configure.tpl').$this->renderConfigForm();
    }

    private function renderConfigForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Points per €1'),
                        'name' => 'LOYALTY_POINTS_PER_EURO',
                        'class' => 'fixed-width-sm'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Welcome bonus'),
                        'name' => 'LOYALTY_WELCOME_BONUS',
                        'class' => 'fixed-width-sm'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Referral bonus'),
                        'name' => 'LOYALTY_REFERRAL_BONUS',
                        'class' => 'fixed-width-sm'
                    ]
                ],
                'submit' => [
                    'title' => $this->trans('Save'),
                    'name' => 'submitLoyaltyConfig'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->submit_action = 'submitLoyaltyConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => [
                'LOYALTY_POINTS_PER_EURO' => Configuration::get('LOYALTY_POINTS_PER_EURO'),
                'LOYALTY_WELCOME_BONUS' => Configuration::get('LOYALTY_WELCOME_BONUS'),
                'LOYALTY_REFERRAL_BONUS' => Configuration::get('LOYALTY_REFERRAL_BONUS')
            ]
        ];

        return $helper->generateForm([$fields_form]);
    }
}