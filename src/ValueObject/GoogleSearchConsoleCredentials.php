<?php

namespace App\ValueObject;

class GoogleSearchConsoleCredentials
{
    public function __construct(
        public readonly string $serviceAccountJson,
        public readonly string $siteUrl,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            serviceAccountJson: $data['serviceAccountJson'],
            siteUrl: $data['siteUrl'],
        );
    }

    public function toArray(): array
    {
        return [
            'serviceAccountJson' => $this->serviceAccountJson,
            'siteUrl' => $this->siteUrl,
        ];
    }
}
