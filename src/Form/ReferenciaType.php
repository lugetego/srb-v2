<?php

namespace App\Form;

use App\Entity\Author;
use App\Entity\Referencia;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ReferenciaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentYear = (int) date('Y');
        $years = array_combine(
            range($currentYear, 1990),
            range($currentYear, 1990)
        );

        $builder
            ->add('type', ChoiceType::class, [
                'label'   => 'Type',
                'choices' => Referencia::TYPES,
                'attr'    => ['class' => 'form-select', 'id' => 'ref_type'],
            ])
            ->add('title', TextareaType::class, [
                'label' => 'Title',
                'attr'  => ['rows' => 3, 'class' => 'form-control'],
            ])
            ->add('author', TextareaType::class, [
                'label'    => 'Authors (text)',
                'required' => false,
                'attr'     => ['rows' => 2, 'class' => 'form-control', 'placeholder' => 'Last, First; Last, First'],
            ])
            ->add('authors', EntityType::class, [
                'class'        => Author::class,
                'label'        => 'Institutional authors',
                'required'     => false,
                'multiple'     => true,
                'expanded'     => false,
                'choice_label' => 'name',
                'attr'         => ['class' => 'form-select', 'size' => 6],
            ])
            ->add('editor', TextType::class, ['label' => 'Editor', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('corporateAuthor', TextType::class, ['label' => 'Corporate author', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('yearPub', ChoiceType::class, [
                'label'    => 'Year published',
                'required' => false,
                'choices'  => ['' => null] + $years,
                'attr'     => ['class' => 'form-select'],
            ])
            ->add('yearPreprint', ChoiceType::class, [
                'label'    => 'Preprint year',
                'required' => false,
                'choices'  => ['' => null] + $years,
                'data'     => (string) $currentYear,
                'attr'     => ['class' => 'form-select'],
            ])
            ->add('journal', TextType::class, ['label' => 'Journal', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('volume', TextType::class, ['label' => 'Volume', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('issue', TextType::class, ['label' => 'Issue', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('pages', TextType::class, ['label' => 'Pages', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('publisher', TextType::class, ['label' => 'Publisher', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('placePub', TextType::class, ['label' => 'Place of publication', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('publication', TextType::class, ['label' => 'Publication', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('bookTitle', TextType::class, ['label' => 'Book title', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('school', TextType::class, ['label' => 'School / Institution', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('advisor', TextType::class, ['label' => 'Advisor', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('thesisType', ChoiceType::class, [
                'label'    => 'Thesis type',
                'required' => false,
                'choices'  => ['' => null, 'Licenciatura' => 'Licenciatura', 'Maestría' => 'Maestría', 'Doctorado' => 'Doctorado'],
                'attr'     => ['class' => 'form-select'],
            ])
            ->add('address', TextType::class, ['label' => 'Address', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('keywords', TextareaType::class, ['label' => 'Keywords', 'required' => false, 'attr' => ['rows' => 2, 'class' => 'form-control']])
            ->add('abst', TextareaType::class, ['label' => 'Abstract', 'required' => false, 'attr' => ['rows' => 4, 'class' => 'form-control']])
            ->add('issn', TextType::class, ['label' => 'ISSN', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('isbn', TextType::class, ['label' => 'ISBN', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('doi', TextType::class, ['label' => 'DOI', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('url', TextType::class, ['label' => 'URL', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('arxiv', TextType::class, ['label' => 'arXiv', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('mathscinet', TextType::class, ['label' => 'MathSciNet', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('zmath', TextType::class, ['label' => 'zbMATH', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('inspires', TextType::class, ['label' => 'INSPIRE', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('reportNumber', TextType::class, ['label' => 'Report number', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('msc', TextType::class, ['label' => 'MSC', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('mrNumber', TextType::class, ['label' => 'MR number', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('medium', TextType::class, ['label' => 'Medium', 'required' => false, 'attr' => ['class' => 'form-control']])
            ->add('notas', TextareaType::class, ['label' => 'Notes', 'required' => false, 'attr' => ['rows' => 3, 'class' => 'form-control']])
            ->add('revision', CheckboxType::class, ['label' => 'Reviewed', 'required' => false, 'attr' => ['class' => 'form-check-input']])
            ->add('uploadedFile', FileType::class, [
                'label'    => 'Attach PDF',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new File(['maxSize' => '20M', 'mimeTypes' => ['application/pdf'], 'mimeTypesMessage' => 'Please upload a PDF file.']),
                ],
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Referencia::class,
        ]);
    }
}
