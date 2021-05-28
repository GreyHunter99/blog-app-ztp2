<?php
/**
 * Post fixtures.
 */

namespace App\DataFixtures;

use App\Entity\Post;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Class PostFixtures.
 */
class PostFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    /**
     * Load data.
     *
     * @param ObjectManager $manager Persistence object manager
     */
    public function loadData(ObjectManager $manager): void
    {
        $this->createMany(50, 'posts', function ($i) {
            $post = new Post();
            $post->setTitle($this->faker->sentence);
            $post->setCreatedAt($this->faker->dateTimeBetween('-100 days', '-1 days'));
            $post->setUpdatedAt($this->faker->dateTimeBetween('-100 days', '-1 days'));
            $post->setContent($this->faker->text);
            $post->setPublished($this->faker->boolean);
            $post->setCategory($this->getRandomReference('categories'));
            $tags = $this->getRandomReferences(
                'tags',
                $this->faker->numberBetween(0, 5)
            );

            foreach ($tags as $tag) {
                $post->addTag($tag);
            }

            $post->setAuthor($this->getRandomReference('users'));

            return $post;
        });

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return array Array of dependencies
     */
    public function getDependencies(): array
    {
        return [CategoryFixtures::class, TagFixtures::class, UserFixtures::class];
    }
}
