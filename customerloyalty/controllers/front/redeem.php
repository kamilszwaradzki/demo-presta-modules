<?php
class CustomerLoyaltyRedeemModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $availableRewards = [
            ['id_reward' => 1, 'name' => '10% discount', 'points_required' => 50],
            ['id_reward' => 2, 'name' => 'Free Shipping', 'points_required' => 100],
        ];

        $this->context->smarty->assign([
            'rewards' => $availableRewards,
        ]);

        $this->setTemplate('front/rewards.tpl');
    }
}
