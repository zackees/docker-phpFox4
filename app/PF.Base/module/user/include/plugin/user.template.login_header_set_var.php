<?php
if (Phpfox::getParam('core.enable_register_with_google') && !empty(Phpfox::getParam('core.google_oauth_client_id'))) {
    Phpfox::getLib('template')->assign('bCustomLogin', true);
}
