<div class="panel">
  <h3><i class="icon-archive"></i> {l s='Warehouses' mod='multiwarehouseinventory'}</h3>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>{l s='ID' mod='multiwarehouseinventory'}</th>
          <th>{l s='Name' mod='multiwarehouseinventory'}</th>
          <th>{l s='Location' mod='multiwarehouseinventory'}</th>
        </tr>
      </thead>
      <tbody>
        {if isset($warehouses) && $warehouses|@count > 0}
          {foreach $warehouses as $w}
            <tr>
              <td>{$w.id_warehouse}</td>
              <td>{$w.name|escape:'html':'UTF-8'}</td>
              <td>{$w.location|escape:'html':'UTF-8'}</td>
            </tr>
          {/foreach}
        {else}
          <tr>
            <td colspan="3" class="text-center text-muted">
              {l s='No warehouses found.' mod='multiwarehouseinventory'}
            </td>
          </tr>
        {/if}
      </tbody>
    </table>
  </div>
</div>
