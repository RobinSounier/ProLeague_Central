<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_admin_user')]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        // on recupère les parametre de recherche ou de tri depuis l'url
        $search = $request->query->get('search', '');
        $filter = $request->query->get('filter', 'all'); // all, active, inactive, admins

        //on recupère tous les utilisateur
        $users = $userRepository->findAll();

        // Filtre de tri
        if ($filter === 'active') {
            $users = array_filter($users, fn($u) => $u->isActive());
        } elseif ($filter === 'inactive') {
            $users = array_filter($users, fn($u) => !$u->isActive());
        } elseif ($filter === 'admins') {
            $users = array_filter($users, fn($u) => in_array('ROLE_ADMIN', $u->getRoles()));
        }

        //Recherche
        if ($search) {
            $users = array_filter($users, function ($user) use ($search) {
                return stripos($user->getPseudo(), $search) !== false
                    || stripos($user->getEmail(), $search) !== false;
            });
        }

        //reindexer le tableau après filtrage
        $users = array_values($users);

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'filter' => $filter
        ]);
    }

    #[Route('/user/{id}', name: 'app_admin_user_show')]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * activer/désactiver un utilisateur
     * @param User $user
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/user/{id}/toggle-active', name: 'app_admin_user_toggle_active', methods: ['POST'])]
    public function userToggleActive(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Verifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_user_' . $user->getId(), $token)) {
            $this->addFlash('error', "Token CSRF invalide");
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        // Ne pas permettre de desactiver son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', "Vous ne pouvez pas désactiver votre propre compte");
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            "L'utilisateur %s a été %s avec succès",
            $user->getPseudo(),
            $user->isActive() ? "activé" : "désactivé"
        ));
        return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
    }

    /**
     * Supprimer un utilisateur
     * @param User $user
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function userDelete(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Verifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_user_' . $user->getId(), $token)) {
            $this->addFlash('error', "Token CSRF invalide");
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        // Ne pas permettre de supprimer son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', "Vous ne pouvez pas supprimer votre propre compte");
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        $user->setIsActive(false);
        $user->setUpdatedAt(new DateTime());
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', "L'utilisateur a été supprimé avec succès");
        return $this->redirectToRoute('app_admin_user');
    }
}
