<?php

class MultiWarehouseInventoryApiModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json; charset=utf-8');

        $token = Tools::getValue('token');
        $expectedToken = Configuration::get('MWI_API_TOKEN');

        if (!$expectedToken || $token !== $expectedToken) {
            $this->ajaxDie(json_encode(['error' => 'Unauthorized']));
        }

        $action = Tools::getValue('action');

        switch ($action) {
            case 'syncStock':
                $this->syncStock();
                break;

            case 'getStock':
                $this->getStock();
                break;

            default:
                $this->ajaxDie(json_encode(['error' => 'Invalid action']));
        }
    }

    private function syncStock()
    {
        $this->ajaxDie(json_encode([
            'status' => 'ok',
            'message' => 'Stock synchronized successfully',
        ]));
    }

    private function getStock()
    {
        $stockData = [
            ['id_product' => 1, 'quantity' => 42],
            ['id_product' => 2, 'quantity' => 15],
        ];

        $this->ajaxDie(json_encode($stockData));
    }
}
