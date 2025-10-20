<?php

class AdminProductBundleController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product_bundle';
        $this->identifier = 'id_bundle';
        $this->className = 'ProductBundle';
        $this->lang = false;

        parent::__construct();

        $this->fields_list = [
            'id_bundle' => [
                'title' => $this->trans('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'bundle_name' => [
                'title' => $this->trans('Bundle Name'),
                'width' => 'auto'
            ],
            'discount_value' => [
                'title' => $this->trans('Discount'),
                'align' => 'center',
                'callback' => 'displayDiscount'
            ],
            'items_count' => [
                'title' => $this->trans('Items'),
                'align' => 'center'
            ],
            'active' => [
                'title' => $this->trans('Active'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ],
            'date_add' => [
                'title' => $this->trans('Created'),
                'type' => 'datetime'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->trans('Delete selected items?')
            ]
        ];

        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJqueryPlugin('select2');
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/bundle.js');

        Media::addJsDef([
            'bundle_ajax_url' => $this->context->link->getAdminLink('AdminProductBundle', true, [], [
                'ajax' => 1,
                'action' => 'searchProducts',
            ]),
        ]);
    }

    public function renderList()
    {
        $this->_select = '
            a.name as bundle_name,
            (SELECT COUNT(*) FROM '._DB_PREFIX_.'product_bundle_item 
             WHERE id_bundle = a.id_bundle) as items_count';

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
        $this->base_tpl_view = 'view.tpl';
        $id_bundle = (int)Tools::getValue('id_bundle');
        
        if (!$id_bundle) {
            $this->errors[] = $this->l('Bundle ID is required');
            return $this->renderList();
        }

        $bundle = new ProductBundle($id_bundle);
        if (!Validate::isLoadedObject($bundle)) {
            $this->errors[] = $this->l('Bundle not found');
            return $this->renderList();
        }

        $final_bundle_price = $bundle->calculatePrice();
        $total_original_price = $bundle->getOriginalPrice();
        $total_savings = $bundle->getSavings();

        // Przypisz dane do szablonu
        $this->tpl_view_vars = [
            'bundle' => $bundle,
            'bundle_products' => $this->getBundleProductsWithDetails($this->object->id),
            'total_original_price' => $this->formatPrice($total_original_price),
            'final_bundle_price' => $this->formatPrice($final_bundle_price),
            'total_savings' => $this->formatPrice($total_savings),
            'currency_sign' => $this->context->currency->sign,
            'back_link' => $this->context->link->getAdminLink('AdminProductBundle'),
            'edit_link' => $this->context->link->getAdminLink('AdminProductBundle') . '&updateproduct_bundle&id_bundle=' . $id_bundle,
        ];

        return parent::renderView();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('ajax') && Tools::getValue('action') == 'toggleStatus') {
            $id_bundle = (int)Tools::getValue('id_bundle');
            $bundle = new ProductBundle($id_bundle);
            
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

    public function renderForm()
    {
        $productsListHtml = $this->generateProductsListHtml();
        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('Bundle details', [], 'Modules.Advancedproductbundle.Admin'),
                'icon'  => 'icon-cogs',
            ],
            'input' => [
                [
                    'type' => 'hidden',
                    'name' => 'id_bundle',
                ],
                // Nazwa bundle'a
                [
                    'type' => 'text',
                    'label' => $this->trans('Bundle name', [], 'Admin.Global'),
                    'name' => 'name',
                    'required' => true,
                ],
                // MULTI SELECT z AJAX - produkty w bundle
                [
                    'type' => 'text',
                    'label' => $this->trans('Bundle products', [], 'Modules.Advancedproductbundle.Admin'),
                    'name' => 'bundle_products_input',
                    'class' => 'bundle-products-ajax-input',
                    'desc' => $this->trans('Start typing to search and select products', [], 'Modules.Advancedproductbundle.Admin'),
                ],
                // Typ zniżki
                [
                    'type' => 'select',
                    'label' => $this->trans('Discount type', [], 'Modules.Advancedproductbundle.Admin'),
                    'name' => 'discount_type',
                    'options' => [
                        'query' => [
                            ['id' => 'percentage', 'name' => $this->trans('Percent (%)', [], 'Admin.Global')],
                            ['id' => 'fixed',  'name' => $this->trans('Fixed amount', [], 'Admin.Global')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                // Wartość zniżki
                [
                    'type' => 'text',
                    'label' => $this->trans('Discount value', [], 'Admin.Global'),
                    'name' => 'discount_value',
                    'required' => true,
                ],
                // Active switch
                [
                    'type' => 'switch',
                    'label' => $this->trans('Active', [], 'Admin.Global'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->trans('Enabled', [], 'Admin.Global')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->trans('Disabled', [], 'Admin.Global')
                        ]
                    ],
                ],
                [
                    'type' => 'html',
                    'name' => 'bundle_products_list',
                    'html_content' => $productsListHtml,
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
            ],
        ];

        return parent::renderForm();
    }

    protected function generateProductsListHtml()
    {
        $bundleProducts = [];
        if ($this->object && $this->object->id) {
            $bundleProducts = $this->getBundleProductsWithDetails($this->object->id);
        }

        $html = '<div class="form-group">
                    <label class="control-label col-lg-3">Selected products</label>
                    <div class="col-lg-9">
                        <div id="bundle-products-list">';
        
        if (!empty($bundleProducts)) {
            foreach ($bundleProducts as $product) {
                $html .= '
                <div class="bundle-product-item well" data-product-id="' . $product['id_product'] . '">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>' . $product['name'] . '</strong>
                            <br><small>REF: ' . ($product['reference'] ?: 'N/A') . '</small>
                        </div>
                        <div class="col-md-3">
                            <input type="number" 
                                name="bundle_products[' . $product['id_product'] . '][quantity]" 
                                value="' . $product['quantity'] . '" 
                                min="1" 
                                class="form-control product-quantity">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-danger remove-product">
                                <i class="icon-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="bundle_products[' . $product['id_product'] . '][id_product]" value="' . $product['id_product'] . '">
                </div>';
            }
        } else {
            $html .= '<div class="alert alert-info">No products added to this bundle yet.</div>';
        }
        
        $html .= '</div></div></div>';
        
        return $html;
    }

    protected function getBundleProductsWithDetails($id_bundle)
    {
        $sql = 'SELECT pbi.*, pl.name, p.reference, p.price
                FROM `' . _DB_PREFIX_ . 'product_bundle_item` pbi
                LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.id_product = pbi.id_product)
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.id_product = p.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ')
                WHERE pbi.id_bundle = ' . (int)$id_bundle . '
                ORDER BY pbi.position';
        
        $products = Db::getInstance()->executeS($sql);
        
        // Formatuj dane
        foreach ($products as &$product) {
            $product['price_formatted'] = $this->formatPrice($product['price']);
            $product['subtotal'] = $product['price'] * $product['quantity'];
            $product['subtotal_formatted'] = $this->formatPrice($product['subtotal']);
        }
        
        return $products;
    }

    protected function formatPrice($price)
    {
        return Context::getContext()->getCurrentLocale()->formatPrice($price, $this->context->currency->iso_code);
    }

    protected function getSelectedProducts()
    {
        $products = [];
        
        if ($this->object && $this->object->id) {
            $bundleProducts = $this->getBundleProducts($this->object->id);
            foreach ($bundleProducts as $id_product) {
                $product = new Product($id_product, false, $this->context->language->id);
                if (Validate::isLoadedObject($product)) {
                    $products[] = [
                        'id_product' => $id_product,
                        'name' => $product->name . ' (REF: ' . $product->reference . ')'
                    ];
                }
            }
        }
        
        return $products;
    }

    protected function getBundleProducts($id_bundle)
    {
        $sql = 'SELECT id_product FROM `' . _DB_PREFIX_ . 'product_bundle_item` 
                WHERE id_bundle = ' . (int)$id_bundle;
        return Db::getInstance()->executeS($sql);
    }

    public function ajaxProcessSearchProducts()
    {
        $query = pSQL(Tools::getValue('q', ''));
        $idLang = (int)$this->context->language->id;

        if (Tools::strlen($query) < 2) {
            die(json_encode([]));
        }

        $sql = 'SELECT p.id_product, pl.name
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl
                    ON (p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ')
                WHERE pl.name LIKE "%' . $query . '%"
                ORDER BY pl.name ASC
                LIMIT 20';

        $results = Db::getInstance()->executeS($sql);

        $formatted = [];
        foreach ($results as $r) {
            $formatted[] = [
                'id' => (int)$r['id_product'],
                'text' => $r['name'] . ' (ID ' . $r['id_product'] . ')'
            ];
        }

        header('Content-Type: application/json');
        die(json_encode($formatted));
    }
}