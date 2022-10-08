<?php
if (Phpfox::getParam('core.enable_register_with_google') && !empty(Phpfox::getParam('core.google_oauth_client_id'))) {
    echo Phpfox_Template::instance()->assign([
        'sPhrase' => 'sign_in_with_google',
        'sId' => 'js_google_signin_' . PHPFOX_TIME
    ])->getTemplate('user.block.google-login-button');
}
