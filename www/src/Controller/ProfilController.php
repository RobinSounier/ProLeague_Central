<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfilType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
final class ProfilController extends AbstractController
{
    #[Route('', name: 'app_profil_show', methods: ['GET'])]
    public function show(): Response
    {

        $user = $this->getUser();

        $ownerTeamsCount = array_filter($user->getTeams()->toArray(), function ($team) use ($user) {
            return $team->getOwner() === $user;
        });

        return $this->render('profil/show.html.twig', [
            'user' => $user,
            'ownerTeamsCount' => count($ownerTeamsCount),
        ]);
    }

    #[Route('/edit', name: 'app_profil_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser(); // On récupère l'utilisateur connecté

        $form = $this->createForm(ProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du fichier avatar
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile) {
                // Supprimer l'ancien avatar s'il existe
                if ($user->getAvatar()) {
                    $fileUploader->delete($user->getAvatar());
                }

                // Uploader le nouveau fichier dans le dossier "avatars"
                $avatarPath = $fileUploader->upload($avatarFile, 'avatars');
                $user->setAvatar($avatarPath);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('app_profil_show');
        }

        return $this->render('profil/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/show/{id}', name: 'app_show_user', methods: ['GET'])]
    public function showUser(User $user): Response
    {
        $ownerTeamsCount = array_filter($user->getTeams()->toArray(), function ($team) use ($user) {
            return $team->getOwner() === $user;
        });

        return $this->render('profil/showUser.html.twig', [
            'user' => $user,
            'ownerTeamsCount' => count($ownerTeamsCount),
        ]);
    }
}
