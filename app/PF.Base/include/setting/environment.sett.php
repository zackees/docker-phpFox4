<?php

return [
    // Salt
    'salt'                                => true,
    'core.salt'                           => 'PFE_CORE_SALT',

    // Cache
    'cache'                               => true,  // for show cache group
    'core.dir_cache'                      => 'PFE_CACHE_DIR',
    'cache.driver'                        => 'PFE_CACHE_DRIVER',
    'cache.host'                          => 'PFE_CACHE_HOST',
    'cache.port'                          => 'PFE_CACHE_PORT',
    'cache.auth_user'                     => 'PFE_CACHE_AUTH_USR',
    'cache.auth_pass'                     => 'PFE_CACHE_AUTH_PW',
    'cache.redis_database'                => 'PFE_CACHE_REDIS_DB',
    'pf_core_cache_driver'                => 'PFE_CACHE_DRIVER',

    // Mailer
    'mailer'                              => true, // for show mailer group
    'core.method'                         => 'PFE_MAIL_PROVIDER',
    'core.mail_from_name'                 => 'PFE_MAIL_FROM_NAME',
    'core.email_from_email'               => 'PFE_MAIL_FROM_EMAIL',
    'core.mailsmtphost'                   => 'PFE_MAIL_SMTP_HOST',
    'core.mail_smtp_authentication'       => 'PFE_MAIL_IS_SMTP_AUTH,boolean',
    'core.mail_smtp_username'             => 'PFE_MAIL_SMTP_USR',
    'core.mail_smtp_password'             => 'PFE_MAIL_SMTP_PW',
    'core.mail_smtp_port'                 => 'PFE_MAIL_SMTP_PORT',
    'core.mail_queue'                     => 'PFE_MAIL_QUEUE,boolean',
    'core.mail_smtp_secure'               => 'PFE_MAIL_SMTP_ENCRYPTION',
    'core.mail_signature'                 => 'PFE_MAIL_SIGNATURE',
    'core.verify_email_at_signup'         => 'PFE_IS_FORCE_EMAIL_VER,boolean',

    // Recaptcha
    'recaptcha'                           => true, // for show recaptcha group
    'captcha.recaptcha_type'              => 'PFE_RECAPT_TYPE',
    'captcha.recaptcha_public_key'        => 'PFE_RECAPT_SITE_KEY',
    'captcha.recaptcha_private_key'       => 'PFE_RECAPT_SECRET',

    // User
    'user'                                => true, // for show user group
    'user.verify_email_at_signup'         => 'PFE_IS_FORCE_EMAIL_VER,boolean',
    'core.admincp_timeout'                => 'PFE_ADMINCP_TIMEOUT,string,60',
    'core.keep_files_in_server'           => 'PFE_KEEP_FILES_IN_SERVER,boolean',

    // assets configure
    'assets'                              => true, // for show assets group
    'pf_assets_storage_id'                => 'PFE_ASSETS_STORAGE',
    'pf_assets_cdn_enable'                => 'PFE_ASSETS_CDN_ENABLED',
    'pf_assets_cdn_url'                   => 'PFE_ASSETS_CDN_URL',
    'pf_core_bundle_js_css'               => 'PFE_ASSETS_BUNDLE_JSCSS',

    // Session & cookie
    'session'                             => true,
    'core.session_prefix'                 => 'PFE_SESSION_PREFIX',
    'core.cookie_path'                    => 'PFE_COOKIE_PATH',
    'core.cookie_domain'                  => 'PFE_COOKIE_DOMAIN',
    'core.session_handling'               => 'PFE_SESSION,options',

    // Log
    'log'                                 => true,
    'core.log_handling'                   => 'PFE_LOG,multi_options',
    'core.log_dir'                        => 'PFE_LOG_DIR',
    'core.log_level'                      => 'PFE_LOG_LEVEL',

    // Message Queue
    'queue'                               => true,
    'core.message_queue_handling'         => 'PFE_QUEUE,multi_options',

    // Storage
    'storage'                             => true,
    'core.storage_handling'               => 'PFE_STORAGE,multi_options',
    'core.storage_default'                => 'PFE_STORAGE_DEFAULT',


    // Secure
    'secure'                              => true,
    'core.use_secure_image_display'       => 'PFE_USE_SECURE_IMAGE_DISPLAY,boolean',
    'core.force_https_secure_pages'       => 'PFE_FORCE_HTTPS_SECURE_PAGES,boolean',

    // License
    'license'                             => true,
    'core.license_id'                     => 'PFE_LICENSE_ID',
    'core.license_key'                    => 'PFE_LICENSE_KEY',

    // Cron Job
    'cron'                                => true,
    'pf_cron_task_token'                  => 'PFE_CRON_TASK_TOKEN',

    // Chat
    'chat'                                => true,
    'pf_im_node_server'                   => 'PFE_IM_NODE_SERVER',
    'pf_im_node_server_key'               => 'PFE_IM_NODE_SERVER_KEY',
    'pf_im_chat_server'                   => 'PFE_IM_CHAT_SERVER',
    'p_chatplus_server'                   => 'PFE_CHATPLUS_CHAT_SERVER',
    'p_chatplus_private_code'             => 'PFE_CHATPLUS_CHAT_SECRET',
    'p_chatplus_jitsi_domain_option'      => 'PFE_CHATPLUS_VIDEOBRIDGE_TYPE',
    'p_chatplus_jitsi_domain'             => 'PFE_CHATPLUS_VIDEOBRIDGE_DOMAIN',
    'p_chatplus_jitsi_enable_auth'        => 'PFE_CHATPLUS_VIDEOBRIDGE_ENABLE_AUTH',
    'p_chatplus_jitsi_application_id'     => 'PFE_CHATPLUS_VIDEOBRIDGE_APP_ID',
    'p_chatplus_jitsi_application_secret' => 'PFE_CHATPLUS_VIDEOBRIDGE_SECRET',
];