<div class="panel">
  <h3><i class="icon-random"></i> {l s='Stock Transfer' mod='multiwarehouseinventory'}</h3>
  
  <form method="post" action="">
    <div class="form-group">
      <label>{l s='From Warehouse' mod='multiwarehouseinventory'}</label>
      <select name="from_warehouse" class="form-control">
        {foreach $warehouses as $w}
          <option value="{$w.id_warehouse}">{$w.name}</option>
        {/foreach}
      </select>
    </div>

    <div class="form-group">
      <label>{l s='To Warehouse' mod='multiwarehouseinventory'}</label>
      <select name="to_warehouse" class="form-control">
        {foreach $warehouses as $w}
          <option value="{$w.id_warehouse}">{$w.name}</option>
        {/foreach}
      </select>
    </div>

    <div class="form-group">
      <label>{l s='Quantity' mod='multiwarehouseinventory'}</label>
      <input type="number" name="quantity" class="form-control" min="1" value="1">
    </div>

    <button type="submit" name="submitTransfer" class="btn btn-primary">
      <i class="icon-exchange"></i> {l s='Transfer' mod='multiwarehouseinventory'}
    </button>
  </form>
</div>
