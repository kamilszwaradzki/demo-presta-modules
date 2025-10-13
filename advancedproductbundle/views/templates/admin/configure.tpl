<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Bundle Module Configuration' mod='advancedproductbundle'}
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <h4>{l s='How to use this module:' mod='advancedproductbundle'}</h4>
            <ol>
                <li>{l s='Go to Catalog > Products' mod='advancedproductbundle'}</li>
                <li>{l s='Edit any product' mod='advancedproductbundle'}</li>
                <li>{l s='Scroll down to "Product Bundle Configuration" panel' mod='advancedproductbundle'}</li>
                <li>{l s='Add products to create a bundle' mod='advancedproductbundle'}</li>
                <li>{l s='Set discount and activate the bundle' mod='advancedproductbundle'}</li>
            </ol>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-bar-chart"></i> {l s='Statistics' mod='advancedproductbundle'}
                    </div>
                    <div class="panel-body text-center">
                        <div style="font-size:48px;color:#4CAF50;font-weight:bold;">
                            {$total_bundles}
                        </div>
                        <div>{l s='Active Bundles' mod='advancedproductbundle'}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-shopping-cart"></i> {l s='Sales' mod='advancedproductbundle'}
                    </div>
                    <div class="panel-body text-center">
                        <div style="font-size:48px;color:#2196F3;font-weight:bold;">
                            {$total_sales}
                        </div>
                        <div>{l s='Bundles Sold (30 days)' mod='advancedproductbundle'}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon-money"></i> {l s='Revenue' mod='advancedproductbundle'}
                    </div>
                    <div class="panel-body text-center">
                        <div style="font-size:36px;color:#FF9800;font-weight:bold;">
                            {displayPrice price=$total_revenue}
                        </div>
                        <div>{l s='Bundle Revenue (30 days)' mod='advancedproductbundle'}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-list"></i> {l s='Recent Bundles' mod='advancedproductbundle'}
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{l s='ID' mod='advancedproductbundle'}</th>
                            <th>{l s='Product Name' mod='advancedproductbundle'}</th>
                            <th>{l s='Items' mod='advancedproductbundle'}</th>
                            <th>{l s='Discount' mod='advancedproductbundle'}</th>
                            <th>{l s='Status' mod='advancedproductbundle'}</th>
                            <th>{l s='Actions' mod='advancedproductbundle'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $recent_bundles}
                            {foreach from=$recent_bundles item=bundle}
                            <tr>
                                <td>#{$bundle.id_bundle}</td>
                                <td><strong>{$bundle.product_name}</strong></td>
                                <td>{$bundle.items_count} {l s='products' mod='advancedproductbundle'}</td>
                                <td>
                                    {if $bundle.discount_type == 'percentage'}
                                        {$bundle.discount_value}%
                                    {else}
                                        {displayPrice price=$bundle.discount_value}
                                    {/if}
                                </td>
                                <td>
                                    {if $bundle.active}
                                        <span class="badge badge-success">{l s='Active' mod='advancedproductbundle'}</span>
                                    {else}
                                        <span class="badge badge-danger">{l s='Inactive' mod='advancedproductbundle'}</span>
                                    {/if}
                                </td>
                                <td>
                                    <a href="{$link->getAdminLink('AdminProducts')}&id_product={$bundle.id_product}&updateproduct" 
                                       class="btn btn-default btn-sm">
                                        <i class="icon-edit"></i> {l s='Edit' mod='advancedproductbundle'}
                                    </a>
                                </td>
                            </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="6" class="text-center">
                                    {l s='No bundles created yet' mod='advancedproductbundle'}
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>