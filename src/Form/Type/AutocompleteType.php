<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSymfonyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AutocompleteType extends AbstractType
{
    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['remote_url'] = $options['remote_url'];
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['min_length'] = $options['min_length'];
        $view->vars['limit'] = $options['limit'];
        $view->vars['initial_text'] = $options['initial_text'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'remote_url' => null,
            'placeholder' => 'Rechercher…',
            'min_length' => 2,
            'limit' => 20,
            'initial_text' => null,
        ]);

        $resolver->setAllowedTypes('remote_url', ['string']);
        $resolver->setAllowedTypes('placeholder', ['string']);
        $resolver->setAllowedTypes('min_length', ['int']);
        $resolver->setAllowedTypes('limit', ['int']);
        $resolver->setAllowedTypes('initial_text', ['null', 'string']);
    }

    public function getBlockPrefix(): string
    {
        return 'ddd_symfony_autocomplete';
    }

    // public function transform(mixed $data): mixed
    // {
    //     // Model data should not be transformed
    //     return $data;
    // }

    // public function reverseTransform(mixed $data): mixed
    // {
    //     return $data ?? '';
    // }
}
