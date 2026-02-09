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
        // vérifier que le user est bine l'auteur du défie
        if ($team->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', "Vous n'avez pas l'autaurisation de supprimer ce challenge.");
            return $this->redirectToRoute('app_challenge_show', ['id' => $team->getId()]);
        }

        //verifie le token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_challenge_'.$team->getId(), $token)) {
            $this->addFlash('error', "Token Csrf Invalid.");
            return $this->redirectToRoute('app_challenge_show', ['id' => $team->getId()]);
        }

        //soft delete
        $challenge->setIsActive(false);
        $challenge->setUpdatedAt(new DateTime());

        $entityManager->flush();

        $this->addFlash('succes', "Votre défis a été supprimer avec succes.");

        return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
    }
}
 