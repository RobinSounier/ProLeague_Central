<?php

namespace App\Controller;

use App\Entity\Team;
use App\Form\TeamType;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/team')]
final class TeamController extends AbstractController
{
    #[Route(name: 'app_team_index', methods: ['GET'])]
    public function index(TeamRepository $teamRepository, GameRepository $gameRepository, Request $request): Response
    {

        // recupération des paramètrre de tri et de filtre soumis par l'utilisateur
        $gamesId = $request->query->getInt('game', 0);

        //récuperation de toute les catégories pour pouvoir les afficher
        $games = $gameRepository->findAll();

        //récuperation des challenge avec possiblité de filtre
        $teams = $teamRepository->findAllWithFilters($gamesId);


        return $this->render('team/index.html.twig', [
            'teams' => $teams,
            'selectGames' => $gamesId,
            'games' => $games,
        ]);
    }

    #[Route('/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($team);
            $entityManager->flush();

            return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team/new.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_team_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team/edit.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_team_delete', methods: ['POST'])]
    public function delete(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        //on vérifie que le owner est l'user en session
        if ($team->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous avez pas l\'autorisation de supprimer cette equipe');
            return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
        }

        //vérifie le token
        $token = $request->query->get('_token');
        if ($this->isCsrfTokenValid('delete_team_'.$team->getId(), $token)) {
            $this->addFlash('error', 'CSRF Token invalid');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()], Response::HTTP_SEE_OTHER);
        }

        $team->setIsActive(false);
        $team->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', "Votre équipe a été supprimer avec succes.");

        return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
    }
}
