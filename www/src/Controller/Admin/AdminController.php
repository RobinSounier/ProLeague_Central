<?php

namespace App\Controller\Admin;

use App\Repository\CommentRepository;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use App\Repository\TournamentRepository;
use App\Repository\UserRepository;
use App\Repository\VoteRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        TournamentRepository $tournamentRepository,
        VoteRepository $voteRepository,
        CommentRepository $commentRepository,
        GameRepository $gameRepository,
        TeamRepository $teamRepository
    ): Response
    {

        // statistiques globales
        $stats = [
            'users' => [
                'total' => $userRepository->count([]),
                'active' => $userRepository->count(['isActive' => true]),
                'admins' => count(array_filter($userRepository->findAll(), fn($u) => in_array('ROLE_ADMIN', $u->getRoles()))),
            ],
            'tournaments' => [
                'total' => $tournamentRepository->count([]),
                'active' => $tournamentRepository->count(['isActive' => true]),
            ],
            'votes' => $voteRepository->count([]),
            'comments' => $commentRepository->count([]),
            'games' => $gameRepository->count([]),
            'teams' => [
                'total' => $teamRepository->count([]),
                'active' => $teamRepository->count(['isActive' => true]),
                
            ],
            ];


            // recuperer les 5 tournois les plus recents
            $recentTournaments = $tournamentRepository->findBy(['isActive' => true], ['createdAt' => 'DESC'], 5);

            // recuperer les dernier utilisateur inscrits
            $recentUsers = $userRepository->findBy(['isActive' => true], ['createdAt' => 'DESC'], 5);

        





        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentTournaments' => $recentTournaments,
            'recentUsers' => $recentUsers,
            'recentTeams' => $teamRepository->findBy(['isActive' => true], ['createdAt' => 'DESC'], 5),
        ]);
    }
}
