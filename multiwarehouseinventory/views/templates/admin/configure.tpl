<div class="panel">
  <h3><i class="icon icon-archive"></i> {l s='Multi Warehouse Inventory' mod='multiwarehouseinventory'}</h3>

  <form method="post" action="">
    <input type="hidden" name="submitWarehouse" value="1" />
    <button type="submit" class="btn btn-primary">
      {l s='Save changes' mod='multiwarehouseinventory'}
    </button>
  </form>

  <h4>{l s='Warehouses' mod='multiwarehouseinventory'}</h4>
  {if $warehouses|@count > 0}
    <ul>
      {foreach from=$warehouses item=w}
        <li>{$w.name} ({$w.location})</li>
      {/foreach}
    </ul>
  {else}
    <p>{l s='No warehouses found.' mod='multiwarehouseinventory'}</p>
  {/if}

  <h4>{l s='Low stock products' mod='multiwarehouseinventory'}</h4>
  <p>{count($low_stock)} {l s='products below threshold' mod='multiwarehouseinventory'}</p>

  <h4>{l s='Recent movements' mod='multiwarehouseinventory'}</h4>
  <p>{count($recent_movements)} {l s='movements found' mod='multiwarehouseinventory'}</p>
</div>
