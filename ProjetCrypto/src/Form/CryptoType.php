<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CryptoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, array('attr' => array('style' => 'width: 200px'),'required'   => false,))
            ->add('price1',NumberType::class, array('attr' => array('style' => 'width: 125px'),'required'   => false,))
            ->add('price2',NumberType::class, array('attr' => array('style' => 'width: 125px'),'required'   => false,))
            ->add('marketcap1',NumberType::class, array('attr' => array('style' => 'width: 125px'),'required'   => false,))
            ->add('marketcap2',NumberType::class, array('attr' => array('style' => 'width: 125px'),'required'   => false,))
            ->add('category',TextType::class, array('attr' => array('style' => 'width: 200px'),'required'   => false,))
            ->add('dateCreation1',DateType::class,
                array('attr' => array('style' => 'width: 125px'),'required'   => false,'widget' => 'single_text')
            )
            ->add('dateCreation2',DateType::class,
                array('attr' => array('style' => 'width: 125px'),'required'   => false,'widget' => 'single_text')
            )
            ->add('favoris1',NumberType::class, array('attr' => array('style' => 'width: 125px'),'required'   => false,))
            ->add('favoris2',NumberType::class, array('attr' => array('style' => 'width: 125px'),'required'   => false,))
            ->add('followers1',NumberType::class, array('attr' => array('style' => 'width: 125px'),'required'   => false,))
            ->add('followers2',NumberType::class, array('attr' => array('style' => 'width: 125px'),'required'   => false,))

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }

}
