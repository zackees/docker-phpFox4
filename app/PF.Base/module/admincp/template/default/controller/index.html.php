<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if Phpfox::demoModeActive()}
<div class="message">
    {_p('AdminCP is set to "Demo Mode". Certain actions are limited when in this mode and acts as a Read Only control
    panel.')}
</div>
{/if}
<div class="dashboard">
    <div class="row">
        <div class="col-md-9">
            {module name='admincp.stat'}
            {block location='6'}
            <div class="row">
                <div class="col-md-4">
                    {template file='admincp.block.trial'}
                    {block location='1'}
                </div>
                <div class="col-md-8">
                    {block location='2'}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            {block location='3'}
        </div>
    </div>
</div>
