<div class="panel">
  <h3><i class="icon-bar-chart"></i> {l s='Warehouse Dashboard' mod='multiwarehouseinventory'}</h3>
  
  <ul class="list-group">
    <li class="list-group-item">
      {l s='Total warehouses:' mod='multiwarehouseinventory'} 
      <strong>{$stats.total_warehouses|default:0}</strong>
    </li>
    <li class="list-group-item">
      {l s='Total stock items:' mod='multiwarehouseinventory'} 
      <strong>{$stats.total_items|default:0}</strong>
    </li>
    <li class="list-group-item">
      {l s='Recent transfers:' mod='multiwarehouseinventory'} 
      <strong>{$stats.recent_transfers|default:0}</strong>
    </li>
  </ul>
</div>
