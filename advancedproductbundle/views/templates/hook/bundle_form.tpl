<form method="post" action="{$link->getModuleLink('advancedproductbundle', 'bundle', [], true)}">
  <input type="hidden" name="bundle_id" value="{$bundle.id}">
  
  {foreach from=$bundle.products item=product}
    <div class="bundle-item">
      <label>
        <input type="checkbox" name="bundle_products[]" value="{$product.id_product}" checked>
        {$product.name} ({$product.price} zł)
      </label>
    </div>
  {/foreach}
  
  <button type="submit" class="btn btn-primary">
    {l s='Dodaj zestaw do koszyka' mod='advancedproductbundle'}
  </button>
</form>
