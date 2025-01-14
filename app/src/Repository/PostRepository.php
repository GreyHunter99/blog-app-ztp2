<?php
/**
 * Post repository.
 */

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class PostRepository.
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    /**
     * Items per page.
     *
     * Use constants to define configuration options that rarely change instead
     * of specifying them in app/config/config.yml.
     * See https://symfony.com/doc/current/best_practices.html#configuration
     *
     * @constant int
     */
    const PAGINATOR_ITEMS_PER_PAGE = 3;

    /**
     * PostRepository constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Save record.
     *
     * @param Post $post Post entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Post $post): void
    {
        $this->_em->persist($post);
        $this->_em->flush();
    }

    /**
     * Delete record.
     *
     * @param Post $post Post entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Post $post): void
    {
        $this->_em->remove($post);
        $this->_em->flush();
    }

    /**
     * Query all records.
     *
     * @param array $filters Filters array
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->getOrCreateQueryBuilder()
            ->select(
                'partial post.{id, createdAt, updatedAt, title, published}',
                'partial category.{id, name}',
                'partial tags.{id, name}',
                'author'
            )
            ->join('post.category', 'category')
            ->join('post.author', 'author')
            ->leftJoin('post.tags', 'tags')
            ->andWhere('author.blocked IS NULL OR author.blocked = :blocked')
            ->setParameter('blocked', '0')
            ->orderBy('post.updatedAt', 'DESC');
        $queryBuilder = $this->applyFiltersToList($queryBuilder, $filters);

        return $queryBuilder;
    }

    /**
     * Query published posts.
     *
     * @param array $filters Filters array
     *
     * @return QueryBuilder Query builder
     */
    public function queryPublished(array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->queryAll($filters);
        $queryBuilder->andWhere('post.published = :published')
            ->setParameter('published', 1);

        return $queryBuilder;
    }

    /**
     * Query posts by author.
     *
     * @param User  $user    User entity
     * @param array $filters Filters array
     *
     * @return QueryBuilder Query builder
     */
    public function queryByAuthor(User $user, array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->getOrCreateQueryBuilder()
            ->select(
                'partial post.{id, createdAt, updatedAt, title, published}',
                'partial category.{id, name}',
                'partial tags.{id, name}',
                'author'
            )
            ->join('post.category', 'category')
            ->join('post.author', 'author')
            ->leftJoin('post.tags', 'tags')
            ->andWhere('post.author = :author')
            ->setParameter('author', $user)
            ->orderBy('post.updatedAt', 'DESC');
        $queryBuilder = $this->applyFiltersToList($queryBuilder, $filters);

        return $queryBuilder;
    }

    /**
     * Query published posts by author.
     *
     * @param User  $user    User entity
     * @param array $filters Filters array
     *
     * @return QueryBuilder Query builder
     */
    public function queryPublishedByAuthor(User $user, array $filters = []): QueryBuilder
    {
        $queryBuilder = $this->queryPublished($filters);
        $queryBuilder->andWhere('post.author = :author')
            ->setParameter('author', $user);

        return $queryBuilder;
    }

    /**
     * Get or create new query builder.
     *
     * @param QueryBuilder|null $queryBuilder Query builder
     *
     * @return QueryBuilder Query builder
     */
    private function getOrCreateQueryBuilder(QueryBuilder $queryBuilder = null): QueryBuilder
    {
        return $queryBuilder ?? $this->createQueryBuilder('post');
    }

    /**
     * Apply filters to paginated list.
     *
     * @param QueryBuilder $queryBuilder Query builder
     * @param array        $filters      Filters array
     *
     * @return QueryBuilder Query builder
     */
    private function applyFiltersToList(QueryBuilder $queryBuilder, array $filters = []): QueryBuilder
    {
        if (isset($filters['category']) && $filters['category'] instanceof Category) {
            $queryBuilder->andWhere('category = :category')
                ->setParameter('category', $filters['category']);
        }

        if (isset($filters['tag']) && $filters['tag'] instanceof Tag) {
            $queryBuilder->andWhere('tags IN (:tag)')
                ->setParameter('tag', $filters['tag']);
        }

        if (isset($filters['search'])) {
            $queryBuilder->andWhere('REGEXP(post.title, :search) = true')
                ->setParameter('search', $filters['search']);
        }

        return $queryBuilder;
    }
}
