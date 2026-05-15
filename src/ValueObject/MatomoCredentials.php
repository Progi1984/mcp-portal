<?php

namespace App\ValueObject;

class MatomoCredentials
{
    public function __construct(
        public readonly string $url,
        public readonly string $apiToken,
        public readonly int $siteId,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'] ?? '',
            apiToken: $data['apiToken'] ?? '',
            siteId: $data['siteId'] ?? 1,
        );
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'apiToken' => $this->apiToken,
            'siteId' => $this->siteId,
        ];
    }
}
