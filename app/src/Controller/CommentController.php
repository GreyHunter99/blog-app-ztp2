<?php
/**
 * Comment controller.
 */

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Service\CommentService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CommentController.
 *
 * @Route("/comment")
 */
class CommentController extends AbstractController
{
    /**
     * Comment service.
     *
     * @var CommentService
     */
    private $commentService;

    /**
     * CommentController constructor.
     *
     * @param CommentService $commentService Comment service
     */
    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     * @param Post    $post    Post entity
     *
     * @return Response HTTP response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @Route(
     *     "/{id}/create",
     *     methods={"GET", "POST"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="comment_create",
     * )
     *
     * @IsGranted(
     *     "IS_AUTHENTICATED_REMEMBERED",
     * )
     */
    public function create(Request $request, Post $post): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPost($post);
            $comment->setAuthor($this->getUser());
            $this->commentService->save($comment);
            $this->addFlash('success', 'message_created_successfully');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render(
            'comment/create.html.twig',
            [
                'form' => $form->createView(),
                'post' => $post
            ]
        );
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param Comment $comment Comment entity
     *
     * @return Response HTTP response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @Route(
     *     "/{id}/edit",
     *     methods={"GET", "PUT"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="comment_edit",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="comment",
     * )
     */
    public function edit(Request $request, Comment $comment): Response
    {
        $form = $this->createForm(CommentType::class, $comment, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentService->save($comment);
            $this->addFlash('success', 'message_updated_successfully');

            return $this->redirectToRoute('post_show', ['id' => $comment->getPost()->getId()]);
        }

        return $this->render(
            'comment/edit.html.twig',
            [
                'form' => $form->createView(),
                'comment' => $comment,
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param Comment $comment Comment entity
     *
     * @return Response HTTP response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @Route(
     *     "/{id}/delete",
     *     methods={"GET", "DELETE"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="comment_delete",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="comment",
     * )
     */
    public function delete(Request $request, Comment $comment): Response
    {
        $form = $this->createForm(FormType::class, $comment, ['method' => 'DELETE']);
        $form->handleRequest($request);

        if ($request->isMethod('DELETE') && !$form->isSubmitted()) {
            $form->submit($request->request->get($form->getName()));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentService->delete($comment);
            $this->addFlash('success', 'message_deleted_successfully');

            return $this->redirectToRoute('post_show', ['id' => $comment->getPost()->getId()]);
        }

        return $this->render(
            'comment/delete.html.twig',
            [
                'form' => $form->createView(),
                'comment' => $comment,
            ]
        );
    }
}