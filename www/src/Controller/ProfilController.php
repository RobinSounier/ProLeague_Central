<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
final class ProfilController extends AbstractController
{
    #[Route('', name: 'app_profil_show', methods: ['GET'])]
    public function show(): Response
    {
        $user = $this->getUser();

        return $this->render('profil/show.html.twig', [
            'user' => $user,
        ]);
    }
}
