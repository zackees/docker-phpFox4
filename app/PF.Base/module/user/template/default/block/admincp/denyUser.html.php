<?php 
defined('PHPFOX') or exit('NO DICE!');
?>
<div id="js_admincp_deny_user">
    <div id="sFeedbackDeny" class="alert alert-success" style="display: none"></div>
    <div id="sFeedbackErrorDeny" class="alert alert-danger" style="display: none"></div>
    <div style="margin-bottom: 8px;">
        {_p var='you_are_about_to_deny_user', link=$aUser.link user_name=$aUser.full_name}
    </div>
    <div class="table form-group">
        <label for="denySubject">{_p var='subject'}:</label>
        <input type="text" size="30" id="denySubject" class="form-control" name="denySubject" value="{left_curly}phrase var='user.deny_mail_subject'{right_curly}">
        <div class="clear"></div>
    </div>
    <div class="table form-group">
        <label for="denyMessage">{_p var='message'}:</label>
        <textarea name="denyMessage"  class="form-control" id="denyMessage" cols="30" rows="3">{left_curly}phrase var='user.deny_mail_message'{right_curly}</textarea>
        <div class="clear"></div>
    </div>
    <div class="table_clear">
        <div id="js_deny_user_action">
            <a href="#" class="btn btn-primary" onclick="return processDenyUser({$aUser.user_id}, 0);">
                {_p var='deny_and_send_email'}
            </a>
            <a href="#" class="btn btn-danger" onclick="return processDenyUser({$aUser.user_id}, 1);">
                {_p var='deny_without_email'}
            </a>
            <input type="button" onclick="tb_remove();" value="{_p var='cancel'}" class="btn btn-default">
        </div>
        <div class="t_center" id="js_deny_user_loading" style="display: none">
            <i class="fa fa-spinner fa-spin" style="font-size:200%"></i>
        </div>
    </div>
</div>

{literal}
<script type="text/javascript">
    function processDenyUser(userId, doReturn) {
        $('#js_deny_user_action').hide();
        $('#js_deny_user_loading').show();
        $.ajaxCall('user.denyUser', $.param({
            sMessage: $('#denyMessage').val(),
            sSubject: $('#denySubject').val(),
            iUser: userId,
            doReturn: doReturn
        }), 'POST');
        return false;
    }
</script>
{/literal}