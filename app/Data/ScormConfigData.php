<?php

namespace App\Data;

use App\Contracts\ScormConfig;
use App\Enums\ScormVersions;
use Spatie\LaravelData\Data;

class ScormConfigData extends Data implements ScormConfig
{
    public function __construct(
        public ScormVersions $version,
        public string $title,
        public string $identifier,
        public string $source,
        public string $destination,
        public int $masteryScore,
        public string $startingPage,
        public string $organization,
        public string $packageName,
        public ?string $metadataDescription,
    ) {}
}
