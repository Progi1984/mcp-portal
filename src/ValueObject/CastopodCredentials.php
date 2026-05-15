<?php

namespace App\ValueObject;

class CastopodCredentials
{
    public function __construct(
        public readonly string $url,
        public readonly string $username,
        public readonly string $password,
        public readonly ?string $op3ApiKey   = null,
        public readonly ?string $op3ShowUuid = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            url:         $data['url'],
            username:    $data['username'],
            password:    $data['password'],
            op3ApiKey:   $data['op3ApiKey']   ?? null,
            op3ShowUuid: $data['op3ShowUuid'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'url'         => $this->url,
            'username'    => $this->username,
            'password'    => $this->password,
            'op3ApiKey'   => $this->op3ApiKey,
            'op3ShowUuid' => $this->op3ShowUuid,
        ];
    }

    public function hasOp3(): bool
    {
        return $this->op3ApiKey !== null && $this->op3ShowUuid !== null;
    }
}
