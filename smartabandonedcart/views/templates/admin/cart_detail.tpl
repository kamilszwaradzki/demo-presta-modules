<div class="panel">
    <div class="panel-heading">
        <i class="icon-shopping-cart"></i> 
        {l s='Abandoned Cart Details' mod='smartabandonedcart'} #{$cart->id_abandoned_cart}
    </div>
    <div class="panel-body">
        
        {* Informacje o kliencie *}
        <div class="row">
            <div class="col-md-6">
                <h4>{l s='Customer Information' mod='smartabandonedcart'}</h4>
                <dl class="well list-detail">
                    <dt>{l s='Name' mod='smartabandonedcart'}</dt>
                    <dd>{$customer->firstname} {$customer->lastname}</dd>
                    
                    <dt>{l s='Email' mod='smartabandonedcart'}</dt>
                    <dd><a href="mailto:{$cart->email}">{$cart->email}</a></dd>
                    
                    <dt>{l s='Customer ID' mod='smartabandonedcart'}</dt>
                    <dd>#{$cart->id_customer}</dd>
                </dl>
            </div>
            
            <div class="col-md-6">
                <h4>{l s='Cart Information' mod='smartabandonedcart'}</h4>
                <dl class="well list-detail">
                    <dt>{l s='Cart Total' mod='smartabandonedcart'}</dt>
                    <dd><strong class="text-success">{displayPrice price=$cart->cart_total}</strong></dd>
                    
                    <dt>{l s='Abandoned Date' mod='smartabandonedcart'}</dt>
                    <dd>{dateFormat date=$cart->abandoned_date full=true}</dd>
                    
                    <dt>{l s='Status' mod='smartabandonedcart'}</dt>
                    <dd>
                        {if $cart->recovered}
                            <span class="badge badge-success">{l s='Recovered' mod='smartabandonedcart'}</span>
                            <br><small>{dateFormat date=$cart->recovery_date full=true}</small>
                        {else}
                            <span class="badge badge-warning">{l s='Pending' mod='smartabandonedcart'}</span>
                        {/if}
                    </dd>
                    
                    <dt>{l s='Reminders Sent' mod='smartabandonedcart'}</dt>
                    <dd>{$cart->reminder_count} / 3</dd>
                    
                    {if $cart->discount_code}
                        <dt>{l s='Discount Code' mod='smartabandonedcart'}</dt>
                        <dd><code>{$cart->discount_code}</code></dd>
                    {/if}
                </dl>
            </div>
        </div>
        
        {* Produkty w koszyku *}
        <h4>{l s='Products in Cart' mod='smartabandonedcart'}</h4>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='Image' mod='smartabandonedcart'}</th>
                        <th>{l s='Product' mod='smartabandonedcart'}</th>
                        <th class="text-center">{l s='Quantity' mod='smartabandonedcart'}</th>
                        <th class="text-right">{l s='Unit Price' mod='smartabandonedcart'}</th>
                        <th class="text-right">{l s='Total' mod='smartabandonedcart'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$products item=product}
                    <tr>
                        <td>
                            {if isset($product.id_image) && $product.id_image}
                                <img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'small_default')}" 
                                     alt="{$product.name}" 
                                     style="max-width: 50px;">
                            {else}
                                <img src="{$img_dir}p/{$lang_iso}-default-small_default.jpg" 
                                     alt="{$product.name}" 
                                     style="max-width: 50px;">
                            {/if}
                        </td>
                        <td>
                            <strong>{$product.name}</strong>
                            {if isset($product.attributes_small)}
                                <br><small>{$product.attributes_small}</small>
                            {/if}
                            <br><small class="text-muted">REF: {$product.reference}</small>
                        </td>
                        <td class="text-center">{$product.cart_quantity}</td>
                        <td class="text-right">{displayPrice price=$product.price_wt}</td>
                        <td class="text-right">
                            <strong>{displayPrice price=$product.total_wt}</strong>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right"><strong>{l s='Total' mod='smartabandonedcart'}:</strong></td>
                        <td class="text-right">
                            <strong class="text-success" style="font-size: 18px;">
                                {displayPrice price=$cart->cart_total}
                            </strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        {* Email campaign history *}
        <h4>{l s='Email Campaign History' mod='smartabandonedcart'}</h4>
        {if $emails && count($emails) > 0}
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{l s='Email Type' mod='smartabandonedcart'}</th>
                            <th>{l s='Sent Date' mod='smartabandonedcart'}</th>
                            <th class="text-center">{l s='Opened' mod='smartabandonedcart'}</th>
                            <th class="text-center">{l s='Clicked' mod='smartabandonedcart'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$emails item=email}
                        <tr>
                            <td>
                                {if $email.email_type == 'reminder_1'}
                                    <span class="badge badge-info">{l s='First Reminder' mod='smartabandonedcart'}</span>
                                {elseif $email.email_type == 'reminder_2'}
                                    <span class="badge badge-warning">{l s='Second Reminder + Discount' mod='smartabandonedcart'}</span>
                                {else}
                                    <span class="badge badge-danger">{l s='Final Reminder' mod='smartabandonedcart'}</span>
                                {/if}
                            </td>
                            <td>{dateFormat date=$email.sent_date full=true}</td>
                            <td class="text-center">
                                {if $email.opened}
                                    <i class="icon-check text-success"></i>
                                    {if $email.opened_date}
                                        <br><small>{dateFormat date=$email.opened_date full=true}</small>
                                    {/if}
                                {else}
                                    <i class="icon-remove text-muted"></i>
                                {/if}
                            </td>
                            <td class="text-center">
                                {if $email.clicked}
                                    <i class="icon-check text-success"></i>
                                    {if $email.clicked_date}
                                        <br><small>{dateFormat date=$email.clicked_date full=true}</small>
                                    {/if}
                                {else}
                                    <i class="icon-remove text-muted"></i>
                                {/if}
                            </td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <div class="alert alert-info">
                <i class="icon-info-circle"></i> {l s='No emails sent yet for this cart.' mod='smartabandonedcart'}
            </div>
        {/if}
        
    </div>
    
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminAbandonedCart')}" class="btn btn-default">
            <i class="icon-arrow-left"></i> {l s='Back to list' mod='smartabandonedcart'}
        </a>
        
        {if !$cart->recovered}
            <button type="button" class="btn btn-primary" onclick="sendManualReminder({$cart->id_abandoned_cart})">
                <i class="icon-envelope"></i> {l s='Send Manual Reminder' mod='smartabandonedcart'}
            </button>
        {/if}
        
        <a href="mailto:{$cart->email}" class="btn btn-info">
            <i class="icon-envelope"></i> {l s='Contact Customer' mod='smartabandonedcart'}
        </a>
    </div>
</div>

<script>
function sendManualReminder(cartId) {
    if (!confirm('{l s='Send reminder email now?' mod='smartabandonedcart' js=1}')) {
        return;
    }
    
    $.ajax({
        url: '{$link->getAdminLink('AdminAbandonedCart')|escape:'javascript':'UTF-8'}',
        method: 'POST',
        data: {
            ajax: true,
            action: 'sendManualReminder',
            id_abandoned_cart: cartId
        },
        success: function(response) {
            if (response.success) {
                showSuccessMessage('{l s='Email sent successfully!' mod='smartabandonedcart' js=1}');
                location.reload();
            } else {
                showErrorMessage(response.error || '{l s='Error sending email' mod='smartabandonedcart' js=1}');
            }
        },
        error: function() {
            showErrorMessage('{l s='Error sending email' mod='smartabandonedcart' js=1}');
        }
    });
}
</script>

<style>
.list-detail dt {
    font-weight: bold;
    margin-top: 10px;
}
.list-detail dd {
    margin-left: 0;
    padding-left: 0;
}
</style>