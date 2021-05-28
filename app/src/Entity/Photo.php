<?php
/**
 * Photo.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Photo.
 *
 * @ORM\Entity(repositoryClass="App\Repository\PhotoRepository")
 * @ORM\Table(
 *     name="photos",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="UQ_filename_1",
 *              columns={"filename"},
 *          ),
 *     },
 * )
 *
 * @UniqueEntity(
 *      fields={"filename"},
 * )
 */
class Photo
{
    /**
     * Id.
     *
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Post.
     *
     * @var Post
     *
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\Post",
     *     inversedBy="photos",
     * )
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\Type(type="App\Entity\Post")
     */
    private $post;

    /**
     * Filename.
     *
     * @var string
     *
     * @ORM\Column(
     *     type="string",
     *     length=128,
     * )
     *
     * @Assert\Type(type="string")
     */
    private $filename;

    /**
     * Getter for id.
     *
     * @return int|null Id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Getter for post.
     *
     * @return Post|null Post
     */
    public function getPost(): ?Post
    {
        return $this->post;
    }

    /**
     * Setter for post.
     *
     * @param Post $post Post
     */
    public function setPost(Post $post): void
    {
        $this->post = $post;
    }

    /**
     * Getter for filename.
     *
     * @return string Filename
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Setter for filename.
     *
     * @param string $filename Filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }
}
