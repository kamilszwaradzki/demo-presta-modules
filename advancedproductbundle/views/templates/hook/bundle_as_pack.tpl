<div class="bundle-deal-box" style="background:#f0f8ff;border:2px solid #4CAF50;padding:15px;border-radius:8px;margin:20px 0;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <span class="badge" style="background:#4CAF50;color:white;font-size:14px;padding:8px 15px;">
            🎁 {l s='Bundle Pack' mod='advancedproductbundle'}
        </span>
        <span style="color:#4CAF50;font-size:18px;font-weight:bold;">
            {l s='Save' mod='advancedproductbundle'} {$savings}!
        </span>
    </div>

    <div style="margin-bottom:15px;">
        <div style="font-size:14px;color:#666;margin-bottom:5px;">
            {l s='Pack includes' mod='advancedproductbundle'}:
        </div>
        <ul style="margin:0;padding-left:20px;">
            {foreach from=$pack_items item=item}
                <li>{$item->pack_quantity}x {$item->name}</li>
            {/foreach}
        </ul>
    </div>

    <div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;">
        <div>
            <div style="text-decoration:line-through;color:#999;font-size:16px;">
                {$original_price}
            </div>
            <div style="font-size:24px;color:#4CAF50;font-weight:bold;">
                {$pack_price}
            </div>
        </div>
        
        <!-- Użyj NATIVNEGO przycisku PrestaShop do dodawania packa -->
        <a href="{$link->getPageLink('cart', true, null, ['add' => 1, 'id_product' => $pack_product_id, 'ipa' => 0, 'qty' => 1])|escape:'html'}" 
           class="btn btn-primary" 
           data-button-action="add-to-cart"
           style="background:#4CAF50;border-color:#4CAF50;padding:12px 24px;font-size:16px;font-weight:bold;">
            <i class="icon-shopping-cart"></i> 
            {l s='Add Pack to Cart' mod='advancedproductbundle'}
        </a>
    </div>
</div>