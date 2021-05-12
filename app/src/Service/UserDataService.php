<?php
/**
 * User data service.
 */

namespace App\Service;

use App\Entity\UserData;
use App\Repository\UserDataRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Class UserDataService.
 */
class UserDataService
{
    /**
     * User data repository.
     *
     * @var UserDataRepository
     */
    private $userDataRepository;

    /**
     * UserDataService constructor.
     *
     * @param UserDataRepository $userDataRepository User data repository
     */
    public function __construct(UserDataRepository $userDataRepository)
    {
        $this->userDataRepository = $userDataRepository;
    }

    /**
     * Save user data.
     *
     * @param UserData $userData UserData entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(UserData $userData): void
    {
        $this->userDataRepository->save($userData);
    }
}