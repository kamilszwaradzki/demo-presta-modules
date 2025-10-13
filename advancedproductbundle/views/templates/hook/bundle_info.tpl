<div class="bundle-deal-box" style="background:#f0f8ff;border:2px solid #4CAF50;padding:15px;border-radius:8px;margin:20px 0;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <span class="badge" style="background:#4CAF50;color:white;font-size:14px;padding:8px 15px;">
            🎁 {l s='Bundle Deal' mod='advancedproductbundle'}
        </span>
        <span style="color:#4CAF50;font-size:18px;font-weight:bold;">
            {l s='Save' mod='advancedproductbundle'} {displayPrice price=$savings}!
        </span>
    </div>

    <div style="margin-bottom:15px;">
        <div style="font-size:14px;color:#666;margin-bottom:5px;">
            {l s='Bundle includes' mod='advancedproductbundle'}:
        </div>
        <ul style="margin:0;padding-left:20px;">
            {foreach from=$bundle_items item=item}
                <li>{$item.quantity}x {$item.name}</li>
            {/foreach}
        </ul>
    </div>

    <div style="display:flex;align-items:center;gap:15px;">
        <div>
            <div style="text-decoration:line-through;color:#999;font-size:16px;">
                {displayPrice price=$original_price}
            </div>
            <div style="font-size:24px;color:#4CAF50;font-weight:bold;">
                {displayPrice price=$bundle_price}
            </div>
        </div>
    </div>
</div>