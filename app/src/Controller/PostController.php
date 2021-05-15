<?php
/**
 * Post controller.
 */

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Service\PostService;
use App\Service\CommentService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PostController.
 *
 * @Route("/")
 */
class PostController extends AbstractController
{
    /**
     * Post service.
     *
     * @var PostService
     */
    private $postService;

    /**
     * Comment service.
     *
     * @var CommentService
     */
    private $commentService;

    /**
     * PostController constructor.
     *
     * @param PostService    $postService    Post service
     * @param CommentService $commentService Comment service
     */
    public function __construct(PostService $postService, CommentService $commentService)
    {
        $this->postService = $postService;
        $this->commentService = $commentService;
    }

    /**
     * Index action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     *
     * @Route(
     *     "/",
     *     methods={"GET", "POST"},
     *     name="post_index",
     * )
     */
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        $filters = [];
        $filters['category_id'] = $request->query->getInt('filters_category_id');
        $filters['tag_id'] = $request->query->getInt('filters_tag_id');

        if ($form->isSubmitted() && $form->isValid()) {
            $filters['search'] = $form->getData();
        }

        $mode = 'main';
        if($this->isGranted('ROLE_ADMIN')){
            $mode = 'main_admin';
        }

        $pagination = $this->postService->createPaginatedList(
            $request->query->getInt('page', 1),
            $mode,
            null,
            $filters
        );

        return $this->render(
            'post/index.html.twig',
            [
                'pagination' => $pagination,
                'form' => $form->createView()
            ]
        );
    }

    /**
     * Show action.
     *
     * @param Post    $post    Post entity
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     *
     * @Route(
     *     "/{id}",
     *     methods={"GET"},
     *     name="post_show",
     *     requirements={"id": "[1-9]\d*"},
     * )
     */
    public function show(Post $post, Request $request): Response
    {
        if ($post->getAuthor()->getBlocked()) {
            if(!$this->isGranted('ROLE_ADMIN')){
                return $this->redirectToRoute('post_index');
            }
        }

        if (!$post->getPublished()) {
            if(!$this->isGranted('MANAGE', $post)){
                return $this->redirectToRoute('post_index');
            }
        }

        $page = $request->query->getInt('page', 1);
        $pagination = $this->commentService->createPaginatedList($page, $post, null);

        return $this->render(
            'post/show.html.twig',
            [
                'post' => $post,
                'pagination' => $pagination,
            ]
        );
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @Route(
     *     "/create",
     *     methods={"GET", "POST"},
     *     name="post_create",
     * )
     *
     * @IsGranted(
     *     "IS_AUTHENTICATED_REMEMBERED",
     * )
     */
    public function create(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAuthor($this->getUser());
            $this->postService->save($post);
            $this->addFlash('success', 'message_created_successfully');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render(
            'post/create.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * Edit action.
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
     *     "/{id}/edit",
     *     methods={"GET", "PUT"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="post_edit",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="post",
     * )
     */
    public function edit(Request $request, Post $post): Response
    {
        $form = $this->createForm(PostType::class, $post, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->postService->save($post);
            $this->addFlash('success', 'message_updated_successfully');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render(
            'post/edit.html.twig',
            [
                'form' => $form->createView(),
                'post' => $post,
            ]
        );
    }

    /**
     * Delete action.
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
     *     "/{id}/delete",
     *     methods={"GET", "DELETE"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="post_delete",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="post",
     * )
     */
    public function delete(Request $request, Post $post): Response
    {
        $form = $this->createForm(FormType::class, $post, ['method' => 'DELETE']);
        $form->handleRequest($request);

        if ($request->isMethod('DELETE') && !$form->isSubmitted()) {
            $form->submit($request->request->get($form->getName()));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->postService->delete($post);
            $this->addFlash('success', 'message_deleted_successfully');

            return $this->redirectToRoute('post_index');
        }

        return $this->render(
            'post/delete.html.twig',
            [
                'form' => $form->createView(),
                'post' => $post,
            ]
        );
    }
}