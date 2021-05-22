<?php
/**
 * Post type.
 */

namespace App\Form;

use App\Entity\Category;
use App\Entity\Post;
use App\Form\DataTransformer\TagsDataTransformer;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PostType.
 */
class PostType extends AbstractType
{
    /**
     * Tags data transformer.
     *
     * @var TagsDataTransformer
     */
    private $tagsDataTransformer;

    /**
     * PostType constructor.
     *
     * @param TagsDataTransformer $tagsDataTransformer Tags data transformer
     */
    public function __construct(TagsDataTransformer $tagsDataTransformer)
    {
        $this->tagsDataTransformer = $tagsDataTransformer;
    }

    /**
     * Builds the form.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the form.
     *
     * @see FormTypeExtensionInterface::buildForm()
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'title',
            TextType::class,
            [
                'label' => 'label_title',
                'required' => true,
                'attr' => ['max_length' => 64],
            ]
        );
        $builder->add(
            'content',
            CKEditorType::class,
            [
                'label' => 'label_content',
                'required' => true,
                'attr' => ['max_length' => 1000],
            ]
        );
        $builder->add(
            'category',
            EntityType::class,
            [
                'class' => Category::class,
                'choice_label' => function ($category) {
                    return $category->getName();
                },
                'label' => 'label_category',
                'required' => true,
            ]
        );
        $builder->add(
            'tags',
            TextType::class,
            [
                'label' => 'label_tags',
                'required' => false,
                'attr' => ['max_length' => 128],
            ]
        );

        $builder->get('tags')->addModelTransformer(
            $this->tagsDataTransformer
        );

        $builder->add(
            'published',
            CheckboxType::class,
            [
                'label' => 'label_publication',
                'required' => false,
            ]
        );
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Post::class]);
    }

    /**
     * Returns the prefix of the template block name for this type.
     *
     * The block prefix defaults to the underscored short class name with
     * the "Type" suffix removed (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix(): string
    {
        return 'post';
    }
}