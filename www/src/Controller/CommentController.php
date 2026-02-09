<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\TournamentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommentController extends AbstractController
{
    #[Route('/tournament/{id}/comment', name: 'app_comment_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        int $id,
        TournamentRepository $tournamentRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ) {
        //on récupère le challenge actif
        $tournament = $tournamentRepository->findActive($id);

        if (!$tournament) {
            $this->addFlash('error', "Ce tournoi n'existe pas ou n'est plus actif.");
            return $this->redirectToRoute('app_tournament_index');
        }

        //on récupère le user en session
        $user = $this->getUser();
        //on crée un objet Comment
        $comment = new Comment();
        //on peut deja setter user et challenge dans comment
        $comment->setAuthor($user);
        $comment->setTournament($tournament);

        //on récupère les data passé dans le formulaire
        $commentData = $request->request->all('comment');
        //on stock le token passé par le formulaire
        $submittedToken = $commentData['_token'] ?? null;
        //on stock le parentComment s'il existe
        $parentId = $commentData['parentComment'] ?? null;
        //on stocke le contenu du commentaire
        $content = $commentData['content'] ?? null;

        // vérifier si c'est une réponse (formulaire HTML simple) ou un commentaire principal (formulaire symfony)
        $isReplyForm = $parentId && $parentId !== '' && is_numeric($parentId);

        if ($isReplyForm) {
            //Pour les réponses: validation manuelle du token CSRF et du contenu
            if (!$submittedToken || !$this->isCsrfTokenValid('submit_tournament_' . $tournament->getId(), $submittedToken)) {
                $this->addFlash('error', "Le token CSRF est invalide, veuillez réessayer.");
                return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
            }

            // on verifie que le contenu n'est pas vide
            if (empty(trim($content))) {
                $this->addFlash('error', "Le commentaire ne peut pas être vide.");
                return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
            }

            // on verifie que le contenu ne dépasse pas les 5000 caractères
            if (strlen($content) > 5000) {
                $this->addFlash('error', "Le commentaire ne peut pas dépasser 5000 caractères.");
                return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
            }

            //on peut setter les information a comment
            $comment->setContent($content);
            $comment->setCreatedAt(new DateTime());

            //definir le parent
            $parentComment = $entityManager->getRepository(Comment::class)->find((int)$parentId);
            if ($parentComment && $parentComment->getTournament() === $tournament) {
                $comment->setParentComment($parentComment);
            }

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', "Le commentaire a été ajouté avec succès.");
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        //pour les commentaires principaux : utiliser le formulaire symfony
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new DateTime());

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', "Le commentaire a été ajouté avec succès.");
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];

            foreach ($form->getErrors(true, false) as $error) {
                $errors[] = $error->getMessage();
            }

            //on ajoute les erreurs de validations des champs
            foreach ($form->all() as $child) {
                foreach ($child->getErrors() as $error) {
                    $errors[] = $error->getMessage();
                }
            }

            //message par défault si aucune erreur spécifique n'est trouvée
            if (empty($errors)) {
                $errors[] = "Le formulaire contient des erreurs. Veuillez vérifier les données.";
            }

            $this->addFlash('error', "Erreur lors de l'ajout d'un commentaire:" . implode(', ', $errors));
        }
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
    }
}
