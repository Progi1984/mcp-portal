<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

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
    }

    protected function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();
    }

    public function testRegisterPageLoads(): void
    {
        $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
    }


    public function testAuthenticatedUserIsRedirectedToProjects(): void
    {
        $user = (new User())->setEmail('existing@test.com')->setPassword('x');
        $this->em->persist($user);
        $this->em->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/register');
        $this->assertResponseRedirects('/projects');
    }

    public function testValidRegistrationCreatesUserAndRedirectsToLogin(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form    = $crawler->filterXPath('//form')->first()->form([
            'registration_form[email]'                 => 'new@example.com',
            'registration_form[plainPassword][first]'  => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/login');

        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);
        $this->assertNotNull($repo->findOneBy(['email' => 'new@example.com']));
    }

    public function testPasswordMismatchKeepsFormWithoutCreatingUser(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form    = $crawler->filterXPath('//form')->first()->form([
            'registration_form[email]'                 => 'new@example.com',
            'registration_form[plainPassword][first]'  => 'password123',
            'registration_form[plainPassword][second]' => 'doesnotmatch',
        ]);

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(422);

        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);
        $this->assertNull($repo->findOneBy(['email' => 'new@example.com']));
    }

    public function testPasswordTooShortKeepsFormWithoutCreatingUser(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form    = $crawler->filterXPath('//form')->first()->form([
            'registration_form[email]'                 => 'short@example.com',
            'registration_form[plainPassword][first]'  => 'short',
            'registration_form[plainPassword][second]' => 'short',
        ]);

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(422);

        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);
        $this->assertNull($repo->findOneBy(['email' => 'short@example.com']));
    }
}
