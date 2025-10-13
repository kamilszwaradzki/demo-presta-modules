<?php
class AdminRewardsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        $rewards = [
            ['id_reward' => 1, 'name' => '10% discount', 'points_required' => 50],
            ['id_reward' => 2, 'name' => 'Free Shipping', 'points_required' => 100],
        ];

        $this->context->smarty->assign([
            'rewards' => $rewards,
        ]);

        $this->setTemplate('admin/dashboard.tpl');
    }
}
