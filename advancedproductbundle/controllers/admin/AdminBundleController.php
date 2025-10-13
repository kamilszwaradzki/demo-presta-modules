<?php

class AdminBundleController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product_bundle';
        $this->identifier = 'id_bundle';
        $this->className = 'Bundle';
        $this->lang = false;

        parent::__construct();

        $this->fields_list = [
            'id_bundle' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'id_product' => [
                'title' => $this->l('Product ID'),
                'align' => 'center'
            ],
            'product_name' => [
                'title' => $this->l('Product Name'),
                'width' => 'auto'
            ],
            'discount_value' => [
                'title' => $this->l('Discount'),
                'align' => 'center',
                'callback' => 'displayDiscount'
            ],
            'items_count' => [
                'title' => $this->l('Items'),
                'align' => 'center'
            ],
            'active' => [
                'title' => $this->l('Active'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ],
            'date_add' => [
                'title' => $this->l('Created'),
                'type' => 'datetime'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            ]
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function renderList()
    {
        $this->_select = '
            pl.name as product_name,
            (SELECT COUNT(*) FROM '._DB_PREFIX_.'product_bundle_item 
             WHERE id_bundle = a.id_bundle) as items_count';
        
        $this->_join = '
            LEFT JOIN '._DB_PREFIX_.'product_lang pl 
            ON (a.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.')';

        return parent::renderList();
    }

    public function displayDiscount($value, $row)
    {
        if ($row['discount_type'] == 'percentage') {
            return $value . '%';
        } else {
            return Tools::displayPrice($value);
        }
    }

    public function renderView()
    {
        $bundle = new Bundle((int)Tools::getValue('id_bundle'));
        $items = $bundle->getItems();

        $this->context->smarty->assign([
            'bundle' => $bundle,
            'items' => $items,
            'product' => new Product($bundle->id_product, false, $this->context->language->id)
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_.'advancedproductbundle/views/templates/admin/bundle_view.tpl'
        );
    }

    public function postProcess()
    {
        if (Tools::isSubmit('ajax') && Tools::getValue('action') == 'toggleStatus') {
            $id_bundle = (int)Tools::getValue('id_bundle');
            $bundle = new Bundle($id_bundle);
            
            if (Validate::isLoadedObject($bundle)) {
                $bundle->active = !$bundle->active;
                $bundle->save();
                
                die(json_encode([
                    'success' => true,
                    'active' => $bundle->active
                ]));
            }
        }

        return parent::postProcess();
    }
}