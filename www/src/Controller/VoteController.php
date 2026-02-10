<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\Vote;
use App\Repository\TournamentRepository;
use App\Repository\VoteRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class VoteController extends AbstractController
{
    /**
     * Méthode pour ajouter un vote à un tournoi
     */
    #[Route('/tournament/{id}/vote', name: 'app_tournament_vote_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addVoteTournament(
        int                    $id,
        VoteRepository         $voteRepository,
        TournamentRepository   $tournamentRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        $user = $this->getUser();
        $tournament = $tournamentRepository->findActive($id);

        if (!$tournament) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce tournoi n\'existe pas ou n\'est plus actif',
            ], Response::HTTP_NOT_FOUND);
        }

        //verifier si l'utilisateur a deja voter
        $existingVote = $voteRepository->findOneBy([
            'author' => $user,
            'tournament' => $tournament
        ]);

        if ($existingVote) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous avez déjà voté pour ce tournoi',
                'voteCount' => $tournament->getVotes()->count()
            ], Response::HTTP_BAD_REQUEST);
        }

        //creation du vote
        $vote = new Vote();
        $vote->setAuthor($user);
        $vote->setTournament($tournament);
        $vote->setCreatedAt(new DateTime());

        $entityManager->persist($vote);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre vote a été enregistré avec succès',
            'voteCount' => $tournament->getVotes()->count()
        ]);
    }

    /**
     * méthode qui supprime un vote du tournoi
     */
    #[Route('/tournament/{id}/vote', name: 'app_tournament_vote_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteVoteTournament(
        int                    $id,
        VoteRepository         $voteRepository,
        TournamentRepository   $tournamentRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        //on recupere l'utilisateur
        $user = $this->getUser();
        // on recupere le tournoi
        $tournament = $tournamentRepository->findActive($id);

        if (!$tournament) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce tournoi n\'existe pas ou n\'est plus actif',
            ], Response::HTTP_NOT_FOUND);
        }

        //verifier si l'utilisateur a deja voter
        $existingVote = $voteRepository->findOneBy([
            'author' => $user,
            'tournament' => $tournament
        ]);

        if (!$existingVote) {
            return new JsonResponse([
                'success' => false,
                'message' => "Vous n'avez pas voté pour ce tournoi",
                'voteCount' => $tournament->getVotes()->count()
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($existingVote);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Vote supprimé avec succès',
            'voteCount' => $tournament->getVotes()->count()
        ]);
    }
}
