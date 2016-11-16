<?php

namespace AJUserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class TaskType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('title')
        ->add('description')
        ->add('user',EntityType::class,array(
            'class'=>'AJUserBundle:User',
            'query_builder'=>function(EntityRepository $er){
                return $er->createQueryBuilder('u')
                    ->where('u.role = :only')
                    ->setParameter('only','ROLE_USER');
            },
            'choice_label'=>'getFullName'
            ))   
        
        ->add('save',SubmitType::class,array('label'=>'Save task'))
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AJUserBundle\Entity\Task'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ajuserbundle_task';
    }


}
