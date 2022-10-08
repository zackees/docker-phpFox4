<?php
if (Phpfox::getParam('feed.enable_hide_feed', 1)) {
    echo
        '<li role="presentation">
            <a href="javascript:void(0)" onclick="tb_show(\'' . _p('manage_hidden') . '\', $.ajaxBox(\'feed.manageHidden\', \'\'));">
                <i class="ico ico-eye-alt-blocked" aria-hidden="true"></i>' . _p('manage_hidden') . '
            </a>
        </li>';
}

