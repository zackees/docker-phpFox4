<?php

$aValidation['feed_display_limit'] = [
    'def' => 'int:required',
    'min' => '1',
    'title' => _p('validate_feed_display_limit'),
];
$aValidation['refresh_activity_feed'] = [
    'def' => 'int',
    'min' => '0',
    'title' => _p('validate_refresh_activity_feed'),
];
$aValidation['feed_limit_days'] = [
    'def' => 'int',
    'min' => '0',
    'title' => _p('validate_feed_limit_days'),
];
$aValidation['feed_sponsor_cache_time'] = [
    'def' => 'int',
    'min' => '0',
    'title' => _p('validate_feed_sponsor_cache_time'),
];