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

namespace Cmfcmf\Module\MediaModule\Form\Collection;

use Cmfcmf\Module\MediaModule\Entity\Collection\CollectionEntity;
use Cmfcmf\Module\MediaModule\Entity\Collection\Repository\CollectionRepository;
use Cmfcmf\Module\MediaModule\Form\CollectionTemplate\TemplateType;
use Cmfcmf\Module\MediaModule\Security\CollectionPermission\CollectionPermissionSecurityTree;
use Cmfcmf\Module\MediaModule\Security\SecurityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class CollectionBlockType extends AbstractType
{
    /**
     * @var CollectionRepository
     */
    private $collectionRepository;

    /**
     * @var SecurityManager
     */
    private $securityManager;

    public function __construct(
        SecurityManager $securityManager,
        EntityManagerInterface $em
    ) {
        $this->securityManager = $securityManager;
        $this->collectionRepository = $em->getRepository('CmfcmfMediaModule:Collection\CollectionEntity');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $securityManager = $this->securityManager;
        $collectionRepository = $this->collectionRepository;

        $collectionOptions = [
            'required' => true,
            'label' => 'Collection',
            'class' => CollectionEntity::class,
            'query_builder' => function (EntityRepository $er) use ($securityManager) {
                /** @var CollectionRepository $er */
                $qb = $securityManager->getCollectionsWithAccessQueryBuilder(
                    CollectionPermissionSecurityTree::PERM_LEVEL_OVERVIEW
                );
                $qb->orderBy('c.root', 'ASC')
                    ->addOrderBy('c.lft', 'ASC');

                return $qb;
            },
            'placeholder' => 'Select collection',
            'choice_label' => 'indentedTitle',
            'multiple' => false
        ];
        $builder
            ->add('id', EntityType::class, $collectionOptions)
            ->add('showHooks', CheckboxType::class, [
                'label' => 'Show hooks',
                'required' => false,
                'disabled' => true
            ])
            ->add('template', TemplateType::class, [
                'label' => 'Display',
            ])
            ->add('showChildCollections', CheckboxType::class, [
                'label' => 'Show child collections',
                'required' => false
            ])
            ->add('showEditAndDownloadLinks', CheckboxType::class, [
                'label' => 'Show edit and download links',
                'required' => false
            ])
            ->addModelTransformer(new CallbackTransformer(
                function ($data) use ($collectionRepository) {
                    $data['id'] = isset($data['id']) ? $collectionRepository->findOneBy(['id' => $data['id']]) : null;

                    return $data;
                },
                function ($data) {
                    $data['id'] = isset($data['id']) ? $data['id']->getId() : null;

                    return $data;
                }
            ))
        ;
    }

    public function getBlockPrefix()
    {
        return 'cmfcmfmediamodule_collectionblock';
    }
}
