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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/team')]
class TeamController extends AbstractController
{
    
    #[Route('/', name: 'app_team_index', methods: ['GET'])]
    public function index(TeamRepository $teamRepository): Response
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
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team->setOwner($this->getUser());
            
            

            $entityManager->persist($team);
            $entityManager->flush();

            $this->addFlash('success', 'Votre équipe a été créée avec succès !');

            // Redirection vers la liste des équipes après la création
            return $this->redirectToRoute('app_team_index'); 
        }

        return $this->render('team/new.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }
}