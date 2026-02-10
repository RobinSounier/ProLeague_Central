<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin/CommentController - Modération des commentaires
 *
 * CONCEPTS CLÉS :
 * - @IsGranted('ROLE_ADMIN') au niveau classe : page complète réservée aux admins
 * - Recherche côté PHP : array_filter pour filtrer les résultats
 * - Soft moderation : pas de suppression immédiate, vérification CSRF d'abord
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class CommentController extends AbstractController
{
    /**
     * Affiche la liste des commentaires avec recherche
     *
     * @Route('/comment', name: 'app_admin_comment')
     *
     * @param CommentRepository Pour récupérer les commentaires
     * @param Request Pour obtenir le paramètre de recherche
     *
     * @return Response Vue de modération
     *
     * PÉDAGOGIE - RECHERCHE EN MÉMOIRE :
     * - Fetch tous les commentaires de la BD
     * - Filtrer en PHP avec array_filter()
     * - ACCEPTABLE pour petite quantité
     * - PROBLÉMATIQUE si 100k+ commentaires → faire la recherche en SQL
     */
    #[Route('/comment', name: 'app_admin_comment')]
    public function index(CommentRepository $commentRepository, Request $request): Response
    {
        // ========== RÉCUPÉRATION DE LA REQUÊTE ==========

        // Paramètre GET : ?search=mot
        $search = $request->query->get('search', '');

        // ========== RÉCUPÉRATION DES COMMENTAIRES ==========

        /**
         * findBy([], ['createdAt' => 'DESC']) :
         * - 1er param : critères de recherche (aucun, tous les commentaires)
         * - 2e param : ordre de tri (les plus récents en premier)
         *
         * Retourne : array de toutes les entités Comment
         */
        $comments = $commentRepository->findBy([], ['createdAt' => 'DESC']);

        // ========== FILTRAGE / RECHERCHE ==========

        if ($search) {
            /**
             * array_filter($array, $callback) :
             * - Applique une fonction à chaque élément
             * - Garde seulement ceux où callback retourne true
             *
             * stripos() : case-insensitive string position
             * - Retourne false si non trouvé
             * - Retourne la position sinon (même 0 = trouvé au début)
             * - !== false : vérifier qu'on a trouvé quelque chose
             */
            $comments = array_filter($comments, function ($comment) use ($search) {
                // Chercher dans le contenu du commentaire
                $foundInContent = stripos($comment->getContent(), $search) !== false;

                // Chercher dans le pseudo de l'auteur
                $foundInPseudo = stripos($comment->getAuthor()->getPseudo(), $search) !== false;

                // Retourner true si trouvé dans l'un des deux
                return $foundInContent || $foundInPseudo;
            });
        }

        /**
         * array_values() :
         * Réindexer le tableau après filtrage
         *
         * Pourquoi ?
         * - array_filter() préserve les clés
         * - Si on a [0, 1, 2, 3] et on en garde [1, 3]
         * - On obtient des clés [1, 3] au lieu de [0, 1]
         * - En Twig, for loop donne des indices bizarres
         * - array_values() remet les indices à [0, 1]
         */
        $comments = array_values($comments);

        return $this->render('admin/comment/index.html.twig', [
            'comments' => $comments,
            'search' => $search  // Afficher le terme pour montrer qu'on l'a cherché
        ]);
    }

    /**
     * Supprime un commentaire
     *
     * @Route('/comment/{id}/delete', name: 'app_admin_comment_delete', methods: ['POST'])
     *
     * @param Comment L'entité à supprimer (convertie automatiquement)
     * @param Request Pour vérifier le token CSRF
     * @param EntityManagerInterface Pour persister la suppression
     *
     * @return Response Redirection après suppression
     *
     * PÉDAGOGIE - DELETION PATTERNS :
     * - Hard delete : supprimer physiquement (utilisé ici)
     * - Soft delete : marquer comme supprimé (ailleurs dans l'app)
     * - Le choix dépend du contexte (votes/commentaires = hard delete ok)
     */
    #[Route('/comment/{id}/delete', name: 'app_admin_comment_delete', methods: ['POST'])]
    public function deleteComment(
        Comment $comment,
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        // ========== VALIDATION DU TOKEN CSRF ==========

        /**
         * isCsrfTokenValid(intention, token) :
         * - intention : identifiant du formulaire
         * - token : token envoyé dans le POST
         *
         * Protège contre les attaques CSRF
         * (forcer un admin à supprimer sans qu'il le sache)
         */
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete_comment_' . $comment->getId(), $token)) {
            $this->addFlash('error', "Token CSRF invalide");
            return $this->redirectToRoute('app_admin_comment');
        }

        // ========== SUPPRESSION ==========

        // remove() : marquer pour suppression
        $entityManager->remove($comment);

        // flush() : EXÉCUTE le DELETE
        $entityManager->flush();

        $this->addFlash('success', "Commentaire supprimé avec succès");
        return $this->redirectToRoute('app_admin_comment');
    }
}
