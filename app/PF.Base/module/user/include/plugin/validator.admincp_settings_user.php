<?php
defined('PHPFOX') or exit('NO DICE!');

$aValidation['date_of_birth_start'] = [
    'def' => 'int:required',
    'min' => '1900',
    'max' => '2017',
    'title' => '"Date of Birth (Start)" the range is accepted: 1900 -> 2017',
];
$aValidation['date_of_birth_end'] = [
    'def' => 'int:required',
    'min' => '1900',
    'max' => '2017',
    'requirements'=>[
        'min'=> '$date_of_birth_start',
    ],
    'title' => '"Date of Birth (End)" the range is accepted: 1900 -> 2017, and large than "Date of Birth (Start)"',
];
$aValidation['maximum_length_for_full_name'] = [
    'def' => 'int:required',
    'min' => '3',
    'title' => _p('setting_validation_maximum_length_for_full_name', [
        'number' => 3
    ]),
];
$aValidation['min_length_for_username'] = [
    'def' => 'int:required',
    'min' => '1',
    'title' => '"Minimum Length for Username" must be greater than 0.',
];
$aValidation['max_length_for_username'] = [
    'def' => 'int:required',
    'min' => '1',
    'requirements'=>[
        'min'=> '$min_length_for_username',
    ],
    'title' => '"Maximum Length for Username" must be greater than "Minimum Length for Username"',
];

$aValidation['check_status_updates'] = [
    'def' => 'int',
    'min' => '0',
    'title' => _p('"Spam Check Status Updates" be greater than or equal to 0'),
];
$aValidation['resend_verification_email_delay_time'] = [
    'def' => 'int',
    'min' => '0',
    'title' => _p('resend_verification_email_delay_time_must_be_more_than_or_equal_to_0'),
];

$aValidation['days_for_delete_pending_user_verification'] = [
    'def'=> 'int',
    'min' => '0',
    'title'=> _p('setting_days_for_delete_pending_user_verification_validation'),
];

$aValidation['delay_time_for_next_promotion'] = [
    'def' => 'int:required',
    'min' => '1',
    'title' => _p('setting_delay_time_for_next_promotion_validation'),
];
$aValidation['min_length_for_password'] = [
    'def' => 'int:required',
    'min' => '1',
    'title' => _p('setting_min_length_for_password_validation'),
];
$aValidation['max_length_for_password'] = [
    'def' => 'int:required',
    'min' => '1',
    'requirements'=>[
        'min'=> '$min_length_for_password',
    ],
    'title' => _p('setting_max_length_for_password_validation'),
];