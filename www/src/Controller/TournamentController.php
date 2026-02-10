<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Media;
use App\Entity\Team;
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
        $isRegistered = false;
        $allTeamsRegistred = $tournament->getTeams()->toArray();

        if ($user) {
            // On parcourt les équipes de l'utilisateur
            foreach ($user->getTeams() as $team) {
                if ($tournament->getTeams()->contains($team)) {
                    $isRegistered = true;
                    break;
                }
            }
        }


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

        // Remplace ton bloc ligne 150-160 par ceci :
        $allUserTournaments = []; // Change le nom pour éviter toute confusion
        if ($user) {
            foreach ($user->getTeams() as $team) {
                // Utilise $t ou $userTournament au lieu de $tournament
                foreach ($team->getTournaments() as $userTournament) {
                    $allUserTournaments[] = $userTournament;
                }
            }
        }

        // Supprimer les doublons
        $allUserTournaments = array_unique($allUserTournaments, SORT_REGULAR);



        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
            'hasVoted' => $hasVoted,
            'voteCount' => $tournament->getVotes()->count(),
            'commentForm' => $commentForm,
            'comments' => $comments,
            'userTournaments' => $allUserTournaments,
            'isRegistered' => $isRegistered,
            'allTeamsRegistred' => $allTeamsRegistred,
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
        //on vérifie que le créateur est l'user en session
        if ($tournament->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de supprimer ce tournoi');
            return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
        }

        //vérifie le token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_tournament_' . $tournament->getId(), $token)) {
            $this->addFlash('error', 'CSRF Token invalid');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()], Response::HTTP_SEE_OTHER);
        }

        $tournament->setIsActive(false);
        $tournament->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', "Le tournoi a été supprimé avec succès.");

        return $this->redirectToRoute('app_tournament_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/tournament/{id}/register-team', name: 'app_tournament_register_team', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function registerTeam(
        Tournament $tournament,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 1. Récupérer l'équipe de l'utilisateur (via le champ team_id du formulaire)
        $teamId = $request->request->get('team_id');
        $team = $entityManager->getRepository(Team::class)->find($teamId);

        if (!$team || $team->getOwner() !== $user) {
            $this->addFlash('danger', 'Équipe invalide ou vous n\'en êtes pas le propriétaire.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // 2. Vérifications de base (Actif / Deadline)
        if (!$tournament->isActive() || ($tournament->getDeadlineJoin() && $tournament->getDeadlineJoin() < new \DateTime())) {
            $this->addFlash('danger', 'Les inscriptions sont fermées.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // 3. LOGIQUE DE BLOCAGE : Un membre est-il déjà dans une équipe inscrite ?
        // On récupère toutes les équipes déjà inscrites au tournoi
        $registeredTeams = $tournament->getTeams();

        // On parcourt chaque membre de l'équipe que l'on veut inscrire
        foreach ($team->getUsers() as $member) {
            foreach ($registeredTeams as $registeredTeam) {
                if ($registeredTeam->getUsers()->contains($member)) {
                    // Si le membre est le owner lui-même
                    if ($member === $user) {
                        $this->addFlash('danger', 'Vous participez déjà à ce tournoi avec l\'équipe ' . $registeredTeam->getName());
                    } else {
                        $this->addFlash('danger', sprintf(
                            'L\'inscription a échoué : %s est déjà inscrit dans l\'équipe %s.',
                            $member->getPseudo(),
                            $registeredTeam->getName()
                        ));
                    }
                    return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
                }
            }
        }

        // 4. Inscription
        $tournament->addTeam($team);
        $entityManager->flush();

        $this->addFlash('success', sprintf('L\'équipe %s est officiellement inscrite !', $team->getName()));

        return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournament/{id}/unregister-team', name: 'app_tournament_unregister_team', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unregisterTeam(
        Tournament $tournament,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Vérification CSRF pour la sécurité
        if (!$this->isCsrfTokenValid('unregister'.$tournament->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token invalide.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        $team = $entityManager->getRepository(Team::class)->findOneBy(['owner' => $user]);

        if (!$team || !$tournament->getTeams()->contains($team)) {
            $this->addFlash('danger', 'Votre équipe n\'est pas inscrite à ce tournoi.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        $tournament->removeTeam($team);
        $entityManager->flush();

        $this->addFlash('success', 'Votre équipe s\'est désinscrite du tournoi.');

        return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
    }
}
