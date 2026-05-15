<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProjectControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $ownerUser;
    private User $otherUser;
    private Project $project;

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

        $this->ownerUser = (new User())->setEmail('owner@test.com')->setPassword('x');
        $this->otherUser = (new User())->setEmail('other@test.com')->setPassword('x');
        $this->project   = (new Project())->setName('Test Project')->setUser($this->ownerUser);

        $this->em->persist($this->ownerUser);
        $this->em->persist($this->otherUser);
        $this->em->persist($this->project);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function testIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', '/projects');
        $this->assertResponseStatusCodeSame(302);
    }

    public function testIndexReturnsOkForAuthenticatedUser(): void
    {
        $this->client->loginUser($this->ownerUser);
        $this->client->request('GET', '/projects');
        $this->assertResponseIsSuccessful();
    }

    public function testIndexShowsOnlyOwnerProjects(): void
    {
        $this->client->loginUser($this->ownerUser);
        $response = $this->client->request('GET', '/projects');

        $this->assertStringContainsString('Test Project', $this->client->getResponse()->getContent());
    }

    // ── show ──────────────────────────────────────────────────────────────────

    public function testShowAllowedForOwner(): void
    {
        $this->client->loginUser($this->ownerUser);
        $this->client->request('GET', '/projects/' . $this->project->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testShowForbiddenForNonOwner(): void
    {
        $this->client->loginUser($this->otherUser);
        $this->client->request('GET', '/projects/' . $this->project->getId());
        $this->assertResponseStatusCodeSame(403);
    }

    // ── new ───────────────────────────────────────────────────────────────────

    public function testNewFormIsAccessibleForAuthenticatedUser(): void
    {
        $this->client->loginUser($this->ownerUser);
        $this->client->request('GET', '/projects/new');
        $this->assertResponseIsSuccessful();
    }

    public function testCreatingProjectRedirectsToIndex(): void
    {
        $this->client->loginUser($this->ownerUser);
        $crawler = $this->client->request('GET', '/projects/new');
        $form    = $crawler->filterXPath('//form[@name="project"]')->form([
            'project[name]' => 'My New Project',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/projects');
    }

    // ── edit ─────────────────────────────────────────────────────────────────

    public function testEditAllowedForOwner(): void
    {
        $this->client->loginUser($this->ownerUser);
        $this->client->request('GET', '/projects/' . $this->project->getId() . '/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditForbiddenForNonOwner(): void
    {
        $this->client->loginUser($this->otherUser);
        $this->client->request('GET', '/projects/' . $this->project->getId() . '/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    // ── delete ────────────────────────────────────────────────────────────────

    public function testDeleteForbiddenForNonOwner(): void
    {
        $this->client->loginUser($this->otherUser);
        $this->client->request('POST', '/projects/' . $this->project->getId() . '/delete', [
            '_token' => 'any',
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteWithValidCsrfTokenRemovesProject(): void
    {
        $this->client->loginUser($this->ownerUser);

        $projectId = (string) $this->project->getId();

        // The delete form and its CSRF token are rendered on the index page
        $crawler = $this->client->request('GET', '/projects');
        $token   = $crawler
            ->filterXPath('//form[contains(@action, "/delete")]//input[@name="_token"]')
            ->first()
            ->attr('value');

        $this->client->request('POST', '/projects/' . $projectId . '/delete', ['_token' => $token]);
        $this->assertResponseRedirects('/projects');

        $this->em->clear();
        $this->assertNull($this->em->find(Project::class, $projectId));
    }

    public function testDeleteWithInvalidCsrfTokenDoesNotRemoveProject(): void
    {
        $this->client->loginUser($this->ownerUser);

        $projectId = (string) $this->project->getId();
        $this->client->request('POST', '/projects/' . $projectId . '/delete', ['_token' => 'bad-token']);
        $this->assertResponseRedirects('/projects');

        $this->em->clear();
        $this->assertNotNull($this->em->find(Project::class, $projectId));
    }
}
