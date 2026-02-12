<?php

namespace App\Controller\Admin;

use App\Entity\Team;
use App\Entity\User;
use App\Form\TeamType;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/team')]
#[IsGranted('ROLE_ADMIN')]
final class TeamController extends AbstractController
{
    /**
     * @param TeamRepository $teamRepository
     * @return Response
     */
    #[Route('', name: 'app_admin_team_index', methods: ['GET'])]
    public function index(TeamRepository $teamRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $teams = $teamRepository->findAll();

        $pagination = $paginator->paginate(
            $teams,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('admin/team/index.html.twig', [
            'pagination' => $pagination,
            'teams' => $teams, // Gardé pour les statistiques
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/new', name: 'app_admin_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team->setCreatedAt(new \DateTime());
            $team->setIsActive(true);
            $entityManager->persist($team);
            $entityManager->flush();

            $this->addFlash('success', 'Équipe créée avec succès.');
            return $this->redirectToRoute('app_admin_team_index');
        }

        return $this->render('admin/team/new.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    /**
     * @param Team $team
     * @param User $user
     * @return Response
     */
    #[Route('/{id}', name: 'app_admin_team_show', methods: ['GET'])]
    public function show(Team $team, User $user): Response
    {
        return $this->render('admin/team/show.html.twig', [
            'team' => $team,
            'user' => $user,
        ]);
    }

    /**
     * @param Request $request
     * @param Team $team
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_admin_team_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Équipe modifiée avec succès.');
            return $this->redirectToRoute('app_admin_team_index');
        }

        return $this->render('admin/team/edit.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param Team $team
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/toggle', name: 'app_admin_team_toggle', methods: ['POST'])]
    public function toggle(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_team_' . $team->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_team_index');
        }

        $team->setIsActive(!$team->isActive());
        $team->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        $status = $team->isActive() ? 'activée' : 'désactivée';
        $this->addFlash('success', "L'équipe a été {$status} avec succès.");

        return $this->redirectToRoute('app_admin_team_index');
    }

    /**
     * @param Request $request
     * @param Team $team
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'app_admin_team_delete', methods: ['POST'])]
    public function delete(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_team_' . $team->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_team_index');
        }

        // Soft delete
        $team->setIsActive(false);
        $team->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        $this->addFlash('success', 'Équipe supprimée avec succès.');

        return $this->redirectToRoute('app_admin_team_index');
    }
}
