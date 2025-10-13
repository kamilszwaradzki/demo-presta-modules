<div class="panel">
  <h3>{l s='Available Rewards' mod='customerloyalty'}</h3>
  <ul class="list-group">
    {foreach $rewards as $reward}
      <li class="list-group-item">
        {$reward.name|escape:'html':'UTF-8'} — {$reward.points_required} {l s='points' mod='customerloyalty'}
      </li>
    {/foreach}
  </ul>
</div>
