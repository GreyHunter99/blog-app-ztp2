<?php
/**
 * User controller.
 */

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserData;
use App\Form\ChangePasswordType;
use App\Form\UserDataType;
use App\Service\UserDataService;
use App\Service\UserService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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
     * UserController constructor.
     *
     * @param UserService     $userService     User service
     * @param UserDataService $userDataService User data service
     */
    public function __construct(UserService $userService, UserDataService $userDataService)
    {
        $this->userService = $userService;
        $this->userDataService = $userDataService;
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
     *
     * @return Response HTTP response
     *
     * @Route(
     *     "/{id}",
     *     methods={"GET"},
     *     name="user_show",
     *     requirements={"id": "[1-9]\d*"},
     * )
     */
    public function show(User $user): Response
    {
        return $this->render(
            'user/show.html.twig',
            ['user' => $user]
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
        $form = $this->createForm(FormType::class, $user, ['method' => 'PUT']);
        $form->handleRequest($request);
        $roles = $user->getRoles();

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
}