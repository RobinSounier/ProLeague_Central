<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Media;
use App\Entity\Tournament;
use App\Form\CommentType;
use App\Form\TournamentType;
use App\Repository\GameRepository;
use App\Repository\TournamentRepository;
use App\Repository\VoteRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tournament')]
final class TournamentController extends AbstractController
{
    #[Route(name: 'app_tournament_index', methods: ['GET'])]
    public function index(TournamentRepository $tournamentRepository, GameRepository $gameRepository, Request $request): Response
    {
        // Récupération des paramètres de filtre et tri
        $gamesId = $request->query->getInt('game', 0);
        $sortBy = $request->query->get('sort', 'recent');

        // Récupération de tous les jeux
        $games = $gameRepository->findAll();

        // Récupération des tournois avec possibilité de filtrer par jeux
        $tournaments = $tournamentRepository->findAllWithFilters($gamesId, $sortBy);

        return $this->render('tournament/index.html.twig', [
            'tournaments' => $tournaments,
            'games' => $games,
            'selectedGame' => $gamesId,
            'selectedSortBy' => $sortBy,
        ]);
    }

    #[Route('/new', name: 'app_tournament_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $user = $this->getUser();
        $tournament = new Tournament();
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $tournament->setCreatedAt(new \DateTime());
            $tournament->setIsActive(true);
            $tournament->setOwner($user);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder le tournoi d'abord pour avoir son ID
            $entityManager->persist($tournament);
            $entityManager->flush();

            // Upload des fichiers
            $files = $form->get('files')->getData();
            if ($files) {
                foreach ($files as $file) {
                    try {
                        // Upload du fichier
                        $fileName = $fileUploader->upload($file, 'tournaments');

                        // On enregistre en BDD les médias
                        $media = new Media();
                        $media->setPath($fileName);
                        $entityManager->persist($media);

                        // Associer le média au tournoi
                        $tournament->addMedia($media);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload d\'un fichier : ' . $e->getMessage());
                    }
                }

                $entityManager->persist($tournament);
                $entityManager->flush();
            }

            $this->addFlash('success', "Votre tournoi a été créé avec succès");
            return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournament/new.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tournament_show', methods: ['GET'])]
    public function show(int $id, TournamentRepository $tournamentRepository, VoteRepository $voteRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $tournament = $tournamentRepository->find($id);
        $user = $this->getUser();

        if (!$tournament) {
            $this->addFlash('error', "Ce tournoi n'existe pas.");
            return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete_tournament_'.$tournament->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tournament);
            $entityManager->flush();
            $this->addFlash('success', 'Le tournoi a été supprimé avec succès.');
        }

        // Vérifier si le tournoi est actif
        if (!$tournament->isActive()) {
            $this->addFlash('error', "Ce tournoi n'est plus disponible.");
            return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
        }

        // Vérifier si l'utilisateur a déjà voté
        $hasVoted = false;
        if ($this->getUser()) {
            $hasVoted = $voteRepository->findOneBy([
                    'author' => $this->getUser(),  // Changé de 'user' à 'author'
                    'tournament' => $tournament
                ]) !== null;
        }

        // Formulaire de commentaire principal
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);

        // Récupérer les commentaires principaux (sans parent)
        $comments = $tournament->getComments()->filter(function (Comment $comment) {
            return $comment->getParentComment() === null;
        })->toArray();

        // Trier les commentaires par date (plus récent en premier)
        usort($comments, function (Comment $a, Comment $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        $allTournaments = [];
        if ($user) {
            foreach ($user->getTeams() as $team) {
                // getTournaments() est bien défini dans ton entité Team
                foreach ($team->getTournaments() as $tournament) {
                    $allTournaments[] = $tournament;
                }
            }
        }


// Optionnel : supprimer les doublons si une équipe est inscrite plusieurs fois
// ou si l'utilisateur est dans deux teams du même tournoi
        $allTournaments = array_unique($allTournaments, SORT_REGULAR);



        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
            'hasVoted' => $hasVoted,
            'voteCount' => $tournament->getVotes()->count(),
            'commentForm' => $commentForm,
            'comments' => $comments,
            'userTournaments' => $allTournaments,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tournament_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournament/edit.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tournament_delete', methods: ['POST'])]
    public function delete(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tournament->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tournament);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
    }
}
