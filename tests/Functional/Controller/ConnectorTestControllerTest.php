<?php

namespace App\Tests\Functional\Controller;

use App\Entity\McpServer;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\McpServerType;
use App\Service\CredentialsEncryptor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConnectorTestControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $ownerUser;
    private User $otherUser;
    private McpServer $server;
    private McpServer $corruptedServer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client    = static::createClient();
        $container       = static::getContainer();
        $this->em        = $container->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($this->em);
        $metadata   = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        /** @var CredentialsEncryptor $encryptor */
        $encryptor = $container->get(CredentialsEncryptor::class);

        $this->ownerUser = (new User())->setEmail('owner@test.com')->setPassword('x');
        $this->otherUser = (new User())->setEmail('other@test.com')->setPassword('x');

        $project = (new Project())->setName('Test Project')->setUser($this->ownerUser);

        $this->server = (new McpServer())
            ->setName('Test Matomo')
            ->setType(McpServerType::Matomo)
            ->setProject($project)
            ->setEncryptedCredentials($encryptor->encrypt([
                'url'      => 'https://matomo.example.com',
                'apiToken' => 'token',
                'siteId'   => 1,
            ]));

        $this->corruptedServer = (new McpServer())
            ->setName('Corrupted')
            ->setType(McpServerType::Matomo)
            ->setProject($project)
            ->setEncryptedCredentials('not-valid-encrypted-data');

        $this->em->persist($this->ownerUser);
        $this->em->persist($this->otherUser);
        $this->em->persist($project);
        $this->em->persist($this->server);
        $this->em->persist($this->corruptedServer);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();
    }

    // ── testRaw ───────────────────────────────────────────────────────────────

    public function testRawRequiresAuthentication(): void
    {
        $this->client->request('POST', '/api/matomo/test', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseRedirects();
        $this->assertResponseStatusCodeSame(302);
    }

    public function testRawInvalidJsonReturnsBadRequest(): void
    {
        $this->client->loginUser($this->ownerUser);
        $this->client->request('POST', '/api/matomo/test', [], [], ['CONTENT_TYPE' => 'application/json'], 'not-json');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testRawUnknownTypeReturns404(): void
    {
        $this->client->loginUser($this->ownerUser);
        $this->client->request('POST', '/api/unknown_type/test', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRawMissingCredentialsReturnsBadRequest(): void
    {
        $this->client->loginUser($this->ownerUser);
        $this->client->request('POST', '/api/matomo/test', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('Missing parameters.', $data['message']);
    }

    // ── testServer ────────────────────────────────────────────────────────────

    public function testServerRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/matomo/test/' . $this->server->getId());
        $this->assertResponseStatusCodeSame(302);
    }

    public function testServerReturnsForbiddenIfNotOwner(): void
    {
        $this->client->loginUser($this->otherUser);
        $this->client->request('GET', '/api/matomo/test/' . $this->server->getId());

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testServerReturnsUnprocessableIfDecryptionFails(): void
    {
        $this->client->loginUser($this->ownerUser);
        $this->client->request('GET', '/api/matomo/test/' . $this->corruptedServer->getId());

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('Credential decryption failed.', $data['message']);
    }
}
