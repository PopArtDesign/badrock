<?php

declare(strict_types=1);

namespace App;

class App
{
    public function run(): void
    {
        // Place your code here
    }

    public function getSoilDefaults(): array
    {
        return [
            'clean-up',
            'disable-rest-api',
            'disable-asset-versioning',
            'disable-trackbacks',
            'js-to-footer',
            'nav-walker',
            'nice-search',
            'relative-urls',
        ];
    }
}
