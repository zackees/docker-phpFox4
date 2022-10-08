<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<div class="feed-table-schedule">
    <div class="js_feed_compose_extra feed_compose_extra js_feed_compose_schedule dont-unbind-children" style="display: none;">
        <div class="feed-box-outer">
            <div class="feed-box">
                <div class="feed-box-inner">
                    <div class="feed-with">{_p var='at'}</div>
                    <div class="feed-schedule-input-box">
                        <div id="js_add_schedule">
                            <div><input type="hidden" id="val_schedule_confirmed" name="val[confirm_scheduled]" class="close_warning val_schedule_confirmed" value='0'></div>
                            <div><input type="hidden" id="val_schedule_time_year" name="val[schedule_time][year]" class="close_warning val_schedule_time_year"></div>
                            <div><input type="hidden" id="val_schedule_time_month" name="val[schedule_time][month]" class="close_warning val_schedule_time_month"></div>
                            <div><input type="hidden" id="val_schedule_time_day" name="val[schedule_time][day]" class="close_warning val_schedule_time_day"></div>
                            <div><input type="hidden" id="val_schedule_time_hour" name="val[schedule_time][hour]" class="close_warning val_schedule_time_hour"></div>
                            <div><input type="hidden" id="val_schedule_time_minute" name="val[schedule_time][minute]" class="close_warning val_schedule_time_minute"></div>
                        </div>
                        {select_date prefix='schedule_' id='_schedule' start_year='current_year' end_year='+1' field_separator=' / ' field_order='MDY' default_all=true add_time=true start_hour='+1'}
                    </div>
                </div>
                {if empty($bIsEdit)}
                    <span class="js_btn_clear_schedule_wrapper" style="display: none;">
                        <a class="btn btn-danger btn-sm btn_clear_schedule" id="btn_clear_schedule">{_p var='clear'}</a>
                    </span>
                {/if}
                <span class="js_btn_confirm_schedule_wrapper">
                    <a class="btn btn-success btn-sm btn_confirm_schedule" data-is_edit="{if !empty($bIsEdit)}1{/if}" id="btn_confirm_schedule">{if !empty($bIsEdit)}{_p var='change_time'}{else}{_p var='confirm'}{/if}</a>
                </span>
            </div>
            <div class="js_schedule_invalid_time text-danger pb-1" style="display: none">{_p var='you_cant_schedule_in_the_past'}</div>
        </div>
    </div>
</div>