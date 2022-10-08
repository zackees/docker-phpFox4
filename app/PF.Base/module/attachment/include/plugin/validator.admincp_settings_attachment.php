<?php

$aValidation['attachment_item_limit'] = [
    'def' => 'int:required',
    'min' => '1',
    'title' => _p('item_limit_must_be_greater_than_or_equal_to_number', ['number' => 1]),
];