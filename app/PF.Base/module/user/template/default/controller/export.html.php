<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<form class="form-horizontal" method="post" action="{url link='current'}">
    <fieldset>
        <div class="form-group">
            <label class="col-md-4 control-label" for="checkboxes">{_p var="Information About You"}</label>
            <div class="col-md-4">
                {foreach from=$aCopyUserInfoStatus key=sKey item=aUserInfo}
                <div class="checkbox">
                    <label for="{$sKey}">
                        <input type="checkbox" name="val[information_about_you][]" id="{$sKey}" value="{$sKey}" checked>
                        {$aUserInfo.tittle}
                    </label>
                    <div class="form-text text-muted">{$aUserInfo.description}</div>
                </div>
                {/foreach}
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label" for="checkboxes">{_p var="Your Information"}</label>
            <div class="col-md-4">
                {foreach from=$aCopyUserDataStatus key=sKey item=aUserData}
                <div class="checkbox">
                    <label for="{$sKey}">
                        <input type="checkbox" name="val[your_information][]" id="{$sKey}" value="{$sKey}" checked>
                        {$aUserData.tittle}
                    </label>
                    <div class="form-text text-muted">{$aUserData.description}</div>
                </div>
                {/foreach}
            </div>
        </div>

        <div class="form-group">
            <label class="col-md-4 control-label" for="button"></label>
            <div class="col-md-4">
                <button id="button" name="button" class="btn btn-primary">{_p var='download'}</button>
            </div>
        </div>

    </fieldset>
</form>
