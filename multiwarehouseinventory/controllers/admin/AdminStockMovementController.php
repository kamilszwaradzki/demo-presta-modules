<?php
class AdminStockMovementController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        $stats = [
            'total_warehouses' => 2,
            'total_items' => 542,
            'recent_transfers' => 7,
        ];

        $this->context->smarty->assign([
            'stats' => $stats,
        ]);

        $this->setTemplate('admin/dashboard.tpl');
    }
}
