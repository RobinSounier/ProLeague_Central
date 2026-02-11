<?php

namespace App\Controller\Admin;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/game')]
#[IsGranted('ROLE_ADMIN')]
final class GameController extends AbstractController
{
    #[Route(name: 'app_admin_game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepository): Response
    {
        return $this->render('admin/game/index.html.twig', [
            'games' => $gameRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_game_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($game);
            $entityManager->flush();

            $this->addFlash('success', 'Jeu ajouté avec succès.');

            return $this->redirectToRoute('app_admin_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/game/new.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_game_show', methods: ['GET'])]
    public function show(Game $game): Response
    {
        return $this->render('admin/game/show.html.twig', [
            'game' => $game,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_game_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Game $game, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Jeu modifié avec succès.');
            return $this->redirectToRoute('app_admin_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, EntityManagerInterface $entityManager): Response
    {

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_game_'.$game->getId(), $token)) {
            $this->addFlash('error', "Token CSRF invalide.");
            $this->redirectToRoute('app_admin_game_index');
        }

        //on vérifie si le jeu est utilisé
        if($game->getTournaments()->count() > 0){
            $this->addFlash('error', "Impossible de supprimer ce jeu car il est utilisé par des tournois.");
            $this->redirectToRoute('app_admin_game_index');
        }

        $entityManager->remove($game);
        $entityManager->flush();

        $this->addFlash('success', "Jeu supprimé avec succès.");
        return $this->redirectToRoute('app_admin_game_index', [], Response::HTTP_SEE_OTHER);
    }
}
