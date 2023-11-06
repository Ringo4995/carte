<?php

namespace App\Form;

use App\Entity\Card;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image as ConstraintsImage;

class CarteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Image', FileType::class,[
                'attr'=>[
                    'accept' => "image/*"
                ],
                'constraints' => [
                    new ConstraintsImage()
                ],
                'data_class' => Card::class, // Replace YourFormData with your form data class
            ])
            ->add('Titre')
            ->add('description')
            ->add('envoyer', SubmitType::class);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class, // Replace YourFormData with your form data class
        ]);
    }
}
