<?php
if (!defined('_PS_VERSION_')) exit;

class CustomerLoyalty extends Module
{
    public function __construct()
    {
        $this->name = 'customerloyalty';
        $this->tab = 'market_place';
        $this->version = '1.0.0';
        $this->author = 'Kamil Szwaradzki';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Customer Loyalty & Rewards');
        $this->description = $this->l('Complete loyalty program with gamification');
    }

    public function install()
    {
        return parent::install() &&
            $this->installDb() &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('actionProductReview') &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('displayHeader') &&
            $this->installTabs();
    }

    public function installDb()
    {
        $sql = [];

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'loyalty_points` (
            `id_loyalty` int(11) NOT NULL AUTO_INCREMENT,
            `id_customer` int(11) NOT NULL,
            `points` int(11) DEFAULT 0,
            `points_spent` int(11) DEFAULT 0,
            `membership_level` int(11) DEFAULT 1,
            `referral_code` varchar(50) UNIQUE,
            PRIMARY KEY (`id_loyalty`),
            UNIQUE KEY `id_customer` (`id_customer`)
        ) ENGINE='._MYSQL_ENGINE_;

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'loyalty_history` (
            `id_history` int(11) NOT NULL AUTO_INCREMENT,
            `id_customer` int(11) NOT NULL,
            `points` int(11) NOT NULL,
            `action_type` varchar(50) NOT NULL,
            `id_order` int(11),
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_history`)
        ) ENGINE='._MYSQL_ENGINE_;

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'loyalty_rewards` (
            `id_reward` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `points_required` int(11) NOT NULL,
            `reward_type` enum("discount","product","voucher") NOT NULL,
            `reward_value` varchar(255),
            `min_level` int(11) DEFAULT 1,
            `active` tinyint(1) DEFAULT 1,
            PRIMARY KEY (`id_reward`)
        ) ENGINE='._MYSQL_ENGINE_;

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'loyalty_referrals` (
            `id_referral` int(11) NOT NULL AUTO_INCREMENT,
            `id_customer_referrer` int(11) NOT NULL,
            `id_customer_referred` int(11) NOT NULL,
            `status` enum("pending","completed") DEFAULT "pending",
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_referral`)
        ) ENGINE='._MYSQL_ENGINE_;

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) return false;
        }
        return true;
    }

    public function hookActionValidateOrder($params) 
    { 
        $order = $params['order'];
        $customer = $params['customer'];
        $points = $this->calculatePointsForOrder($order);
        $this->addPoints($customer->id, $points, 'order', $order->id);
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $customer = $params['newCustomer'];
        $this->createLoyaltyAccount($customer->id);
        $this->addPoints($customer->id, 100, 'registration');
    }

    public function addPoints($id_customer, $points, $type, $id_order = null)
    {
        if (!$id_customer || !$points) {
            return false;
        }

        $loyalty = new LoyaltyPoints();
        $loyalty->getByCustomer($id_customer);

        if (!$loyalty->id_loyalty) {
            $loyalty->id_customer = $id_customer;
            $loyalty->points = 0;
            $loyalty->points_spent = 0;
            $loyalty->membership_level = 1;
            $loyalty->referral_code = 'REF'.$id_customer.strtoupper(substr(md5(uniqid()), 0, 6));
            $loyalty->add();
        }

        $loyalty->points += $points;
        $loyalty->update();

        $history = new LoyaltyHistory();
        $history->id_customer = $id_customer;
        $history->points = $points;
        $history->action_type = $type;
        $history->id_order = $id_order;
        $history->date_add = date('Y-m-d H:i:s');
        $history->add();

        $new_level = floor($loyalty->points / 100) + 1;
        if ($new_level > $loyalty->membership_level) {
            $loyalty->membership_level = $new_level;
            $loyalty->update();
        }

        return true;
    }

    public function calculatePointsForOrder($order)
    {
        if (!$order || !isset($order->total_paid)) {
            return 0;
        }

        return (int) floor($order->total_paid / 10);
    }

    public function getMembershipLevel($id_customer)
    {
        $loyalty = new LoyaltyPoints();
        $loyalty->getByCustomer($id_customer);
        return $loyalty->membership_level ?? 1;
    }

    public function redeemReward($id_customer, $id_reward)
    {
        $loyalty = new LoyaltyPoints();
        $loyalty->getByCustomer($id_customer);

        if (!$loyalty->id_loyalty) return false;

        $reward = Reward::getById($id_reward);
        if (!$reward || !$reward->active) return false;

        if ($loyalty->points < $reward->points_required) {
            return false;
        }

        $loyalty->points -= $reward->points_required;
        $loyalty->points_spent += $reward->points_required;
        $loyalty->update();

        $history = new LoyaltyHistory();
        $history->id_customer = $id_customer;
        $history->points = -$reward->points_required;
        $history->action_type = 'redeem_reward';
        $history->id_order = null;
        $history->date_add = date('Y-m-d H:i:s');
        $history->add();

        return true;
    }


    public function generateReferralCode($id_customer)
    {
        return 'REF' . $id_customer . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    public function processReferral($referral_code, $id_new_customer)
    {
        $referrer = new LoyaltyPoints();
        $row = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'loyalty_points` WHERE referral_code="'.pSQL($referral_code).'"');
        if (!$row) return false;

        $referrer->id_loyalty = $row['id_loyalty'];
        $referrer->id_customer = $row['id_customer'];
        $referrer->points = $row['points'];
        $referrer->points_spent = $row['points_spent'];

        $this->addPoints($referrer->id_customer, 50, 'referral');

        $referral = new Referral();
        $referral->id_customer_referrer = $referrer->id_customer;
        $referral->id_customer_referred = $id_new_customer;
        $referral->status = 'completed';
        $referral->date_add = date('Y-m-d H:i:s');
        $referral->add();

        return true;
    }

    public function getCustomerPoints($id_customer)
    {
        $loyalty = new LoyaltyPoints();
        $loyalty->getByCustomer($id_customer);
        return $loyalty->points ?? 0;
    }

    public function checkBirthdayRewards()
    {
        $today = date('m-d');

        $customers = Db::getInstance()->executeS('
            SELECT c.id_customer
            FROM `'._DB_PREFIX_.'customer` c
            WHERE DATE_FORMAT(c.birthday, "%m-%d") = "'.$today.'"'
        );

        foreach ($customers as $cust) {
            $this->addPoints($cust['id_customer'], 20, 'birthday');

            $history = new LoyaltyHistory();
            $history->id_customer = $cust['id_customer'];
            $history->points = 20;
            $history->action_type = 'birthday_bonus';
            $history->date_add = date('Y-m-d H:i:s');
            $history->add();
        }

        return true;
    }

}