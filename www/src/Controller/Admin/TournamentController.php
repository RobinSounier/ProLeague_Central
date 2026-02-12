<?php

namespace App\Controller\Admin;

use App\Entity\Tournament;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class TournamentController extends AbstractController
{

    /**
     * afficher la liste des tournois avec systemes de filtres et de recherche
     * @param TournamentRepository $tournamentRepository
     * @param Request $request
     * @return Response
     */


    #[Route('/tournament', name: 'app_admin_tournament')]
    public function index(TournamentRepository $tournamentRepository, Request $request, PaginatorInterface $paginator): Response
    {
        // on recupere les parametres de recherche ou de tri depuis l'url
        $search = $request->query->get('search', '');
        $filter = $request->query->get('filter', 'all'); // all, active ,inactive , admins etc

        // on recupere tous les tournois trie du plus recent au plus ancien
        $tournaments = $tournamentRepository->findBy([], ['createdAt' => 'DESC']);



        // Filtre de tri
        if ($filter === 'active') {
            $tournaments = array_filter($tournaments, fn($c) => $c->isActive());
        } elseif ($filter === 'inactive') {
            $tournaments = array_filter($tournaments, fn($c) => !$c->isActive());
        }

        //Recherche
        if ($search) {
            $tournaments = array_filter($tournaments, function ($tournament) use ($search) {
                return stripos($tournament->getTitle(), $search) !== false
                    || stripos($tournament->getDescription(), $search) !== false
                    || stripos($tournament->getGame()->getLabel(), $search) !== false;
            });
        }

        //reindexer le tableau après filtrage
        $tournaments = array_values($tournaments);


        $pagination = $paginator->paginate(
            $tournaments,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('admin/tournament/index.html.twig', [
            'pagination' => $pagination,
            'tournaments' => $tournaments,
            'search' => $search,
            'filter' => $filter
        ]);

    }

    /**
     * affiche le detail du tournoi
     * @param Tournament $tournament
     * @return Response
     */
    #[Route('/tournament/{id}', name: 'app_admin_tournament_show')]
    #[IsGranted('ROLE_ADMIN')]


    public function show(Tournament $tournament): Response
    {
        return $this->render('admin/tournament/show.html.twig', [
            'tournament' => $tournament,
        ]);
    }

    /**
     * activer desactiver un tournoi
     * @param Tournament $tournament
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */

    #[Route('/tournament/{id}/toggle-active', name: 'app_admin_tournament_toggle_active', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function tournamentToggleActive(Tournament $tournament, Request $request, EntityManagerInterface $entityManager): Response
    {
        // verifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_tournament_' . $tournament->getId(), $token)) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_admin_tournament_show', ['id' => $tournament->getId()]);
        }

        $tournament->setIsActive(!$tournament->isActive());
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Le tournoi %s a été %s avec succès.',
            $tournament->getTitle(),
            $tournament->isActive() ? 'activé' : 'désactivé'
        ));
        return $this->redirectToRoute('app_admin_tournament_show', ['id' => $tournament->getId()]);
    }

    /**
     * supprimer un tournoi
     * @param Tournament $tournament
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */

    #[Route('/tournament/{id}/delete', name: 'app_admin_tournament_delete', methods: ['POST'])]
    public function TournamentDelete(
        Tournament $tournament,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // verifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_tournament_' . $tournament->getId(), $token)) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_admin_tournament_show', ['id' => $tournament->getId()]);
        }

        // Ne pas permettre de supprimer son propre compte
        if ($tournament === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
            return $this->redirectToRoute('app_admin_tournament_show', ['id' => $tournament->getId()]);
        }

        $tournament->setIsActive(false); // Désactiver le compte avant de le supprimer
        $tournament->setUpdatedAt(new \DateTime()); // Mettre à jour la date de modification

        $entityManager->persist($tournament);
        $entityManager->flush();
        $this->addFlash('success', "Le tournoi a été supprimé avec succès.");


        return $this->redirectToRoute('app_admin_tournament');
    }
}
