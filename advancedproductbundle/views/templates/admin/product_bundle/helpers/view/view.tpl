{* 
Szablon dla renderView() 
Plik musi być w: modules/advancedproductbundle/views/templates/admin/bundle/view.tpl
*}

<div class="bootstrap">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-eye"></i> {l s='Bundle Details' mod='advancedproductbundle'} - {$bundle->name}
        </div>
        
        <!-- Basic Info -->
        <div class="row">
            <div class="col-lg-6">
                <div class="form-horizontal">
                    <div class="form-group">
                        <label class="control-label col-lg-4">{l s='Bundle Name' mod='advancedproductbundle'}:</label>
                        <div class="col-lg-8">
                            <p class="form-control-static">{$bundle->name}</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-4">{l s='Discount Type' mod='advancedproductbundle'}:</label>
                        <div class="col-lg-8">
                            <p class="form-control-static">
                                {if $bundle->discount_type == 'percentage'}
                                    <span class="label label-info">{l s='Percentage' mod='advancedproductbundle'}</span>
                                {else}
                                    <span class="label label-warning">{l s='Fixed Amount' mod='advancedproductbundle'}</span>
                                {/if}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-4">{l s='Discount Value' mod='advancedproductbundle'}:</label>
                        <div class="col-lg-8">
                            <p class="form-control-static">
                                {if $bundle->discount_type == 'percentage'}
                                    {$bundle->discount_value}%
                                {else}
                                    {$bundle->discount_value} {$currency_sign}
                                {/if}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="form-horizontal">
                    <div class="form-group">
                        <label class="control-label col-lg-4">{l s='Status' mod='advancedproductbundle'}:</label>
                        <div class="col-lg-8">
                            <p class="form-control-static">
                                {if $bundle->active}
                                    <span class="label label-success">{l s='Active' mod='advancedproductbundle'}</span>
                                {else}
                                    <span class="label label-danger">{l s='Inactive' mod='advancedproductbundle'}</span>
                                {/if}
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-4">{l s='Created' mod='advancedproductbundle'}:</label>
                        <div class="col-lg-8">
                            <p class="form-control-static">{$bundle->date_add|date_format:'%d/%m/%Y %H:%M'}</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-4">{l s='Updated' mod='advancedproductbundle'}:</label>
                        <div class="col-lg-8">
                            <p class="form-control-static">{$bundle->date_upd|date_format:'%d/%m/%Y %H:%M'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Section -->
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-list"></i> {l s='Products in Bundle' mod='advancedproductbundle'} 
            <span class="badge">{$bundle_products|count}</span>
        </div>
        
        {if $bundle_products && $bundle_products|count > 0}
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="80px">{l s='ID' mod='advancedproductbundle'}</th>
                            <th>{l s='Product' mod='advancedproductbundle'}</th>
                            <th width="100px">{l s='Quantity' mod='advancedproductbundle'}</th>
                            <th width="120px">{l s='Price' mod='advancedproductbundle'}</th>
                            <th width="150px">{l s='Subtotal' mod='advancedproductbundle'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$bundle_products item=product}
                        <tr>
                            <td class="text-center">{$product.id_product}</td>
                            <td>
                                <strong>{$product.name}</strong>
                                {if $product.reference}
                                    <br><small class="text-muted">REF: {$product.reference}</small>
                                {/if}
                            </td>
                            <td class="text-center">{$product.quantity}</td>
                            <td class="text-right">{$product.price_formatted}</td>
                            <td class="text-right">{$product.subtotal_formatted}</td>
                        </tr>
                        {/foreach}
                    </tbody>
                    <tfoot>
                        <tr class="success">
                            <td colspan="4" class="text-right"><strong>{l s='Total Original Price' mod='advancedproductbundle'}:</strong></td>
                            <td class="text-right"><strong>{$total_original_price}</strong></td>
                        </tr>
                        <tr class="warning">
                            <td colspan="4" class="text-right"><strong>{l s='Discount Applied' mod='advancedproductbundle'}:</strong></td>
                            <td class="text-right">
                                <strong>
                                    {if $bundle->discount_type == 'percentage'}
                                        -{$bundle->discount_value}%
                                    {else}
                                        -{$bundle->discount_value} {$currency_sign}
                                    {/if}
                                </strong>
                            </td>
                        </tr>
                        <tr class="info">
                            <td colspan="4" class="text-right"><strong>{l s='Final Bundle Price' mod='advancedproductbundle'}:</strong></td>
                            <td class="text-right"><strong>{$final_bundle_price}</strong></td>
                        </tr>
                        <tr class="active">
                            <td colspan="4" class="text-right"><strong>{l s='Total Savings' mod='advancedproductbundle'}:</strong></td>
                            <td class="text-right text-success"><strong>{$total_savings}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        {else}
            <div class="alert alert-warning">
                {l s='No products in this bundle.' mod='advancedproductbundle'}
            </div>
        {/if}
    </div>

    <!-- Actions -->
    <div class="panel">
        <div class="panel-footer">
            <a href="{$back_link}" class="btn btn-default">
                <i class="icon-arrow-left"></i> {l s='Back to list' mod='advancedproductbundle'}
            </a>
            <a href="{$edit_link}" class="btn btn-primary">
                <i class="icon-edit"></i> {l s='Edit Bundle' mod='advancedproductbundle'}
            </a>
        </div>
    </div>
</div>