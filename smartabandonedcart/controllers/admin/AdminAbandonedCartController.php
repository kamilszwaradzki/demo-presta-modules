<?php

class AdminAbandonedCartController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'abandoned_cart';
        $this->identifier = 'id_abandoned_cart';
        $this->className = 'AbandonedCart';
        $this->lang = false;

        parent::__construct();

        $this->fields_list = [
            'id_abandoned_cart' => [
                'title' => $this->trans('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'email' => [
                'title' => $this->trans('Email')
            ],
            'cart_total' => [
                'title' => $this->trans('Total'),
                'type' => 'price',
                'align' => 'right'
            ],
            'abandoned_date' => [
                'title' => $this->trans('Date'),
                'type' => 'datetime'
            ],
            'reminder_count' => [
                'title' => $this->trans('Reminders'),
                'align' => 'center'
            ],
            'recovered' => [
                'title' => $this->trans('Recovered'),
                'type' => 'bool',
                'align' => 'center'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected'),
                'confirm' => $this->trans('Delete selected items?')
            ]
        ];
    }

    public function renderView()
    {
        $cart = new AbandonedCart((int)Tools::getValue('id_abandoned_cart'));
        $products = $cart->getProducts();
        $emails = EmailCampaign::getEmailStats($cart->id);

        $this->context->smarty->assign([
            'cart' => $cart,
            'products' => $products,
            'emails' => $emails,
            'customer' => $cart->getCustomer(),
            'link' => Context::getContext()->link,
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_.'smartabandonedcart/views/templates/admin/cart_detail.tpl'
        );
    }
}