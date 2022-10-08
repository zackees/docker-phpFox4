<?php
$aValidation['min_character_to_search'] = [
    'def' => 'int:required',
    'min' => 1,
    'title' => _p('global_search_minimum_character_validation_messages', [
        'number' => 1,
    ])
];
$aValidation['no_pages_for_scroll_down'] = [
    'def' => 'int:required',
    'min' => 1,
    'title' => _p('no_pages_for_scroll_down_validation_messages')
];
