<?php

namespace App\Entity;

use App\Enum\McpServerType;
use App\Repository\McpServerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: McpServerRepository::class)]
#[ORM\Index(columns: ['created_at'])]
class McpServer
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 50, enumType: McpServerType::class)]
    private McpServerType $type = McpServerType::Matomo;

    #[ORM\Column(length: 64, unique: true)]
    private string $accessToken;

    #[ORM\Column(length: 64, unique: true)]
    private string $clientSecret;

    #[ORM\Column(type: 'text')]
    private ?string $encryptedCredentials = null;

    #[ORM\ManyToOne(inversedBy: 'mcpServers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->accessToken = bin2hex(random_bytes(32));
        $this->clientSecret = bin2hex(random_bytes(32));
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getType(): McpServerType { return $this->type; }
    public function setType(McpServerType $type): static { $this->type = $type; return $this; }

    public function getAccessToken(): string { return $this->accessToken; }

    public function regenerateToken(): static
    {
        $this->accessToken = bin2hex(random_bytes(32));
        return $this;
    }

    public function getClientSecret(): string { return $this->clientSecret; }

    public function regenerateClientSecret(): static
    {
        $this->clientSecret = bin2hex(random_bytes(32));
        return $this;
    }

    public function getEncryptedCredentials(): ?string { return $this->encryptedCredentials; }
    public function setEncryptedCredentials(string $encryptedCredentials): static
    {
        $this->encryptedCredentials = $encryptedCredentials;
        return $this;
    }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
