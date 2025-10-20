{extends file='page.tpl'}

{block name='page_title'}
  {l s='My loyalty points' mod='customerloyalty'}
{/block}

{block name='page_content'}
  <div class="card">
    <div class="card-body">
      <h2 class="h4 mb-3">
        {l s='Your loyalty points' mod='customerloyalty'}
      </h2>

      <p>
        {l s='Hello,' mod='customerloyalty'} {$customer.firstname}!<br>
        {l s='You currently have' mod='customerloyalty'} <strong>{$account.points}</strong> {l s='points.' mod='customerloyalty'}
      </p>

      <hr>

      <p class="text-muted">
        {l s='You can redeem your points for discounts or exclusive rewards.' mod='customerloyalty'}
      </p>

      <a href="{$link->getPageLink('my-account')}" class="btn btn-secondary">
        <i class="material-icons">arrow_back</i> {l s='Back to my account' mod='customerloyalty'}
      </a>
    </div>
  </div>
{/block}
