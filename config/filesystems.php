<?php

return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => getcwd(),
        ],
        'internal' => [
            'driver' => 'local',
            'root' => (Phar::running(false))
                ? Phar::running().DIRECTORY_SEPARATOR
                : dirname(app_path()),
        ],
    ],
];
