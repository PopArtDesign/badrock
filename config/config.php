<?php

use Monolog\Handler\StreamHandler;

return [
    'soil' => [
        'clean-up',
        'disable-asset-versioning',
        'disable-trackbacks',
        'js-to-footer',
        'nav-walker',
        'nice-search',
        'relative-urls',
    ],
    'wonolog' => [
        'handler' => new StreamHandler(
            \sprintf('%s/var/log/%s.log', $this->getRootDir(), $this->getEnv()),
        )
    ],
];
