<?php
/**
 * User controller.
 */

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserData;
use App\Form\ChangePasswordType;
use App\Form\UserDataType;
use App\Service\CommentService;
use App\Service\PostService;
use App\Service\UserDataService;
use App\Service\UserService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class UserController.
 *
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * User service.
     *
     * @var UserService
     */
    private $userService;

    /**
     * User data service.
     *
     * @var UserDataService
     */
    private $userDataService;

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
     * UserController constructor.
     *
     * @param UserService     $userService     User service
     * @param UserDataService $userDataService User data service
     * @param PostService     $postService     Post service
     * @param CommentService  $commentService  Comment service
     */
    public function __construct(UserService $userService, UserDataService $userDataService, PostService $postService, CommentService $commentService)
    {
        $this->userService = $userService;
        $this->userDataService = $userDataService;
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
     *     methods={"GET"},
     *     name="user_index",
     * )
     *
     * @IsGranted(
     *     "ROLE_ADMIN",
     * )
     */
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $pagination = $this->userService->createPaginatedList($page);

        return $this->render(
            'user/index.html.twig',
            ['pagination' => $pagination]
        );
    }

    /**
     * Show action.
     *
     * @param User $user User entity
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     *
     * @Route(
     *     "/{id}",
     *     methods={"GET", "POST"},
     *     name="user_show",
     *     requirements={"id": "[1-9]\d*"},
     * )
     */
    public function show(User $user, Request $request): Response
    {
        if ($user->getBlocked()) {
            if(!$this->isGranted('ROLE_ADMIN')){
                return $this->redirectToRoute('post_index');
            }
        }

        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        $filters = [];
        $filters['category_id'] = $request->query->getInt('filters_category_id');
        $filters['tag_id'] = $request->query->getInt('filters_tag_id');

        if ($form->isSubmitted() && $form->isValid()) {
            $filters['search'] = $form->getData();
        }

        $mode = 'profile';
        if($this->isGranted('MANAGE', $user)){
            $mode = 'profile_author';
        }

        $pagination = $this->postService->createPaginatedList(
            $request->query->getInt('page', 1),
            $mode,
            $user,
            $filters
        );

        return $this->render(
            'user/show.html.twig',
            [
                'user' => $user,
                'pagination' => $pagination,
                'form' => $form->createView()
            ]
        );
    }

    /**
     * Change password action.
     *
     * @param Request                      $request         HTTP request
     * @param User                         $user            User entity
     * @param UserPasswordEncoderInterface $passwordEncoder Password encoder
     *
     * @return Response HTTP response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @Route(
     *     "/{id}/changePassword",
     *     methods={"GET", "PUT"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="user_changePassword",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="user",
     * )
     */
    public function changePassword(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $form = $this->createForm(ChangePasswordType::class, $user, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $user->getPassword()
                )
            );
            $this->userService->save($user);

            $this->addFlash('success', 'message_updated_successfully');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render(
            'user/changePassword.html.twig',
            [
                'form' => $form->createView(),
                'user' => $user,
            ]
        );
    }

    /**
     * Change data action.
     *
     * @param Request  $request  HTTP request
     * @param UserData $userData UserData entity
     *
     * @return Response HTTP response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @Route(
     *     "/{id}/changeData",
     *     methods={"GET", "PUT"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="user_changeData",
     * )
     *
     * @IsGranted(
     *     "MANAGE",
     *     subject="userData",
     * )
     */
    public function changeData(Request $request, UserData $userData): Response
    {
        $form = $this->createForm(UserDataType::class, $userData, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userDataService->save($userData);

            $this->addFlash('success', 'message_updated_successfully');

            return $this->redirectToRoute('user_show', ['id' => $userData->getUser()->getId()]);
        }

        return $this->render(
            'user/changeData.html.twig',
            [
                'form' => $form->createView(),
                'userData' => $userData,
            ]
        );
    }

    /**
     * Grant admin action.
     *
     * @param Request $request HTTP request
     * @param User    $user    User entity
     *
     * @return Response HTTP response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @Route(
     *     "/{id}/grantAdmin",
     *     methods={"GET", "PUT"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="user_grantAdmin",
     * )
     *
     * @IsGranted(
     *     "ROLE_ADMIN"
     * )
     */
    public function grantAdmin(Request $request, User $user): Response
    {
        $roles = $user->getRoles();

        if (isset($roles[1]) && $this->userService->numberOfAdmins() == 1) {
            $this->addFlash('danger', 'message_last_admin');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        $form = $this->createForm(FormType::class, $user, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (isset($roles[1])) {
                $user->setRoles(['ROLE_USER']);
            } else {
                $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            }
            $this->userService->save($user);

            $this->addFlash('success', 'message_updated_successfully');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        if (isset($roles[1])) {
            return $this->render(
                'user/revokeAdmin.html.twig',
                [
                    'form' => $form->createView(),
                    'user' => $user,
                ]
            );
        } else {
            return $this->render(
                'user/grantAdmin.html.twig',
                [
                    'form' => $form->createView(),
                    'user' => $user,
                ]
            );
        }
    }

    /**
     * Block user action.
     *
     * @param Request $request HTTP request
     * @param User    $user    User entity
     *
     * @return Response HTTP response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @Route(
     *     "/{id}/block",
     *     methods={"GET", "PUT"},
     *     requirements={"id": "[1-9]\d*"},
     *     name="user_block",
     * )
     *
     * @IsGranted(
     *     "ROLE_ADMIN"
     * )
     */
    public function block(Request $request, User $user): Response
    {
        $roles = $user->getRoles();

        if (isset($roles[1]) && $this->userService->numberOfAdmins() == 1) {
            $this->addFlash('danger', 'message_last_admin');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        $form = $this->createForm(FormType::class, $user, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getBlocked()) {
                $user->setBlocked(false);
            } else {
                $user->setBlocked(true);
            }
            $this->userService->save($user);

            $this->addFlash('success', 'message_updated_successfully');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        if ($user->getBlocked()) {
            return $this->render(
                'user/unblock.html.twig',
                [
                    'form' => $form->createView(),
                    'user' => $user,
                ]
            );
        } else {
            return $this->render(
                'user/block.html.twig',
                [
                    'form' => $form->createView(),
                    'user' => $user,
                ]
            );
        }
    }

}