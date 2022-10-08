<?php 
/**
 * [PHPFOX_HEADER]
 *
 */
 
defined('PHPFOX') or exit('NO DICE!');
?>
<input type="{if $bIsFaText}text{else}hidden{/if}" class="form-control {$sIconInputClass}" value="{$sValue}" name="val[{$sIconInputName}]" id="{$sIconInputId}"/>
<div class="icon-font-picker-preview" id="js_icon_font_picker_preview" {if $bIsFaText}style="display:none"{/if}>
    <input class="form-control" type="text" name="" id="js_icon_font_search" placeholder="Search icons..."/>
    <div id="js_icon_font_selected" class="icon-font-selected-wrapper {if empty($sValue)}hide{/if}">
        {if !empty($sValue)}
            <span class="icon-style-selected {if $bIsFontAws}fa fa-{$sValue}{else}ico {$sValue}{/if}"></span>
            <span class="js_icon_selected_remove ico ico-close" title="{_p var='remove_icon'}" onclick="clearSelectedIcon();"></span>
        {/if}
    </div>
</div>
<div class="icon-font-picker-container" id="js_icon_font_picker_container" {if $bIsFaText}style="display:none"{/if}>
    {foreach from=$aIcons item=sIcon}
        <span data-icon="{$sIcon}" class="icon-font-item js_icon_font_item ico {$sIcon} {if !empty($sValue) && $sValue == $sIcon}active{/if}"></span>
    {/foreach}
</div>

{literal}
<script>
    var iconString = {/literal}{$sIconString}{literal};
    var smallDelay, isInit = false;
    function clearSelectedIcon() {
        $('#js_icon_font_selected').html('').addClass('hide');
        $('#{/literal}{$sIconInputId}{literal}').val('').trigger('change');
        $('#js_icon_font_picker_container > .js_icon_font_item').removeClass('active');
    }
    $Behavior.searchIcons = function() {
        $('#js_icon_font_picker_container > .js_icon_font_item').on('click', function () {
            var value = $(this).data('icon');
            $('#js_icon_font_picker_container > .js_icon_font_item').removeClass('active');
            $(this).addClass('active');
            $('#js_icon_font_selected').html('<span class="icon-style-selected ico ' + value + '"></span>' +
                '<span class="js_icon_selected_remove ico ico-close" title="{/literal}{_p var='remove_icon'}{literal}"></span>').removeClass('hide');
            $('.js_icon_selected_remove').on('click', clearSelectedIcon);
            $('#{/literal}{$sIconInputId}{literal}').val(value).trigger('change');
        });
        $('#js_icon_font_search').off('keyup').on('keyup', function () {
            var value = $(this).val(), eleHolder = $('#js_icon_font_picker_container');
            if (value.length < 1) {
                eleHolder.find('.js_icon_font_item').removeClass('hide');
                return false;
            }
            var result = iconString.filter(function (setting) {
                return setting && setting.toLowerCase().search(value.toLowerCase()) !== -1;
            });
            eleHolder.find('.js_icon_font_item').addClass('hide');
            eleHolder.find('.js_no_icon').remove();
            if (result.length) {
                for (var i = 0; i < result.length; ++i) {
                    var iconName = result[i];
                    eleHolder.find('span[data-icon="' + iconName + '"]').removeClass('hide');
                }
            } else {
                eleHolder.append('<div class="h5 t_center js_no_icon">' + oTranslations['no_results'] + '</div>');
            }
        })
        {/literal}{if $bIsFontAws}{literal}
            if (!isInit) {
                isInit = true;
                $('#js_font_aws_helper').hide();
                $('#js_font_lineficon_helper').show();
                $('#menu_font_helper').show();
            }
        {/literal}{/if}{literal}
    }
</script>
{/literal}