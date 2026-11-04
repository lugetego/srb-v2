<?php

namespace App\Form;

use App\Entity\Referencia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentYear = (int) date('Y');
        $years = ['' => null] + array_combine(range($currentYear, 1990), range($currentYear, 1990));

        $types = ['All' => 'all'] + Referencia::TYPES;

        $builder
            ->add('title', TextType::class, [
                'label'    => 'Title',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'Title contains...'],
            ])
            ->add('author', TextType::class, [
                'label'    => 'Author',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'Author name contains...'],
            ])
            ->add('keyword', TextType::class, [
                'label'    => 'Keyword / Abstract',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'Keyword or abstract contains...'],
            ])
            ->add('type', ChoiceType::class, [
                'label'    => 'Type',
                'required' => false,
                'choices'  => $types,
                'attr'     => ['class' => 'form-select'],
            ])
            ->add('yearStart', ChoiceType::class, [
                'label'    => 'Year from',
                'required' => false,
                'choices'  => $years,
                'attr'     => ['class' => 'form-select'],
            ])
            ->add('yearEnd', ChoiceType::class, [
                'label'    => 'Year to',
                'required' => false,
                'choices'  => $years,
                'attr'     => ['class' => 'form-select'],
            ])
            ->add('allYears', CheckboxType::class, [
                'label'    => 'All years',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['csrf_protection' => false]);
    }

    public function getBlockPrefix(): string { return ''; }
}
