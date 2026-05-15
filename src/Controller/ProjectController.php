<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Form\ProjectType;
use App\Repository\McpServerRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/projects')]
class ProjectController extends AbstractController
{
    private const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    #[Route('', name: 'app_project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('project/index.html.twig', [
            'projects' => $projectRepository->findByUser($this->getAuthenticatedUser()),
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'], requirements: ['id' => self::UUID])]
    public function show(Project $project, McpServerRepository $mcpServerRepository): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'servers' => $mcpServerRepository->findByProject($project),
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setUser($this->getAuthenticatedUser());
            $em->persist($project);
            $em->flush();

            $this->addFlash('success', 'flash.project.created');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'], requirements: ['id' => self::UUID])]
    public function edit(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'flash.project.updated');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('User must be authenticated.');
        }

        return $user;
    }

    #[Route('/{id}/delete', name: 'app_project_delete', methods: ['POST'], requirements: ['id' => self::UUID])]
    public function delete(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        if ($this->isCsrfTokenValid('delete-project-'.$project->getId(), $request->request->get('_token'))) {
            $em->remove($project);
            $em->flush();
            $this->addFlash('success', 'flash.project.deleted');
        }

        return $this->redirectToRoute('app_project_index');
    }
}
