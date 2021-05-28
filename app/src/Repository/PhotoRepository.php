<?php
/**
 * Photo repository.
 */

namespace App\Repository;

use App\Entity\Photo;
use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class PhotoRepository.
 *
 * @method Photo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Photo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Photo[]    findAll()
 * @method Photo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoRepository extends ServiceEntityRepository
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
     * PhotoRepository constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    /**
     * Save record.
     *
     * @param Photo $photo Photo entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Photo $photo): void
    {
        $this->_em->persist($photo);
        $this->_em->flush();
    }

    /**
     * Delete record.
     *
     * @param Photo $photo Photo entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Photo $photo): void
    {
        $this->_em->remove($photo);
        $this->_em->flush();
    }

    /**
     * Query all comments for specific post.
     *
     * @param Post $post Post entity
     *
     * @return QueryBuilder Query builder
     */
    public function queryPostPhotos(Post $post): QueryBuilder
    {
        return $this->getOrCreateQueryBuilder()
            ->select(
                'partial photo.{id, filename}',
                'partial post.{id, title}'
            )
            ->join('photo.post', 'post')
            ->andWhere('photo.post = :post')
            ->setParameter('post', $post)
            ->orderBy('photo.id', 'DESC');
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
        return $queryBuilder ?? $this->createQueryBuilder('photo');
    }
}
