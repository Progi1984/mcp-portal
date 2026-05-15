<?php

namespace App\Controller;

use App\Entity\McpServer;
use App\Entity\Project;
use App\Enum\McpServerType;
use App\Form\McpServerFormType;
use App\Repository\McpServerRepository;
use App\Service\CredentialsEncryptor;
use App\ValueObject\CastopodCredentials;
use App\ValueObject\GoogleSearchConsoleCredentials;
use App\ValueObject\MatomoCredentials;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/projects/{projectId}/mcp')]
class McpServerController extends AbstractController
{
    public function __construct(
        private readonly CredentialsEncryptor $encryptor,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'app_mcp_index', methods: ['GET'])]
    public function index(string $projectId, McpServerRepository $repo): Response
    {
        $project = $this->getOwnedProject($projectId);

        return $this->render('mcp_server/index.html.twig', [
            'project' => $project,
            'servers' => $repo->findByProject($project),
        ]);
    }

    #[Route('/new', name: 'app_mcp_new', methods: ['GET'])]
    public function selectType(string $projectId): Response
    {
        $project = $this->getOwnedProject($projectId);

        return $this->render('mcp_server/select_type.html.twig', [
            'project' => $project,
            'types'   => McpServerType::cases(),
        ]);
    }

    #[Route('/new/{type}', name: 'app_mcp_new_type', methods: ['GET', 'POST'])]
    public function new(string $projectId, string $type, Request $request): Response
    {
        $project    = $this->getOwnedProject($projectId);
        $serverType = McpServerType::from($type);

        $server = new McpServer();
        $server->setType($serverType);

        $form = $this->createForm(McpServerFormType::class, $server, ['server_type' => $serverType]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $credentials = $this->buildCredentials($serverType, $form);

            $server->setProject($project);
            $server->setEncryptedCredentials($this->encryptor->encrypt($credentials->toArray()));

            $this->em->persist($server);
            $this->em->flush();

            $this->addFlash('success', 'flash.mcp.created');

            return $this->redirectToRoute('app_project_show', ['id' => $projectId]);
        }

        return $this->render('mcp_server/new.html.twig', [
            'project'     => $project,
            'server_type' => $serverType,
            'form'        => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mcp_edit', methods: ['GET', 'POST'])]
    public function edit(string $projectId, McpServer $server, Request $request): Response
    {
        $project    = $this->getOwnedProject($projectId);
        $this->assertServerBelongsToProject($server, $project);

        $serverType = $server->getType();
        try {
            $rawCredentials = $this->encryptor->decrypt($server->getEncryptedCredentials());
        } catch (\RuntimeException) {
            $this->addFlash('danger', 'flash.mcp.decrypt_error');

            return $this->redirectToRoute('app_project_show', ['id' => $projectId]);
        }

        $form = $this->createForm(McpServerFormType::class, $server, ['server_type' => $serverType]);
        $this->prefillCredentials($serverType, $form, $rawCredentials);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newCredentials = $this->buildCredentials($serverType, $form);
            $server->setEncryptedCredentials($this->encryptor->encrypt($newCredentials->toArray()));
            $this->em->flush();

            $this->addFlash('success', 'flash.mcp.updated');

            return $this->redirectToRoute('app_project_show', ['id' => $projectId]);
        }

        return $this->render('mcp_server/edit.html.twig', [
            'project'     => $project,
            'server'      => $server,
            'server_type' => $serverType,
            'form'        => $form,
        ]);
    }

    #[Route('/{id}/regenerate-token', name: 'app_mcp_regenerate_token', methods: ['POST'])]
    public function regenerateToken(string $projectId, McpServer $server, Request $request): Response
    {
        $project = $this->getOwnedProject($projectId);
        $this->assertServerBelongsToProject($server, $project);

        if ($this->isCsrfTokenValid('regenerate-'.$server->getId(), $request->request->get('_token'))) {
            $server->regenerateToken();
            $this->em->flush();
            $this->addFlash('success', 'flash.mcp.token_regenerated');
        }

        return $this->redirectToRoute('app_project_show', ['id' => $projectId]);
    }

    #[Route('/{id}/regenerate-secret', name: 'app_mcp_regenerate_secret', methods: ['POST'])]
    public function regenerateClientSecret(string $projectId, McpServer $server, Request $request): Response
    {
        $project = $this->getOwnedProject($projectId);
        $this->assertServerBelongsToProject($server, $project);

        if ($this->isCsrfTokenValid('regenerate-secret-'.$server->getId(), $request->request->get('_token'))) {
            $server->regenerateClientSecret();
            $this->em->flush();
            $this->addFlash('success', 'flash.mcp.secret_regenerated');
        }

        return $this->redirectToRoute('app_project_show', ['id' => $projectId]);
    }

    #[Route('/{id}/delete', name: 'app_mcp_delete', methods: ['POST'])]
    public function delete(string $projectId, McpServer $server, Request $request): Response
    {
        $project = $this->getOwnedProject($projectId);
        $this->assertServerBelongsToProject($server, $project);

        if ($this->isCsrfTokenValid('delete-mcp-'.$server->getId(), $request->request->get('_token'))) {
            $this->em->remove($server);
            $this->em->flush();
            $this->addFlash('success', 'flash.mcp.deleted');
        }

        return $this->redirectToRoute('app_project_show', ['id' => $projectId]);
    }

    // -------------------------------------------------------------------------

    private function buildCredentials(McpServerType $type, FormInterface $form): CastopodCredentials|GoogleSearchConsoleCredentials|MatomoCredentials
    {
        return match ($type) {
            McpServerType::Castopod => new CastopodCredentials(
                url:         $form->get('castopodUrl')->getData(),
                username:    $form->get('castopodUsername')->getData(),
                password:    $form->get('castopodPassword')->getData(),
                op3ApiKey:   $form->get('castopodOp3ApiKey')->getData() ?: null,
                op3ShowUuid: $form->get('castopodOp3ShowUuid')->getData() ?: null,
            ),
            McpServerType::GoogleSearchConsole => new GoogleSearchConsoleCredentials(
                serviceAccountJson: $form->get('gscServiceAccountJson')->getData(),
                siteUrl:            $form->get('gscSiteUrl')->getData(),
            ),
            McpServerType::Matomo => new MatomoCredentials(
                url:      $form->get('matomoUrl')->getData(),
                apiToken: $form->get('matomoApiToken')->getData(),
                siteId:   (int) $form->get('matomoSiteId')->getData(),
            ),
        };
    }

    private function prefillCredentials(McpServerType $type, FormInterface $form, array $raw): void
    {
        match ($type) {
            McpServerType::Castopod => $this->prefillCastopodForm($form, $raw),
            McpServerType::GoogleSearchConsole => $this->prefillGscForm($form, $raw),
            McpServerType::Matomo => $this->prefillMatomoForm($form, $raw),
        };
    }

    private function prefillCastopodForm(FormInterface $form, array $raw): void
    {
        $form->get('castopodUrl')->setData($raw['url']);
        $form->get('castopodUsername')->setData($raw['username']);
        $form->get('castopodPassword')->setData($raw['password']);
        $form->get('castopodOp3ApiKey')->setData($raw['op3ApiKey'] ?? '');
        $form->get('castopodOp3ShowUuid')->setData($raw['op3ShowUuid'] ?? '');
    }

    private function prefillGscForm(FormInterface $form, array $raw): void
    {
        $form->get('gscSiteUrl')->setData($raw['siteUrl']);
        $form->get('gscServiceAccountJson')->setData($raw['serviceAccountJson']);
    }

    private function prefillMatomoForm(FormInterface $form, array $raw): void
    {
        $form->get('matomoUrl')->setData($raw['url']);
        $form->get('matomoApiToken')->setData($raw['apiToken']);
        $form->get('matomoSiteId')->setData($raw['siteId']);
    }

    private function getOwnedProject(string $projectId): Project
    {
        $project = $this->em->find(Project::class, $projectId);

        if ($project === null) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        return $project;
    }

    private function assertServerBelongsToProject(McpServer $server, Project $project): void
    {
        if ($server->getProject() !== $project) {
            throw $this->createAccessDeniedException();
        }
    }
}
