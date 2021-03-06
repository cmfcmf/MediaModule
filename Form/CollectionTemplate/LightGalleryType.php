<?php

declare(strict_types=1);

/*
 * This file is part of the MediaModule for Zikula.
 *
 * (c) Christian Flach <hi@christianflach.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cmfcmf\Module\MediaModule\Form\CollectionTemplate;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class LightGalleryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('thumbWidth', NumberType::class, [
            'label' => 'Thumbnail width'
        ])
        ->add('thumbHeight', NumberType::class, [
            'label' => 'Thumbnail height'
        ])
        ->add('thumbMode', ChoiceType::class, [
            'label' => 'Thumbnail mode',
            'choices' => [
                'inset' => 'inset',
                'outbound' => 'outbound'
            ],
        ])
        ->add('showTitleBelowThumbs', CheckboxType::class, [
            'label' => 'Show the image titles below thumbnails.',
            'required' => false
        ])
        ->add('showAttributionBelowThumbs', CheckboxType::class, [
            'label' => 'Show the image attributions below thumbnails.',
            'required' => false
        ]);
    }
}
