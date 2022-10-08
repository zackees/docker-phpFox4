<?php
defined('PHPFOX') or exit('NO DICE!');
?>
{if !empty($aMetaDataList)}
    <div class="form-group">
        <label>{_p var='default_metadata'}</label>
        <div class="help-block">{_p var='this_section_requires_php_version_to_be_equal_to_or_greater_than_version_if_your_server_does_not_meet_the_condition_files_will_not_have_the_metadata_that_you_configured' version='7.1'}</div>
        <div style="padding-left: 16px;">
            {foreach from=$aMetaDataList item=aMetadataItem}
                <div class="form-group">
                    <label for="metadata_{$aMetadataItem.name}">{$aMetadataItem.title}</label>
                    <input id="metadata_{$aMetadataItem.name}" class="form-control" type="text" name="val[metadata][{$aMetadataItem.name}]" value="{if isset($aMetadataItem.value)}{$aMetadataItem.value}{/if}">
                </div>
            {/foreach}
        </div>
    </div>
{/if}