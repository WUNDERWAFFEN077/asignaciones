<?php

namespace AJUserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('username')
        ->add('firstName')
        ->add('lastName')
        ->add('email',EmailType::class)
        ->add('password',PasswordType::class)
        ->add('role',ChoiceType::class,array('choices'=>array('Administrator'=>'ROLE_ADMIN','User'=>'ROLE_USER'),'placeholder'=>'Select a role'))
        ->add('isActive',CheckboxType::class)
        ->add('save',SubmitType::class, array(
            'label'=>'Save User',
            'attr'=>array(
                'class'=> 'form-submit btn btn-success'
                )))
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AJUserBundle\Entity\User'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ajuserbundle_user';
    }


}
