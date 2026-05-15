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

class McpEndpointControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private string $accessToken;
    private string $clientSecret;

    protected function setUp(): void
    {
        parent::setUp();

        // createClient() must be called first - it boots the kernel
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->em = $container->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        /** @var CredentialsEncryptor $encryptor */
        $encryptor = $container->get(CredentialsEncryptor::class);

        $user = (new User())->setEmail('test@example.com')->setPassword('x');
        $project = (new Project())->setName('Test Project')->setUser($user);
        $server = (new McpServer())
            ->setName('Test Matomo')
            ->setType(McpServerType::Matomo)
            ->setProject($project)
            ->setEncryptedCredentials($encryptor->encrypt([
                'url' => 'https://matomo.example.com',
                'apiToken' => 'test-token',
                'siteId' => 1,
            ]));

        $this->accessToken = $server->getAccessToken();
        $this->clientSecret = $server->getClientSecret();

        $this->em->persist($user);
        $this->em->persist($project);
        $this->em->persist($server);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();
    }

    private function post(string $url, string $body, array $headers = []): void
    {
        $this->client->request('POST', $url, [], [], $headers + ['CONTENT_TYPE' => 'application/json'], $body);
    }

    public function testUnknownAccessTokenReturns401(): void
    {
        $this->post(
            '/api/mcp/unknown-token-00000000000000000000000000000000',
            '{"jsonrpc":"2.0","method":"initialize","id":1}',
            ['HTTP_AUTHORIZATION' => 'Bearer test'],
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMissingAuthorizationHeaderReturns401(): void
    {
        $this->post(
            '/api/mcp/'.$this->accessToken,
            '{"jsonrpc":"2.0","method":"initialize","id":1}',
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testWrongBearerReturns401(): void
    {
        $this->post(
            '/api/mcp/'.$this->accessToken,
            '{"jsonrpc":"2.0","method":"initialize","id":1}',
            ['HTTP_AUTHORIZATION' => 'Bearer wrong-secret'],
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMalformedJsonReturnsParseError(): void
    {
        $this->post(
            '/api/mcp/'.$this->accessToken,
            'not-valid-json',
            ['HTTP_AUTHORIZATION' => 'Bearer '.$this->clientSecret],
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(-32700, $data['error']['code']);
    }

    public function testInitializeReturnsServerInfo(): void
    {
        $this->post(
            '/api/mcp/'.$this->accessToken,
            '{"jsonrpc":"2.0","method":"initialize","id":1}',
            ['HTTP_AUTHORIZATION' => 'Bearer '.$this->clientSecret],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('2.0', $data['jsonrpc']);
        $this->assertSame('Test Matomo', $data['result']['serverInfo']['name']);
        $this->assertArrayHasKey('protocolVersion', $data['result']);
    }
}
