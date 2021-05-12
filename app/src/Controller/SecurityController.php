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
     * Login form action
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return Response
     *
     * @Route(
     *     "/login",
     *     name="app_login"
     * )
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error
            ]
        );
    }

    /**
     * Logout action
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
     * Logout success action
     *
     * @return Response
     *
     * @Route(
     *     "/logout_success",
     *     name="app_logout_success"
     * )
     */
    public function logoutSuccess(): Response
    {
        $this->addFlash('success', 'message_logout_successfully');
        return $this->redirectToRoute('home_index');
    }
}
