<div class="panel">
  <h3><i class="icon-trophy"></i> {l s='Loyalty & Rewards Dashboard' mod='customerloyalty'}</h3>
  
  {if isset($loyaltyData)}
    <h4>{l s='Customer Points' mod='customerloyalty'}</h4>
    <table class="table">
      <thead>
        <tr>
          <th>{l s='Customer' mod='customerloyalty'}</th>
          <th>{l s='Points' mod='customerloyalty'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $loyaltyData as $item}
          <tr>
            <td>{$item.name|escape:'html':'UTF-8'}</td>
            <td>{$item.points}</td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {/if}

  {if isset($rewards)}
    <h4>{l s='Available Rewards' mod='customerloyalty'}</h4>
    <ul>
      {foreach $rewards as $reward}
        <li>{$reward.name|escape:'html':'UTF-8'} ({$reward.points_required} {l s='points' mod='customerloyalty'})</li>
      {/foreach}
    </ul>
  {/if}
</div>
