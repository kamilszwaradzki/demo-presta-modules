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
                'title' => $this->trans('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->trans('Name'),
                'width' => 'auto'
            ],
            'city' => [
                'title' => $this->trans('City')
            ],
            'priority' => [
                'title' => $this->trans('Priority'),
                'align' => 'center'
            ],
            'active' => [
                'title' => $this->trans('Active'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->trans('Delete selected warehouses?')
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
                'title' => $this->trans('Warehouse'),
                'icon' => 'icon-home'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->trans('Name'),
                    'name' => 'name',
                    'required' => true
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->trans('Address'),
                    'name' => 'address'
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('City'),
                    'name' => 'city'
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Postcode'),
                    'name' => 'postcode'
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Phone'),
                    'name' => 'phone'
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Priority'),
                    'name' => 'priority',
                    'class' => 'fixed-width-sm'
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Active'),
                    'name' => 'active',
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No')]
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->trans('Save')
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