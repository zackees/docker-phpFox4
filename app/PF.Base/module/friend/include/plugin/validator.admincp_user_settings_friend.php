<?php

$aValidation = [
    'total_folders' => [
        'def' => 'int:required',
        'min' => 0,
        'title' => _p('allowed_total_friend_folders_must_be_greater_than_or_equals_to_0'),
    ],
    'points_friend_addnewfriend' => [
        'def' => 'int:required',
        'min' => 0,
        'title' => _p('user_setting_validation_points_friend_addnewfriend'),
    ]
];
