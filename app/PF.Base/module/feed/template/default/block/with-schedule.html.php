<?php
defined('PHPFOX') or exit('NO DICE!');
?>
<a href="#" type="button" id="btn_display_with_schedule" class="activity_feed_share_this_one_btn js_btn_display_with_schedule parent js_hover_title dont-unbind-children" onclick="return false;">
    <span class="ico ico-clock-o"></span>
    <span class="js_hover_info">
        {_p var='schedule'}
    </span>
</a>
<script type="text/javascript">
    $Behavior.prepareScheduleInit = function() {l}
        $Core.FeedSchedule.init();
    {r}
</script>
