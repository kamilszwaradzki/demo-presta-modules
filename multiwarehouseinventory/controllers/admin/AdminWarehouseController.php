<?php

class AdminWarehouseController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mwi_warehouse';
        $this->identifier = 'id_warehouse';
        $this->className = 'Warehouse';
        $this->lang = false;

        parent::__construct();

        $this->fields_list = [
            'id_warehouse' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Name'),
                'width' => 'auto'
            ],
            'city' => [
                'title' => $this->l('City')
            ],
            'priority' => [
                'title' => $this->l('Priority'),
                'align' => 'center'
            ],
            'active' => [
                'title' => $this->l('Active'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected warehouses?')
            ]
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowAction('view');
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Warehouse'),
                'icon' => 'icon-home'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => true
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Address'),
                    'name' => 'address'
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('City'),
                    'name' => 'city'
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Postcode'),
                    'name' => 'postcode'
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Phone'),
                    'name' => 'phone'
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Priority'),
                    'name' => 'priority',
                    'class' => 'fixed-width-sm'
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')]
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];

        return parent::renderForm();
    }

    public function renderView()
    {
        $warehouse = new Warehouse((int)Tools::getValue('id_warehouse'));
        $stock = $warehouse->getAllStock();
        $movements = StockMovement::getByWarehouse($warehouse->id, 50);

        $this->context->smarty->assign([
            'warehouse' => $warehouse,
            'stock' => $stock,
            'movements' => $movements,
            'total_value' => $warehouse->getTotalValue()
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_.'multiwarehouseinventory/views/templates/admin/warehouse_view.tpl'
        );
    }
}