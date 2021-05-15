<?php
/**
 * Comment repository.
 */

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * Class CommentRepository.
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
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
     * CommentRepository constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Save record.
     *
     * @param Comment $comment Comment entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Comment $comment): void
    {
        $this->_em->persist($comment);
        $this->_em->flush();
    }

    /**
     * Delete record.
     *
     * @param Comment $comment Comment entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Comment $comment): void
    {
        $this->_em->remove($comment);
        $this->_em->flush();
    }

    /**
     * Query all records.
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(): QueryBuilder
    {
        return $this->getOrCreateQueryBuilder()
            ->orderBy('comment.updatedAt', 'DESC');
    }

    /**
     * Query all comments for specific post.
     *
     * @param Post $post Post entity
     *
     * @return QueryBuilder Query builder
     */
    public function queryPostComments(Post $post): QueryBuilder
    {
        return $this->getOrCreateQueryBuilder()
            ->select(
                'partial comment.{id, createdAt, updatedAt, title, content}',
                'partial post.{id, title}',
                'author'
            )
            ->join('comment.post', 'post')
            ->join('comment.author', 'author')
            ->andWhere('comment.post = :post')
            ->setParameter('post', $post)
            ->andWhere('author.blocked IS NULL OR author.blocked = :blocked')
            ->setParameter('blocked', '0')
            ->orderBy('comment.updatedAt', 'DESC');
    }

    /**
     * Query comments by author.
     *
     * @param User  $user    User entity
     *
     * @return QueryBuilder Query builder
     */
    public function queryByAuthor(User $user): QueryBuilder
    {
        return $this->getOrCreateQueryBuilder()
            ->select(
                'partial comment.{id, createdAt, updatedAt, title, content}',
                'partial post.{id, title}',
                'author'
            )
            ->join('comment.post', 'post')
            ->join('comment.author', 'author')
            ->where('comment.author = :author')
            ->setParameter('author', $user)
            ->orderBy('comment.updatedAt', 'DESC');
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
        return $queryBuilder ?? $this->createQueryBuilder('comment');
    }
}
