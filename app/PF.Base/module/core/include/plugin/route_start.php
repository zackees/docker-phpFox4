<?php

\Core\Api\ApiManager::register([
    'core/temp-file' => [
        'api_service' => 'core.api',
        'maps' => [
            'post' => 'postTempFile'
        ],
    ],
]);