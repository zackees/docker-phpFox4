<?php 

defined('PHPFOX') or exit('NO DICE!'); 

?>
{if $hasAccess}
    {if !PHPFOX_IS_AJAX}
    <div id="js_basic_info_data" class="profile-basic-info">
        {/if}
        {if Phpfox::getParam('user.enable_relationship_status') && $sRelationship != ''}
        <div class="item">
            <div class="item-label">
                {_p var='relationship_status'}
            </div>
            <div class="item-value">
                {$sRelationship}
            </div>
        </div>
        {/if}
        {foreach from=$aUserDetails key=sKey item=sValue}
        {if !empty($sValue)}
        <div class="item">
            <div class="item-label">
                {$sKey}:
            </div>
            <div class="item-value">
                {$sValue}
            </div>
        </div>
        {/if}
        {/foreach}
        {foreach from=$aInfos key=sPhrase item=sValue}
        <div class="item">
            <div class="item-label">
                {$sPhrase}:
            </div>
            <div class="item-value">
                {$sValue}
            </div>
        </div>
        {/foreach}
        {if $bShowCustomFields}
        {module name='custom.display' type_id='user_panel' template='info'}
        {/if}
        {plugin call='profile.template_block_info'}
        {if !PHPFOX_IS_AJAX}
    </div>
    <div id="js_basic_info_form"></div>
    {/if}
{else}
    <div class="alert alert-info mt-2 ml-1">
        {_p var='you_are_not_allowed_to_view_this_basic_information'}
    </div>
{/if}