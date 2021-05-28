<?php
/**
 * Post service.
 */

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Class PostService.
 */
class PostService
{
    /**
     * Post repository.
     *
     * @var PostRepository
     */
    private $postRepository;

    /**
     * Paginator.
     *
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * Category service.
     *
     * @var CategoryService
     */
    private $categoryService;

    /**
     * Tag service.
     *
     * @var TagService
     */
    private $tagService;

    /**
     * PostService constructor.
     *
     * @param PostRepository     $postRepository  Post repository
     * @param PaginatorInterface $paginator       Paginator
     * @param CategoryService    $categoryService Category service
     * @param TagService         $tagService      Tag service
     */
    public function __construct(PostRepository $postRepository, PaginatorInterface $paginator, CategoryService $categoryService, TagService $tagService)
    {
        $this->postRepository = $postRepository;
        $this->paginator = $paginator;
        $this->categoryService = $categoryService;
        $this->tagService = $tagService;
    }

    /**
     * Create paginated list.
     *
     * @param int       $page    Page number
     * @param string    $mode    Mode
     * @param User|null $user    User
     * @param array     $filters Filters array
     *
     * @return PaginationInterface Paginated list
     */
    public function createPaginatedList(int $page, string $mode, ?User $user, array $filters = []): PaginationInterface
    {
        $filters = $this->prepareFilters($filters);

        if ('main_admin' === $mode) {
            return $this->paginator->paginate(
                $this->postRepository->queryAll($filters),
                $page,
                PostRepository::PAGINATOR_ITEMS_PER_PAGE
            );
        }
        if ('main' === $mode) {
            return $this->paginator->paginate(
                $this->postRepository->queryPublished($filters),
                $page,
                PostRepository::PAGINATOR_ITEMS_PER_PAGE
            );
        }
        if ('profile_author' === $mode) {
            return $this->paginator->paginate(
                $this->postRepository->queryByAuthor($user, $filters),
                $page,
                PostRepository::PAGINATOR_ITEMS_PER_PAGE
            );
        }

        return $this->paginator->paginate(
            $this->postRepository->queryPublishedByAuthor($user, $filters),
            $page,
            PostRepository::PAGINATOR_ITEMS_PER_PAGE
        );
    }

    /**
     * Save post.
     *
     * @param Post $post Post entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Post $post): void
    {
        $this->postRepository->save($post);
    }

    /**
     * Delete post.
     *
     * @param Post $post Post entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Post $post): void
    {
        $this->postRepository->delete($post);
    }

    /**
     * Prepare filters for the posts list.
     *
     * @param array $filters Raw filters from request
     *
     * @return array Result array of filters
     */
    private function prepareFilters(array $filters): array
    {
        $resultFilters = [];
        if (isset($filters['category_id']) && is_numeric($filters['category_id'])) {
            $category = $this->categoryService->findOneById(
                $filters['category_id']
            );
            if (null !== $category) {
                $resultFilters['category'] = $category;
            }
        }

        if (isset($filters['tag_id']) && is_numeric($filters['tag_id'])) {
            $tag = $this->tagService->findOneById($filters['tag_id']);
            if (null !== $tag) {
                $resultFilters['tag'] = $tag;
            }
        }

        if (isset($filters['search'])) {
            $resultFilters['search'] = $filters['search'];
        }

        return $resultFilters;
    }
}
