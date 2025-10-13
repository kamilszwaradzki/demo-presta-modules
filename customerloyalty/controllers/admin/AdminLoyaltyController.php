<?php
class AdminLoyaltyController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        $loyaltyData = [
            ['id_customer' => 1, 'name' => 'Jan Kowalski', 'points' => 120],
            ['id_customer' => 2, 'name' => 'Anna Nowak', 'points' => 95],
        ];

        $this->context->smarty->assign([
            'loyaltyData' => $loyaltyData,
        ]);

        $this->setTemplate('admin/dashboard.tpl');
    }
}
