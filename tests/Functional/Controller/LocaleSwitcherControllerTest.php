<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocaleSwitcherControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $container    = static::getContainer();
        $this->em     = $container->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($this->em);
        $metadata   = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $this->user = (new User())->setEmail('test@example.com')->setPassword('x');
        $this->em->persist($this->user);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();
    }

    private function csrfToken(): string
    {
        $crawler = $this->client->request('GET', '/projects');

        return $crawler->filterXPath('//input[@name="_token"]')->first()->attr('value');
    }

    public function testRequiresAuthentication(): void
    {
        $this->client->request('POST', '/locale/en', ['_token' => 'whatever']);
        $this->assertResponseStatusCodeSame(302);
    }

    public function testInvalidCsrfTokenReturnsForbidden(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/locale/en', ['_token' => 'invalid-token']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnsupportedLocaleReturns404(): void
    {
        $this->client->loginUser($this->user);
        $token = $this->csrfToken();

        $this->client->request('POST', '/locale/de', ['_token' => $token]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testValidSwitchRedirectsToProjectsWithoutReferer(): void
    {
        $this->client->loginUser($this->user);
        $token = $this->csrfToken();

        $this->client->request('POST', '/locale/fr', ['_token' => $token]);
        $this->assertResponseRedirects('/projects');
    }

    public function testValidSwitchRedirectsToSameHostReferer(): void
    {
        $this->client->loginUser($this->user);
        $token = $this->csrfToken();

        $this->client->request(
            'POST',
            '/locale/en',
            ['_token' => $token],
            [],
            ['HTTP_REFERER' => 'http://localhost/projects'],
        );
        $this->assertResponseRedirects('http://localhost/projects');
    }

    public function testValidSwitchIgnoresExternalReferer(): void
    {
        $this->client->loginUser($this->user);
        $token = $this->csrfToken();

        $this->client->request(
            'POST',
            '/locale/en',
            ['_token' => $token],
            [],
            ['HTTP_REFERER' => 'https://evil.com/steal'],
        );
        $this->assertResponseRedirects('/projects');
    }
}
