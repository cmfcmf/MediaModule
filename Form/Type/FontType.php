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

namespace Cmfcmf\Module\MediaModule\Form\Type;

use Cmfcmf\Module\MediaModule\Font\FontCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Translation\Extractor\Annotation\Ignore;

/**
 * Displays a font selector.
 */
class FontType extends AbstractType
{
    /**
     * @var FontCollection
     */
    private $fontCollection;

    public function __construct(FontCollection $fontCollection)
    {
        $this->fontCollection = $fontCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'expanded' => true,
            'choices' => /** @Ignore */ $this->fontCollection->getFontsForForm()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fontUrl'] = $this->fontCollection->getFontUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'cmfcmfmediamodule_font_choice';
    }
}
