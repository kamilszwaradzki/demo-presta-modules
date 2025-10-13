<?php
class CustomerLoyaltyAccountModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $accountData = [
            'customer_name' => $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
            'points' => 120,
        ];

        $this->context->smarty->assign([
            'account' => $accountData,
        ]);

        $this->setTemplate('front/account.tpl');
    }
}
