<?php
defined('PHPFOX') or exit('NO DICE!');

$aValidation['total_times_can_change_user_name'] = [
    'def' => 'int',
    'min' => '0',
    'title' => '"How many times can this user group edit their user name" must be greater than or equal to 0',
];
$aValidation['total_times_can_change_own_full_name'] = [
    'def' => 'int',
    'min' => '0',
    'title' => '"How many times can members of this user group change their full name?" must be greater than or equal to 0',
];
$aValidation['points_user_signup'] = [
    'def' => 'int',
    'min' => '0',
    'title' => '"Get activity points when user sign up" must be greater than or equal to 0',
];
$aValidation['points_user_accesssite'] = [
    'def' => 'int',
    'min' => '0',
    'title' => '"Get activity points when user access to site" must be greater than or equal to 0',
];
$aValidation['points_user_uploadprofilephoto'] = [
    'def' => 'int',
    'min' => '0',
    'title' => '"Get activity points when user uploaded profile photos" must be greater than or equal to 0',
];
$aValidation['points_user_uploadcoverphoto'] = [
    'def' => 'int',
    'min' => '0',
    'title' => '"Get activity points when user uploaded cover photos" must be greater than or equal to 0',
];
$aValidation['total_upload_space'] = [
    'def' => 'int',
    'min' => '0',
    'title' => _p('total_amount_space_user_uploading_must_greater_or_equal_to_zero'),
];