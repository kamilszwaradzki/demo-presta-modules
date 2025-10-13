<?php

class EmailCampaign
{
    private $context;

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    public function sendReminder(AbandonedCart $abandonedCart, $type)
    {
        $customer = $abandonedCart->getCustomer();
        $cart = $abandonedCart->getCart();

        if (!Validate::isLoadedObject($customer) || !Validate::isLoadedObject($cart)) {
            return false;
        }

        $discount = null;
        if (in_array($type, ['reminder_2', 'reminder_3'])) {
            $discount = $this->generateDiscount($abandonedCart, $type);
        }

        $templateVars = $this->getTemplateVars($abandonedCart, $customer, $cart, $discount);
        $subject = $this->getSubject($type);

        $sent = Mail::Send(
            (int)$this->context->language->id,
            $type,
            $subject,
            $templateVars,
            $abandonedCart->email,
            $customer->firstname.' '.$customer->lastname,
            null, null
        );

        if ($sent) {
            $this->logEmail($abandonedCart->id, $type);
        }

        return $sent;
    }

    private function getTemplateVars($abandonedCart, $customer, $cart, $discount = null)
    {
        return [
            '{customer_name}' => $customer->firstname,
            '{cart_total}' => Tools::displayPrice($abandonedCart->cart_total),
            '{cart_url}' => $this->context->link->getPageLink('cart', true),
            '{discount_code}' => $discount ?: '',
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{products_html}' => $this->getProductsHtml($cart)
        ];
    }

    private function getProductsHtml($cart)
    {
        $products = $cart->getProducts();
        $html = '<ul>';
        foreach ($products as $product) {
            $html .= '<li>'.$product['name'].' x '.$product['quantity'].'</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    private function generateDiscount($abandonedCart, $type)
    {
        $code = 'SAC'.$abandonedCart->id_cart.'_'.strtoupper(substr($type, -1));
        $value = $type == 'reminder_2' ? 10 : 15;

        $rule = new CartRule();
        $rule->code = $code;
        $rule->id_customer = $abandonedCart->id_customer;
        $rule->reduction_percent = $value;
        $rule->quantity = 1;
        $rule->date_from = date('Y-m-d H:i:s');
        $rule->date_to = date('Y-m-d H:i:s', strtotime('+7 days'));
        $rule->active = 1;

        return $rule->add() ? $code : null;
    }

    private function getSubject($type)
    {
        $subjects = [
            'reminder_1' => 'You left something in your cart',
            'reminder_2' => 'Special offer: Complete your order!',
            'reminder_3' => 'Last chance: Your cart expires soon!'
        ];
        return $subjects[$type] ?? 'Your cart is waiting';
    }

    private function logEmail($id_abandoned_cart, $type)
    {
        return Db::getInstance()->insert('abandoned_cart_email', [
            'id_abandoned_cart' => (int)$id_abandoned_cart,
            'email_type' => pSQL($type),
            'sent_date' => date('Y-m-d H:i:s')
        ]);
    }

    public static function getEmailStats($id_abandoned_cart)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.'abandoned_cart_email` 
            WHERE id_abandoned_cart = '.(int)$id_abandoned_cart.' 
            ORDER BY sent_date ASC'
        );
    }
}