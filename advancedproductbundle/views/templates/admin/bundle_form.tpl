<div class="panel" id="bundle-panel">
    <div class="panel-heading">
        <i class="icon-gift"></i> {l s='Product Bundle Configuration' mod='advancedproductbundle'}
    </div>
    
    <div class="panel-body">
        <form id="bundle-form">
            <input type="hidden" name="id_product" value="{$id_product}">
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Enable Bundle' mod='advancedproductbundle'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="active" id="active_on" value="1" {if !$bundle || $bundle.active}checked{/if}>
                        <label for="active_on">{l s='Yes' mod='advancedproductbundle'}</label>
                        <input type="radio" name="active" id="active_off" value="0" {if $bundle && !$bundle.active}checked{/if}>
                        <label for="active_off">{l s='No' mod='advancedproductbundle'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Discount Type' mod='advancedproductbundle'}</label>
                <div class="col-lg-9">
                    <select name="discount_type" id="discount_type" class="form-control">
                        <option value="percentage" {if $bundle && $bundle.discount_type == 'percentage'}selected{/if}>
                            {l s='Percentage' mod='advancedproductbundle'}
                        </option>
                        <option value="fixed" {if $bundle && $bundle.discount_type == 'fixed'}selected{/if}>
                            {l s='Fixed Amount' mod='advancedproductbundle'}
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Discount Value' mod='advancedproductbundle'}</label>
                <div class="col-lg-9">
                    <div class="input-group">
                        <input type="text" name="discount_value" id="discount_value" 
                               class="form-control" 
                               value="{if $bundle}{$bundle.discount_value}{else}10{/if}">
                        <span class="input-group-addon" id="discount-suffix">%</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Bundle Products' mod='advancedproductbundle'}</label>
                <div class="col-lg-9">
                    <div id="bundle-products-list">
                        {if $bundle_items}
                            {foreach from=$bundle_items item=item}
                                <div class="bundle-product-row" style="margin-bottom:10px;">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <strong>{$item.name}</strong>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" 
                                                   name="bundle_products[{$item.id_product}][quantity]" 
                                                   value="{$item.quantity}" 
                                                   min="1" 
                                                   class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger btn-sm remove-product" 
                                                    data-product-id="{$item.id_product}">
                                                <i class="icon-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            {/foreach}
                        {/if}
                    </div>

                    <div class="row" style="margin-top:15px;">
                        <div class="col-md-10">
                            <select id="product-selector" class="form-control">
                                <option value="">{l s='-- Select Product --' mod='advancedproductbundle'}</option>
                                {foreach from=$all_products item=product}
                                    <option value="{$product.id_product}">{$product.name}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="add-product-btn" class="btn btn-success">
                                <i class="icon-plus"></i> {l s='Add' mod='advancedproductbundle'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Save Bundle' mod='advancedproductbundle'}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#discount_type').on('change', function() {
        $('#discount-suffix').text($(this).val() === 'percentage' ? '%' : '{$currency->sign}');
    });

    $('#add-product-btn').on('click', function() {
        var select = $('#product-selector');
        var id = select.val();
        var name = select.find('option:selected').text();
        
        if (!id) return;

        var html = '<div class="bundle-product-row" style="margin-bottom:10px;">' +
            '<div class="row">' +
            '<div class="col-md-8"><strong>' + name + '</strong></div>' +
            '<div class="col-md-2">' +
            '<input type="number" name="bundle_products[' + id + '][quantity]" value="1" min="1" class="form-control">' +
            '</div>' +
            '<div class="col-md-2">' +
            '<button type="button" class="btn btn-danger btn-sm remove-product"><i class="icon-trash"></i></button>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('#bundle-products-list').append(html);
        select.val('');
    });

    $(document).on('click', '.remove-product', function() {
        $(this).closest('.bundle-product-row').remove();
    });

    $('#bundle-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '{$link->getAdminLink('AdminModules')|escape:'javascript':'UTF-8'}&configure=advancedproductbundle&ajax=1&action=saveBundle',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                showSuccessMessage('{l s='Bundle saved successfully!' mod='advancedproductbundle' js=1}');
            },
            error: function() {
                showErrorMessage('{l s='Error saving bundle' mod='advancedproductbundle' js=1}');
            }
        });
    });
});
</script>