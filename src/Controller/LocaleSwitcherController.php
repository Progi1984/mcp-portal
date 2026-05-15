<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class LocaleSwitcherController extends AbstractController
{
    private const SUPPORTED = ['en', 'fr'];

    #[Route('/locale/{locale}', name: 'app_locale_switch', methods: ['POST'])]
    public function switch(string $locale, Request $request, EntityManagerInterface $em): Response
    {
        // Point 2 - vérification CSRF
        if (!$this->isCsrfTokenValid('locale-switch', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!in_array($locale, self::SUPPORTED, true)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        if ($user instanceof User) {
            $user->setLocale($locale);
            $em->flush();
        }

        $request->getSession()->set('_locale', $locale);

        // Point 1 - pas d'open redirect : on valide que le referer appartient au même host
        $referer = $request->headers->get('referer', '');
        if ($referer && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_project_index');
    }
}
