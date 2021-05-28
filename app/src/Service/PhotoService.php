<?php
/**
 * Photo service.
 */

namespace App\Service;

use App\Entity\Photo;
use App\Entity\Post;
use App\Repository\PhotoRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Class PhotoService.
 */
class PhotoService
{
    /**
     * Photo repository.
     *
     * @var PhotoRepository
     */
    private $photoRepository;

    /**
     * Paginator.
     *
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * PhotoService constructor.
     *
     * @param PhotoRepository    $photoRepository Photo repository
     * @param PaginatorInterface $paginator       Paginator
     */
    public function __construct(PhotoRepository $photoRepository, PaginatorInterface $paginator)
    {
        $this->photoRepository = $photoRepository;
        $this->paginator = $paginator;
    }

    /**
     * Save photo.
     *
     * @param Photo $photo Photo entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Photo $photo): void
    {
        $this->photoRepository->save($photo);
    }

    /**
     * Delete photo.
     *
     * @param Photo $photo Photo entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Photo $photo): void
    {
        $this->photoRepository->delete($photo);
    }

    /**
     * Create paginated list.
     *
     * @param int  $page Page number
     * @param Post $post Post entity
     *
     * @return PaginationInterface Paginated list
     */
    public function createPaginatedList(int $page, Post $post): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->photoRepository->queryPostPhotos($post),
            $page,
            PhotoRepository::PAGINATOR_ITEMS_PER_PAGE
        );
    }
}