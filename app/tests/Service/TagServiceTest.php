<?php
/**
 * TagService tests.
 */

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\CategoryService;
use App\Service\PostService;
use App\Service\TagService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class TagServiceTest.
 */
class TagServiceTest extends KernelTestCase
{
    /**
     * Tag service.
     *
     * @var TagService|object|null
     */
    private $tagService;

    /**
     * Tag repository.
     *
     * @var TagRepository|object|null
     */
    private $tagRepository;

    /**
     * Post service.
     *
     * @var PostService|object|null
     */
    private $postService;

    /**
     * Category service.
     *
     * @var CategoryService|object|null
     */
    private $categoryService;

    /**
     * Set up test.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$container;
        $this->tagRepository = $container->get(TagRepository::class);
        $this->tagService = $container->get(TagService::class);
        $this->postService = $container->get(PostService::class);
        $this->categoryService = $container->get(CategoryService::class);
    }

    /**
     * Test save.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testSave(): void
    {
        // given
        $expectedTag = new Tag();
        $expectedTag->setName('Test Tag');

        // when
        $this->tagService->save($expectedTag);
        $resultTag = $this->tagRepository->findOneById(
            $expectedTag->getId()
        );

        // then
        $this->assertEquals($expectedTag, $resultTag);
    }

    /**
     * Test delete.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDelete(): void
    {
        // given
        $expectedTag = new Tag();
        $expectedTag->setName('Test Tag');
        $this->tagRepository->save($expectedTag);
        $expectedId = $expectedTag->getId();

        // when
        $this->tagService->delete($expectedTag);
        $result = $this->tagRepository->findOneById($expectedId);

        // then
        $this->assertNull($result);
    }

    /**
     * Test find by id.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testFindById(): void
    {
        // given
        $expectedTag = new Tag();
        $expectedTag->setName('Test Tag');
        $this->tagRepository->save($expectedTag);

        // when
        $result = $this->tagService->findOneById($expectedTag->getId());

        // then
        $this->assertEquals($expectedTag->getId(), $result->getId());
    }

    /**
     * Test find by name.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testFindByName(): void
    {
        // given
        $expectedTag = new Tag();
        $expectedTag->setName('Test Tag');
        $this->tagRepository->save($expectedTag);

        // when
        $result = $this->tagService->findOneByName($expectedTag->getName());

        // then
        $this->assertEquals($expectedTag->getId(), $result->getId());
    }

    /**
     * Test pagination empty list.
     */
    public function testCreatePaginatedListEmptyList(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 0;

        // when
        $result = $this->tagService->createPaginatedList($page);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test pagination list.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreatePaginatedList(): void
    {
        // given
        $page = 1;
        $dataSetSize = 3;
        $expectedResultSize = 3;

        $category = new Category();
        $category->setName('Test Category');
        $this->categoryService->save($category);

        $passwordEncoder = self::$container->get('security.password_encoder');
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles([User::ROLE_USER]);
        $user->setPassword(
            $passwordEncoder->encodePassword(
                $user,
                'p@55w0rd'
            )
        );
        $userRepository = self::$container->get(UserRepository::class);
        $userRepository->save($user);

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('Test Post Content');
        $post->setCategory($category);
        $post->setAuthor($user);

        $counter = 0;
        while ($counter < $dataSetSize) {
            $tag = new Tag();
            $tag->setName('Test Tag #'.$counter);
            $this->tagRepository->save($tag);
            $post->addTag($tag);

            ++$counter;
        }
        $this->postService->save($post);

        // when
        $result = $this->tagService->createPaginatedList($page);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }
}
