<?php
if (!Phpfox::getUserParam('user.can_browse_users_in_public')) {
    foreach ($aMenus as $index => $aMenu) {
        if ($aMenu['m_connection'] == 'main' && $aMenu['module'] == 'user') {
            unset($aMenus[$index]);
        }
    }
}
