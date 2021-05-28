<?php
/**
 * Access denied handler.
 */

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * Class AccessDeniedHandler.
 */
class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private $urlGenerator;

    /**
     * AccessDeniedHandler constructor.
     *
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Access Denied Handler.
     *
     * @param Request               $request
     * @param AccessDeniedException $accessDeniedException
     *
     * @return Response|null HTTP response
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        $request->getSession()->getFlashBag()->add('danger', 'message_access_denied');

        return new RedirectResponse($this->urlGenerator->generate('post_index'));
    }
}
