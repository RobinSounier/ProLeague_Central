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
    #[Route('/tournament/{id}/vote', name: 'app_tournament_vote_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleVoteTournament(
        int $id,
        VoteRepository $voteRepository,
        TournamentRepository $tournamentRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getUser();
        $tournament = $tournamentRepository->findActive($id);

        if (!$tournament) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce tournoi n\'existe pas ou n\'est plus actif',
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur a déjà voté
        $existingVote = $voteRepository->findOneBy([
            'author' => $user,
            'tournament' => $tournament
        ]);

        if ($existingVote) {
            // Supprimer le vote
            $entityManager->remove($existingVote);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'action' => 'removed',
                'message' => 'Vote supprimé',
                'voteCount' => $tournament->getVotes()->count()
            ]);
        }

        // Créer le vote
        $vote = new Vote();
        $vote->setAuthor($user);
        $vote->setTournament($tournament);
        $vote->setCreatedAt(new \DateTime());

        $entityManager->persist($vote);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'action' => 'added',
            'message' => 'Vote ajouté',
            'voteCount' => $tournament->getVotes()->count()
        ]);
    }
}
