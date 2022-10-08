<?php
$aLiteApps = [
    'Core_Announcement' => [
        'name' => 'Announcement',
        'dir' => 'core-announcement',
    ],
    'Core_Newsletter' => [
        'name' => 'Newsletter',
        'dir' => 'core-newsletter',
    ],
    'Core_Poke' => [
        'name' => 'Poke',
        'dir' => 'core-poke',
    ],
    'Core_Pages' => [
        'name' => 'Pages',
        'dir' => 'core-pages',
    ],
    'PHPfox_Twemoji_Awesome' => [
        'name' => 'Emoji',
        'dir' => 'core-twemoji-awesome',
    ],
    'Core_Music' => [
        'name' => 'Music',
        'dir' => 'core-music',
    ],
    'PHPfox_Facebook' => [
        'name' => 'Facebook Connect',
        'dir' => 'core-facebook',
    ],
    'PHPfox_CDN_Service' => [
        'name' => 'CDN Service',
        'dir' => 'core-cdn-service',
    ],
    'PHPfox_CDN' => [
        'name' => 'phpFox CDN',
        'dir' => 'core-cdn',
    ],
    'PHPfox_AmazonS3' => [
        'name' => 'Amazon CDN',
        'dir' => 'core-amazon-s3',
    ],
    'Core_Captcha' => [
        'name' => 'Captcha',
        'dir' => 'core-captcha',
    ],
    'Core_Events' => [
        'name' => 'Events',
        'dir' => 'core-events',
    ],
    'PHPfox_Groups' => [
        'name' => 'Groups',
        'dir' => 'core-groups',
    ],
    'Core_eGifts' => [
        'name' => 'Egift',
        'dir' => 'core-egift',
    ],
    'Core_RSS' => [
        'name' => 'RSS Feed',
        'dir' => 'core-rss',
    ],
    'Core_Messages' => [
        'name' => 'Messages',
        'dir' => 'core-messages',
    ],
    'Core_Activity_Points' => [
        'name' => 'Activity Points',
        'dir' => 'core-activity-points',
    ],
];

$aBasicApps = [
    'Core_Blogs' => [
        'name' => 'Blogs',
        'dir' => 'core-blogs',
    ],
    'Core_Quizzes' => [
        'name' => 'Quizzes',
        'dir' => 'core-quizzes',
    ],
    'Core_Polls' => [
        'name' => 'Polls',
        'dir' => 'core-polls',
    ],
    'Core_Forums' => [
        'name' => 'Forum',
        'dir' => 'core-forums',
    ],
    'phpFox_CKEditor' => [
        'name' => 'CKEditor',
        'dir' => 'core-CKEditor',
    ],
];

$aProApps = [
    'PHPfox_Videos' => [
        'name' => 'Videos',
        'dir' => 'core-videos',
    ],
    'PHPfox_IM' => [
        'name' => 'Instant Messaging',
        'dir' => 'core-im',
    ],
    'Core_Subscriptions' => [
        'name' => 'Subscriptions',
        'dir' => 'core-subscriptions',
    ],
    'Core_BetterAds' => [
        'name' => 'Ad',
        'dir' => 'core-better-ads',
    ],
    'Core_Marketplace' => [
        'name' => 'Marketplace',
        'dir' => 'core-marketplace',
    ],
    'phpFox_Shoutbox' => [
        'name' => 'Shoutbox',
        'dir' => 'core-shoutbox',
    ],
    'phpFox_RESTful_API' => [
        'name' => 'RESTful API',
        'dir' => 'core-restful-api',
    ],
    'P_SavedItems' => [
        'name' => 'Saved Items',
        'dir' => 'p-saved-items',
    ],
    'P_Reaction' => [
        'name' => 'Reaction',
        'dir' => 'p-reaction',
    ],
    'P_StatusBg' => [
        'name' => 'Feed Status Background',
        'dir' => 'p-status-background',
    ],
];

$aUltApps = [
    'Core_MobileApi' => [
        'name' => 'Mobile API',
        'dir' => 'core-mobile-api',
    ],
];

$iPackageId = defined('PHPFOX_PACKAGE_ID') ? PHPFOX_PACKAGE_ID : 3;
if ($iPackageId == 1) {
    return $aLiteApps;
} elseif ($iPackageId == 2) {
    return array_merge($aLiteApps, $aBasicApps);
} elseif ($iPackageId == 3) {
    return array_merge($aLiteApps, $aBasicApps, $aProApps);
} else {
    return array_merge($aLiteApps, $aBasicApps, $aProApps, $aUltApps);
}