<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\User;
use App\Form\TeamType;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/team')]
final class TeamController extends AbstractController
{
    /**
     * Méthode pour lister les équipe
     * @param TeamRepository $teamRepository
     * @param GameRepository $gameRepository
     * @param Request $request
     * @return Response
     */
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

    /**
     * Méthode pour crée une équipe et renvoyer la vue
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {

        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        // 1. Récupérer l'utilisateur connecté
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            // 2. Assigner le Owner
            $team->setOwner($user);

            // 3.  Ajouter le créateur comme premier membre de l'équipe
            $team->addUser($user);

            // 4. Initialiser les champs obligatoires qui ne sont pas dans le formulaire
            $team->setCreatedAt(new \DateTime());
            $team->setIsActive(true); // On active l'équipe par défaut

            $entityManager->persist($team);
            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Votre équipe a été créée avec succès !');

            return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team/new.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    /**
     * Méthode pour voir l'équipe en détail
     * @param Team $team
     * @return Response
     */
    #[Route('/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

    /**
     * Méthode pour modifier une équipe
     * @param Request $request
     * @param Team $team
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
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

    /**
     * Méthode pour supprimer une équipe
     * @param Request $request
     * @param Team $team
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
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
        if ($this->isCsrfTokenValid('delete_team_' . $team->getId(), $token)) {
            $this->addFlash('error', 'CSRF Token invalid');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()], Response::HTTP_SEE_OTHER);
        }

        $team->setIsActive(false);
        $team->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', "Votre équipe a été supprimée avec succès.");

        return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Méthode pour inscrire sont équipe a un tournois
     * @param Tournament $tournament
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/tournament/{id}/register-team', name: 'app_tournament_register_team', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function registerTeam(
        Tournament $tournament,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Récupérer l'ID de l'équipe choisie dans le formulaire
        $teamId = $request->request->get('team_id');
        $team = $entityManager->getRepository(Team::class)->find($teamId);

        // 1. Vérifications de base sur l'équipe
        if (!$team || $team->getOwner() !== $user) {
            $this->addFlash('danger', 'Équipe invalide ou vous n\'en êtes pas le propriétaire.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // 2. Vérifications sur le tournoi
        if (!$tournament->isActive() || ($tournament->getDeadlineJoin() && $tournament->getDeadlineJoin() < new \DateTime())) {
            $this->addFlash('danger', 'Les inscriptions sont fermées pour ce tournoi.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // 3. L'équipe est-elle déjà inscrite ?
        if ($tournament->getTeams()->contains($team)) {
            $this->addFlash('warning', sprintf('L\'équipe %s est déjà inscrite.', $team->getName()));
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // 4. Inscription (Le ManyToMany s'occupe du reste)
        $tournament->addTeam($team);
        $entityManager->flush();

        $this->addFlash('success', "Inscription réussie !");
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
    }

    /**
     * Méthode pour rejoindre une équipe
     * @param Request $request
     * @param Team $team
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/join', name: 'app_team_join', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // L'utilisateur est-il déjà dans l'équipe ?
        if ($team->getUsers()->contains($user)) {
            $this->addFlash('warning', 'Vous faites déjà partie de cette équipe !');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Limite de 15 joueurs
        if ($team->getUsers()->count() >= 15) {
            $this->addFlash('danger', 'Désolé, cette équipe a atteint sa limite de 15 joueurs.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Vérifier si l'utilisateur est déjà inscrit aux mêmes tournois avec une autre équipe
        $teamTournaments = $team->getTournaments();

        if (!$teamTournaments->isEmpty()) {
            foreach ($user->getTeams() as $userTeam) {
                foreach ($userTeam->getTournaments() as $userTournament) {
                    if ($teamTournaments->contains($userTournament)) {
                        $this->addFlash('danger',
                            'Vous ne pouvez pas rejoindre. Vous êtes déjà inscrit au tournoi "' . $userTournament->getTitle() . '" avec l\'équipe "' . $userTeam->getName() . '".'
                        );
                        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
                    }
                }
            }
        }

        // Si tout est bon, on ajoute le joueur
        $team->addUser($user);
        $entityManager->flush();

        $this->addFlash('success', 'Bienvenue ! Vous avez rejoint l\'équipe ' . $team->getName() . '.');

        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
    }

    /**
     * Méthode pour quitter une équipe
     * @param Request $request
     * @param Team $team
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/leave', name: 'app_team_leave', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 1. Vérification CSRF (Sécurité)
        if (!$this->isCsrfTokenValid('leave' . $team->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (Token CSRF).');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // 2. Vérification : Est-ce le propriétaire ? (Interdit de quitter sa propre team)
        if ($team->getOwner() === $user) {
            $this->addFlash('danger', 'Le propriétaire ne peut pas quitter son équipe. Vous devez la supprimer.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // 3. Vérification : Est-il membre ?
        if (!$team->getUsers()->contains($user)) {
            $this->addFlash('warning', 'Vous ne faites pas partie de cette équipe.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // 4. On retire l'utilisateur
        $team->removeUser($user);
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez quitté l\'équipe avec succès.');

        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
    }

    #[Route('/{id}/member', name: 'app_team_members', methods: ['GET'])]
    public function members(Team $team, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser(); // Récupère l'utilisateur connecté
        $members = $team->getUsers();
        $owner = $team->getOwner();

        // Vérification : Seul le owner peut retirer un membre
        if ($owner !== $currentUser) {
            $this->addFlash('danger', 'Acces interdit.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/members.html.twig', [
            'team' => $team,
            'members' => $members,
            'user' => $currentUser,
        ]);
    }

    #[Route('/{teamId}/member/{userId}/leave', name: 'app_team_member_leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leaveMember(
        #[MapEntity(id: 'teamId')] Team $team,
        #[MapEntity(id: 'userId')] User $userToRemove,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        $owner = $team->getOwner();

        // Vérification : Seul le owner peut retirer un membre
        if ($owner !== $currentUser) {
            $this->addFlash('danger', 'Seul le propriétaire de l\'équipe peut retirer un membre.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Vérification CSRF (Sécurité)
        if (!$this->isCsrfTokenValid('delete_team_member_' . $team->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide (Token CSRF).');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Vérification : Le owner ne peut pas se retirer lui-même
        if ($userToRemove === $owner) {
            $this->addFlash('danger', 'Le propriétaire ne peut pas quitter son équipe. Vous devez la supprimer.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Vérification : L'utilisateur est-il membre de l'équipe ?
        if (!$team->getUsers()->contains($userToRemove)) {
            $this->addFlash('warning', 'Cet utilisateur ne fait pas partie de cette équipe.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        // Retrait de l'utilisateur
        $team->removeUser($userToRemove);
        $entityManager->flush();

        $this->addFlash('success', 'Le membre a été retiré de l\'équipe avec succès.');

        return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
    }

}
