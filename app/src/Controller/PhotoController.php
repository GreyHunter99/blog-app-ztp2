<?php
/**
 * Photo controller.
 */

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\Post;
use App\Form\PhotoType;
use App\Service\PhotoService;
use App\Service\FileUploader;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PhotoController.
 *
 * @Route("/photo")
 */
class PhotoController extends AbstractController
{
    /**
     * Photo service.
     *
     * @var PhotoService
     */
    private $photoService;

    /**
     * File uploader.
     *
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * Filesystem component.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * PhotoController constructor.
     *
     * @param PhotoService $photoService Photo service
     * @param Filesystem   $filesystem   Filesystem component
     * @param FileUploader $fileUploader File uploader
     */
    public function __construct(PhotoService $photoService, Filesystem $filesystem, FileUploader $fileUploader)
    {
        $this->photoService = $photoService;
        $this->filesystem = $filesystem;
        $this->fileUploader = $fileUploader;
    }

    /**
     * Index action.
     *
     * @param Request $request HTTP request
     * @param Post    $post    Post entity
     *
     * @return Response HTTP response
     *
     * @Route(
     *     "/{id}/",
     *     methods={"GET"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="photo_index",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="post",
     * )
     */
    public function index(Request $request, Post $post): Response
    {
        $page = $request->query->getInt('page', 1);
        $pagination = $this->photoService->createPaginatedList($page, $post);

        return $this->render(
            'photo/index.html.twig',
            [
                'pagination' => $pagination,
                'post' => $post
            ]
        );
    }

    /**
     * Show action.
     *
     * @param Photo $photo Photo entity
     *
     * @return Response HTTP response
     *
     * @Route(
     *     "/view/{id}",
     *     methods={"GET"},
     *     name="photo_show",
     *     requirements={"id": "[1-9]\d*"},
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="photo",
     * )
     */
    public function show(Photo $photo): Response
    {
        return $this->render(
            'photo/show.html.twig',
            ['photo' => $photo]
        );
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
     *     name="photo_create",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="post",
     * )
     */
    public function create(Request $request, Post $post): Response
    {
        $photo = new Photo();
        $form = $this->createForm(PhotoType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFilename = $this->fileUploader->upload(
                $form->get('file')->getData()
            );
            $photo->setPost($post);
            $photo->setFilename($photoFilename);
            $this->photoService->save($photo);
            $this->addFlash('success', 'message_created_successfully');

            return $this->redirectToRoute('photo_index', ['id' => $post->getId()]);
        }

        return $this->render(
            'photo/create.html.twig',
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
     * @param Photo   $photo   Photo
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
     *     name="photo_edit",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="photo",
     * )
     */
    public function edit(Request $request, Photo $photo): Response
    {
        $form = $this->createForm(PhotoType::class, $photo, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->filesystem->remove(
                $this->getParameter('photos_directory').'/'.$photo->getFilename()
            );
            $photoFilename = $this->fileUploader->upload(
                $form->get('file')->getData()
            );
            $photo->setFilename($photoFilename);
            $this->photoService->save($photo);

            $this->addFlash('success', 'message_updated_successfully');

            return $this->redirectToRoute('photo_index', ['id' => $photo->getPost()->getId()]);
        }

        return $this->render(
            'photo/edit.html.twig',
            [
                'form' => $form->createView(),
                'photo' => $photo,
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param Photo   $photo   Photo entity
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
     *     name="photo_delete",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="photo",
     * )
     */
    public function delete(Request $request, Photo $photo): Response
    {
        $form = $this->createForm(FormType::class, $photo, ['method' => 'DELETE']);
        $form->handleRequest($request);

        if ($request->isMethod('DELETE') && !$form->isSubmitted()) {
            $form->submit($request->request->get($form->getName()));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->filesystem->remove(
                $this->getParameter('photos_directory').'/'.$photo->getFilename()
            );
            $this->photoService->delete($photo);
            $this->addFlash('success', 'message_deleted_successfully');

            return $this->redirectToRoute('photo_index', ['id' => $photo->getPost()->getId()]);
        }

        return $this->render(
            'photo/delete.html.twig',
            [
                'form' => $form->createView(),
                'photo' => $photo,
            ]
        );
    }
}