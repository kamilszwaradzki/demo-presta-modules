<div class="panel">
    <div class="panel-heading">
        <i class="icon-bar-chart"></i> {l s='Statistics (Last 30 Days)' mod='smartabandonedcart'}
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3">
                <div class="metric-box">
                    <div class="metric-value">{$stats.total_abandoned}</div>
                    <div class="metric-label">{l s='Abandoned Carts' mod='smartabandonedcart'}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-box">
                    <div class="metric-value">{$stats.total_recovered}</div>
                    <div class="metric-label">{l s='Recovered' mod='smartabandonedcart'}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-box">
                    <div class="metric-value">{$stats.recovery_rate}%</div>
                    <div class="metric-label">{l s='Recovery Rate' mod='smartabandonedcart'}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-box">
                    <div class="metric-value">{displayPrice price=$stats.total_value_recovered}</div>
                    <div class="metric-label">{l s='Value Recovered' mod='smartabandonedcart'}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-envelope"></i> {l s='Email Campaign Stats' mod='smartabandonedcart'}
    </div>
    <div class="panel-body">
        <p><strong>{l s='Emails sent' mod='smartabandonedcart'}:</strong> {$stats.emails_sent}</p>
        <p><strong>{l s='Open rate' mod='smartabandonedcart'}:</strong> {$stats.open_rate}%</p>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-cog"></i> {l s='Cron Job Setup' mod='smartabandonedcart'}
    </div>
    <div class="panel-body">
        <p>{l s='Add this to your crontab to process abandoned carts every hour:' mod='smartabandonedcart'}</p>
        <pre>0 * * * * php {$cron_url}</pre>
        <p>{l s='Or use this URL in your cron service:' mod='smartabandonedcart'}</p>
        <pre>{$cron_url}</pre>
    </div>
</div>

<style>
.metric-box {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 5px;
}
.metric-value {
    font-size: 32px;
    font-weight: bold;
    color: #007bff;
}
.metric-label {
    color: #666;
    margin-top: 5px;
}
</style>