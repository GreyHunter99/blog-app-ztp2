<?php
/**
 * Login form authenticator service.
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Class LoginFormAuthenticatorService.
 */
class LoginFormAuthenticatorService
{
    /**
     * User repository.
     *
     * @var UserRepository
     */
    private $userRepository;

    /**
     * LoginFormAuthenticatorService constructor.
     *
     * @param UserRepository     $userRepository User repository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Find user by Id.
     *
     * @param array $email User Email
     *
     * @return User|null User entity
     */
    public function findOneBy(array $email): ?User
    {
        return $this->userRepository->findOneBy($email);
    }
}