<div class="panel">
    <div class="panel-heading">
        <i class="icon-shopping-cart"></i> {l s='Recent Abandoned Carts' mod='smartabandonedcart'}
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>{l s='ID' mod='smartabandonedcart'}</th>
                    <th>{l s='Email' mod='smartabandonedcart'}</th>
                    <th>{l s='Total' mod='smartabandonedcart'}</th>
                    <th>{l s='Date' mod='smartabandonedcart'}</th>
                    <th>{l s='Reminders' mod='smartabandonedcart'}</th>
                    <th>{l s='Status' mod='smartabandonedcart'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$recent_carts item=cart}
                <tr>
                    <td>{$cart.id_abandoned_cart}</td>
                    <td>{$cart.email}</td>
                    <td>{displayPrice price=$cart.cart_total}</td>
                    <td>{$cart.abandoned_date}</td>
                    <td class="text-center">{$cart.reminder_count}</td>
                    <td>
                        {if $cart.recovered}
                            <span class="badge badge-success">{l s='Recovered' mod='smartabandonedcart'}</span>
                        {else}
                            <span class="badge badge-warning">{l s='Pending' mod='smartabandonedcart'}</span>
                        {/if}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>