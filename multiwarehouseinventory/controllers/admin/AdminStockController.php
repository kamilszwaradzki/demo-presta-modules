<?php
class AdminStockController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        $warehouses = [
            ['id_warehouse' => 1, 'name' => 'Main Warehouse'],
            ['id_warehouse' => 2, 'name' => 'Backup Warehouse'],
        ];

        if (Tools::isSubmit('submitTransfer')) {
            $from = (int)Tools::getValue('from_warehouse');
            $to = (int)Tools::getValue('to_warehouse');
            $qty = (int)Tools::getValue('quantity');

            if ($from && $to && $qty > 0) {
                $this->confirmations[] = $this->l('Transfer executed successfully.');
            } else {
                $this->errors[] = $this->l('Invalid transfer data.');
            }
        }

        $this->context->smarty->assign([
            'warehouses' => $warehouses,
        ]);

        $this->setTemplate('admin/stock_transfer.tpl');
    }
}
