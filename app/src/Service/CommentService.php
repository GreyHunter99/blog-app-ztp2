<?php
/**
 * Comment service.
 */

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CommentRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Class CommentService.
 */
class CommentService
{
    /**
     * Comment repository.
     *
     * @var CommentRepository
     */
    private $commentRepository;

    /**
     * Paginator.
     *
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * CommentService constructor.
     *
     * @param CommentRepository  $commentRepository Comment repository
     * @param PaginatorInterface $paginator         Paginator
     */
    public function __construct(CommentRepository $commentRepository, PaginatorInterface $paginator)
    {
        $this->commentRepository = $commentRepository;
        $this->paginator = $paginator;
    }

    /**
     * Create paginated list.
     *
     * @param int       $page Page number
     * @param Post|null $post Post entity
     * @param User|null $user User entity
     *
     * @return PaginationInterface Paginated list
     */
    public function createPaginatedList(int $page, ?Post $post, ?User $user): PaginationInterface
    {
        if ($post) {
            return $this->paginator->paginate(
                $this->commentRepository->queryPostComments($post),
                $page,
                CommentRepository::PAGINATOR_ITEMS_PER_PAGE
            );
        }

        if ($user) {
            return $this->paginator->paginate(
                $this->commentRepository->queryByAuthor($user),
                $page,
                CommentRepository::PAGINATOR_ITEMS_PER_PAGE
            );
        }

        return $this->paginator->paginate(
            $this->commentRepository->queryAll(),
            $page,
            CommentRepository::PAGINATOR_ITEMS_PER_PAGE
        );
    }

    /**
     * Save comment.
     *
     * @param Comment $comment Comment entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Comment $comment): void
    {
        $this->commentRepository->save($comment);
    }

    /**
     * Delete comment.
     *
     * @param Comment $comment Comment entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Comment $comment): void
    {
        $this->commentRepository->delete($comment);
    }
}
