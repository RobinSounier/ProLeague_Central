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
     * Méthode pour ajouter un vote à un challenge
     * @param int $id
     * @param VoteRepository $voteRepository
     * @param TournamentRepository $tournamentRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @throws ORMException
     */
    #[Route('/tournament/{id}/vote', name: 'app_tournament_vote', methods: ['POST'])]
    #[isGranted('ROLE_USER')]
    public function addVote(
        int                    $id,
        VoteRepository         $voteRepository,
        TournamentRepository    $tournamentRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        $user = $this->getUser();
        $challenge = $tournamentRepository->findActive($id);

        if (!$challenge) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce tournoi n\'existe pas ou n\'est plus actif',
            ], Response::HTTP_NOT_FOUND);
        }

        //verifier si l'utilisateur a deja voter
        $existingVote = $voteRepository->findOneBy([
            'author' => $user,
            'tournament' => $challenge
        ]);

        if ($existingVote) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous avez déjà voter pour ce défi',
                'voteCount' => $challenge->getVotes()->count()
            ], Response::HTTP_BAD_REQUEST);
        }

        //creation du vote
        $vote = new Vote();
        $vote->setAuthor($user);
        $vote->setTournament($challenge);
        $vote->setCreatedAt(new DateTime());

        $entityManager->persist($vote);
        $entityManager->flush();

        //recharger le challenge pour avoir le bon nombre de vote
        $entityManager->refresh($challenge);


        return new JsonResponse([
            'success' => true,
            'message' => 'Votre vote a été enregistrer avec succes',
            'voteCount' => $challenge->getVotes()->count()
        ]);
    }

    /**
     * méthode qui supprime un vote du challenge
     * @param int $id
     * @param VoteRepository $voteRepository
     * @param TournamentRepository $tournamentRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/tournament/{id}/vote', name: 'app_vote_delete', methods: ['DELETE'])]
    #[isGranted('ROLE_USER')]
    public function deleteVote(
        int                    $id,
        VoteRepository         $voteRepository,
        TournamentRepository    $tournamentRepository,
        EntityManagerInterface $entityManager

    ): JsonResponse
    {
        //on recupere l'utilisateur
        $user = $this->getUser();
        // on recupere le challenge
        $challenge = $tournamentRepository->findActive($id);

        if (!$challenge) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce tournoi n\'existe pas ou n\'est plus actif',
            ], Response::HTTP_NOT_FOUND);
        }

        //verifier si l'utilisateur a deja voter
        $existingVote = $voteRepository->findOneBy([
            'author' => $user,
            'tournament' => $challenge
        ]);

        if (!$existingVote) {

            return new JsonResponse([
                'success' => false,
                'message' => "Vous n'avez pas voter pour ce defi",
                'voteCount' => $challenge->getVotes()->count()
            ], Response::HTTP_BAD_REQUEST);

        }
        $entityManager->remove($existingVote);
        $entityManager->flush();

        //recharger le challenge pour avoir le bon nombre de vote
        $entityManager->refresh($challenge);

        return new JsonResponse([
            'success' => true,
            'message' => 'Vote supprimer avec succes',
            'voteCount' => $challenge->getVotes()->count()
        ]);
    }
}
