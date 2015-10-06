<?php

namespace Cmfcmf\Module\MediaModule\Form\Media;

use Symfony\Component\Form\FormBuilderInterface;

class DeezerType extends WebType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['hiddenFields'] = [
            'title', 'url', 'license'
        ];
        parent::buildForm($builder, $options);

        $builder
            ->add('musicType', 'hidden')
            ->add('musicId', 'hidden')
            ->add('showPlaylist', 'checkbox', [
                'label' => $this->__('Show playlist'),
                'required' => false
            ])
        ;
    }
}
