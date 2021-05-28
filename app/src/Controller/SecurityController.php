<?php
/**
 * Security controller.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController.
 */
class SecurityController extends AbstractController
{
    /**
     * Login form action.
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @Route(
     *     "/login",
     *     name="app_login"
     * )
     *
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('post_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error,
            ]
        );
    }

    /**
     * Logout action.
     *
     * @Route(
     *     "/logout",
     *     name="app_logout"
     * )
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Logout success action.
     *
     * @Route(
     *     "/logout_success",
     *     name="app_logout_success"
     * )
     *
     * @return Response
     */
    public function logoutSuccess(): Response
    {
        $this->addFlash('success', 'message_logout_successfully');

        return $this->redirectToRoute('post_index');
    }
}
