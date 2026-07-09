<?php

namespace App\Data;

use App\Contracts\ScormConfig;
use App\Enums\ScormVersions;
use IBroStudio\DataRepository\ValueObjects\SemanticVersion;
use Spatie\LaravelData\Data;

class ScormConfigWithMetadataData extends Data implements ScormConfig
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
        public SemanticVersion $packageVersion,
        public string $packageName,
        public string $entryIdentifier,
        public string $catalogValue,
        public string $lifeCycleVersion,
        public string $classification,
        public ?string $metadataDescription,
    ) {}
}
