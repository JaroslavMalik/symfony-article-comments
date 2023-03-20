<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/articles', name: 'api_articles')]
    public function getArticles(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findAll();

        return new JsonResponse(['status' => 'OK', 'data' => $articles], Response::HTTP_OK);
    }

    #[Route('/api/article/new', name: 'api_new_article')]
    public function createArticle(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $requestData = $request->getContent();
        $data = json_decode($requestData, true);

        try {
            $article = new Article();
            $article->setTitle($data['title']);
            $article->setText($data['text']);
            $article->setContributor($data['contributor']);
            $article->setPublicationDate($data['publicationDate'] ? new \DateTime($data['publicationDate']) : new \DateTime('now'));

            $entityManager->persist($article);
            $entityManager->flush();
        } catch (Exception $exception) {
            return new JsonResponse(['status' => 'FAIL', 'errorMessage' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['status' => 'OK', 'data' => $article], Response::HTTP_OK);
    }

    #[Route('/api/article/{id}', name: 'api_get_article')]
    public function getArticle(string $id, ArticleRepository $articleRepository): JsonResponse
    {
        $article = $articleRepository->find($id);

        if ($article === null) {
            return new JsonResponse(['status' => 'FAIL', 'errorMessage' => "Article with id '{$id}' is not found."], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['status' => 'OK', 'data' => $article], Response::HTTP_OK);
    }

    #[Route('/api/article/{id}/comments', name: 'api_get_article_comments')]
    public function getComments(string $id, ArticleRepository $articleRepository): JsonResponse
    {
        $article = $articleRepository->find($id);

        if ($article === null) {
            return new JsonResponse(['status' => 'FAIL', 'errorMessage' => "Article with id '{$id}' is not found."], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['status' => 'OK', 'data' => $article->getComments()], Response::HTTP_OK);
    }

    #[Route('/api/comment/new', name: 'api_new_article')]
    public function createComment(Request $request, ArticleRepository $articleRepository, CommentRepository $commentRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $requestData = $request->getContent();
        $data = json_decode($requestData, true);

        $articleId = $data['article'] ?? '';
        if (empty($articleId)) {
            return new JsonResponse(['status' => 'FAIL', 'errorMessage' => "No article specified."], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $article = $articleRepository->find($articleId);
        if ($article === null) {
            return new JsonResponse(['status' => 'FAIL', 'errorMessage' => "Article with id '{$articleId}' is not found."], Response::HTTP_NOT_FOUND);
        }

        $parent = null;
        $parentId = $data['parent'] ?? '';
        if (!empty($parentId)) {
            $parent = $commentRepository->find($parentId);
            if ($parent === null) {
                return new JsonResponse(['status' => 'FAIL', 'errorMessage' => "Parent comment with id '{$parentId}' is not found."], Response::HTTP_NOT_FOUND);
            }
            if ($parent->getArticle()->getId() !== $article->getId()) {
                return new JsonResponse(['status' => 'FAIL', 'errorMessage' => "Parent comment is not in the same article."], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        try {
            $comment = new Comment();
            $comment->setText($data['text']);
            $comment->setContributor($data['contributor']);
            $comment->setEmail($data['contributor']);
            $comment->setPublicationDate($data['publicationDate'] ? new \DateTime($data['publicationDate']) : new \DateTime('now'));
            $comment->setArticle($article);
            $comment->setParent($parent);

            $entityManager->persist($comment);
            $entityManager->flush();
        } catch (Exception $exception) {
            return new JsonResponse(['status' => 'FAIL', 'errorMessage' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['status' => 'OK', 'data' => $comment], Response::HTTP_OK);
    }

    #[Route('/api/comment/{id}', name: 'api_get_comment')]
    public function getComment(string $id, CommentRepository $commentRepository): JsonResponse
    {
        $comment = $commentRepository->find($id);

        if ($comment === null) {
            return new JsonResponse(['status' => 'FAIL', 'errorMessage' => "Comment with id '{$id}' is not found."], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['status' => 'OK', 'data' => $comment], Response::HTTP_OK);
    }

    #[Route('/api/comment/{id}/responses', name: 'api_get_comment_responses')]
    public function getCommentResponses(string $id, CommentRepository $commentRepository): JsonResponse
    {
        $comment = $commentRepository->find($id);

        if ($comment === null) {
            return new JsonResponse(['status' => 'FAIL', 'errorMessage' => "Comment with id '{$id}' is not found."], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['status' => 'OK', 'data' => $comment->getResponses()], Response::HTTP_OK);
    }
}
