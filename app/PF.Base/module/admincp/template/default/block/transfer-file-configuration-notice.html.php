<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<label>{_p var='configuration'}</label>
<div class="help-block">
    <div>{_p var='you_need_to_check_some_below_steps_before_starting_transferring_files'}</div>
    <div style="padding-left: 8px;">{_p var='go_to_message_queue_system_to_choose_the_service_you_want_to_use' link=$messageQueueLink}</div>
    <div style="padding-left: 8px;">{_p var='make_sure_that_you_have_configured_cron_for_working_or_go_to_link_to_get_cron_running' link=$cronJobLink}</div>
</div>
