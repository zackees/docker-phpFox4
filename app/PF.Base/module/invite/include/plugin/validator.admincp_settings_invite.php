<?php

$aValidation = [
    'pendings_to_show_per_page'=>[
        'def' => 'int:required',
        'min' => '1',
        'title' => _p('"How Many Pendings To Show" must be greater than 0'),
    ],
    'invite_expire'=>[
        'def' => 'int:required',
        'min' => '0',
        'title' => _p('"Expire invites timeout" must be greater than or equal to 0'),
    ],
];