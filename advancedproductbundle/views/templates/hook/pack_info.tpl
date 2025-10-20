{* views/templates/hook/pack_info.tpl *}
<div class="pack-info-box" style="background:#fff3cd;border:2px solid #ffc107;padding:15px;border-radius:8px;margin:20px 0;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <span class="badge" style="background:#ffc107;color:#000;font-size:14px;padding:8px 15px;">
            📦 {l s='Product Pack' mod='advancedproductbundle'}
        </span>
        {if $savings > 0}
        <span style="color:#856404;font-size:18px;font-weight:bold;">
            {l s='Save' mod='advancedproductbundle'} {$savings}!
        </span>
        {/if}
    </div>

    <div style="margin-bottom:15px;">
        <div style="font-size:14px;color:#856404;margin-bottom:5px;font-weight:bold;">
            {l s='This pack includes' mod='advancedproductbundle'}:
        </div>
        <ul style="margin:0;padding-left:20px;color:#856404;">
            {foreach from=$pack_items item=item}
            <li style="margin-bottom:5px;">
                <strong>{$item->pack_quantity}x</strong> 
                <a href="{$link->getProductLink($item->id)}" target="_blank" style="color:#856404;text-decoration:underline;">
                    {$item->name}
                </a>
                {if $item->reference}
                    <br><small style="color:#666;">REF: {$item->reference}</small>
                {/if}
            </li>
            {/foreach}
        </ul>
    </div>

    {if $savings > 0}
    <div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;padding:10px;background:#fff;border-radius:5px;">
        <div>
            <div style="text-decoration:line-through;color:#999;font-size:16px;">
                {$original_price}
            </div>
            <div style="font-size:24px;color:#856404;font-weight:bold;">
                {$pack_price}
            </div>
        </div>
        
        <div style="color:#28a745;font-weight:bold;font-size:16px;">
            ⚡ {l s='Better value when bought together!' mod='advancedproductbundle'}
        </div>
    </div>
    {/if}

    <div style="margin-top:10px;font-size:12px;color:#666;">
        <i class="icon-info-circle"></i>
        {l s='This product is part of a pack. You get all items together at a special price.' mod='advancedproductbundle'}
    </div>
</div>

<style>
.pack-info-box a:hover {
    color: #664d03 !important;
}
</style>