<?php

/*
 * This file is part of the MediaModule for Zikula.
 *
 * (c) Christian Flach <hi@christianflach.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cmfcmf\Module\MediaModule\Form\Collection;

use Cmfcmf\Module\MediaModule\CollectionTemplate\TemplateCollection;
use Cmfcmf\Module\MediaModule\Entity\Collection\CollectionEntity;
use Cmfcmf\Module\MediaModule\Entity\Collection\Repository\CollectionRepository;
use Cmfcmf\Module\MediaModule\Entity\Media\Repository\MediaRepository;
use Cmfcmf\Module\MediaModule\Feature\Checker;
use Cmfcmf\Module\MediaModule\Form\AbstractType;
use Cmfcmf\Module\MediaModule\Security\CollectionPermission\CollectionPermissionSecurityTree;
use Cmfcmf\Module\MediaModule\Security\SecurityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Parameter;
use Symfony\Component\Form\FormBuilderInterface;

class CollectionType extends AbstractType
{
    /**
     * @var CollectionEntity|null
     */
    private $parent;

    /**
     * @var TemplateCollection
     */
    private $templateCollection;

    /**
     * @var SecurityManager
     */
    private $securityManager;

    /**
     * @var Checker
     */
    private $checker;

    public function __construct(
        TemplateCollection $templateCollection,
        CollectionEntity $parent = null,
        SecurityManager $securityManager,
        Checker $checker
    ) {
        $this->parent = $parent;
        $this->templateCollection = $templateCollection;
        $this->securityManager = $securityManager;
        $this->checker = $checker;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $escapingStrategy = \ModUtil::getVar(
            'CmfcmfMediaModule',
            'descriptionEscapingStrategyForCollection');
        switch ($escapingStrategy) {
            case 'raw':
                $descriptionHelp = $this->translator->trans('You may use HTML.', [], 'cmfcmfmediamodule');
                break;
            case 'text':
                $descriptionHelp = $this->translator->trans('Only plaintext allowed.', [], 'cmfcmfmediamodule');
                break;
            case 'markdown':
                $descriptionHelp = $this->translator->trans('You may use MarkDown.', [], 'cmfcmfmediamodule');
                break;
            default:
                throw new \LogicException();
        }

        /** @var CollectionEntity $theCollection */
        $theCollection = $options['data'];
        $securityManager = $this->securityManager;

        $builder->add('title', 'text', [
            'label' => $this->translator->trans('Title', [], 'cmfcmfmediamodule')
        ]);
        // If enabled, breaks slug generation of children when the slug is changed.
        //if (\ModUtil::getVar('CmfcmfMediaModule', 'slugEditable')) {
        //    $builder
        //        ->add('slug', 'text', [
        //            'label' => $this->translator->trans('Slug', [], 'cmfcmfmediamodule'),
        //            'required'=> false,
        //            'attr' => [
        //                'placeholder' => $this->translator->trans('Leave empty to autogenerate', [], 'cmfcmfmediamodule')
        //            ]
        //        ])
        //    ;
        //}

        if ($this->checker->isEnabled('collectionDescription')) {
            $builder->add(
                'description',
                'textarea',
                [
                    'label' => $this->translator->trans('Description', [], 'cmfcmfmediamodule'),
                    'required' => false,
                    'attr' => [
                        'help' => $descriptionHelp
                    ]
            ]);
        }
        if ($this->checker->isEnabled('collectionCategories')) {
            $builder->add('categoryAssignments', 'Zikula\CategoriesModule\Form\Type\CategoriesType', [
                'required' => false,
                'multiple' => true,
                'module' => 'CmfcmfMediaModule',
                'entity' => 'CollectionEntity',
                'entityCategoryClass' => 'Cmfcmf\Module\MediaModule\Entity\Collection\CollectionCategoryAssignmentEntity',
            ]);
        }
        if ($this->checker->isEnabled('collectionTemplate')) {
            $builder->add('defaultTemplate', 'cmfcmfmediamodule_collectiontemplate');
        }
        $builder->add('parent', 'entity', [
            'class' => 'Cmfcmf\Module\MediaModule\Entity\Collection\CollectionEntity',
            'required' => false,
            'label' => $this->translator->trans('Parent', [], 'cmfcmfmediamodule'),
            'placeholder' => $this->translator->trans('No parent', [], 'cmfcmfmediamodule'),
            'query_builder' => function (EntityRepository $er) use (
                $theCollection,
                $securityManager
            ) {
                /** @var CollectionRepository $er */
                $qb = $securityManager->getCollectionsWithAccessQueryBuilder(
                    CollectionPermissionSecurityTree::PERM_LEVEL_ADD_SUB_COLLECTIONS
                );
                $qb->orderBy('c.root', 'ASC')
                    ->addOrderBy('c.lft', 'ASC');
                if ($theCollection->getId() != null) {
                    // The collection is currently edited. Make sure it's not placed into
                    // itself or one of it's children.
                    $childrenQuery = $er->getChildrenQuery($theCollection);
                    $qb
                        ->andWhere(
                            $qb->expr()->notIn(
                                'c.id',
                                $childrenQuery->getDQL()
                            )
                        )
                        ->andWhere($qb->expr()->neq('c.id', ':id'))
                        ->setParameter('id', $theCollection->getId())
                    ;
                    $childrenQuery->getParameters()->forAll(function ($key, Parameter $parameter) use ($qb) {
                        $qb->setParameter($parameter->getName(), $parameter->getValue());
                    });
                }

                return $qb;
            },
            'data' => $this->parent,
            'property' => 'indentedTitle',
        ]);
        if ($this->checker->isEnabled('collectionWatermarks')) {
            $builder->add('watermark', 'entity', [
                'class' => 'CmfcmfMediaModule:Watermark\AbstractWatermarkEntity',
                'required' => false,
                'label' => $this->translator->trans('Watermark', [], 'cmfcmfmediamodule'),
                'data' => $theCollection->getId() !== null ? $theCollection->getWatermark() :
                    (isset($this->parent) ? $this->parent->getWatermark() : null),
                'placeholder' => $this->translator->trans('No watermark', [], 'cmfcmfmediamodule'),
                'property' => 'title',
            ]);
        }
        if ($this->checker->isEnabled('collectionPrimaryMedium')) {
            $builder->add('primaryMedium', 'entity', [
                'class' => 'CmfcmfMediaModule:Media\AbstractMediaEntity',
                'required' => false,
                'label' => $this->translator->trans('Primary medium', [], 'cmfcmfmediamodule'),
                'placeholder' => $this->translator->trans('First medium of collection', [], 'cmfcmfmediamodule'),
                'disabled' => $theCollection->getId() == null,
                'property' => 'title',
                'query_builder' => function (EntityRepository $er) use ($theCollection) {
                    /** @var MediaRepository $er */
                    $qb = $er->createQueryBuilder('m');
                    $qb->where($qb->expr()->eq('m.collection', ':collection'))
                        ->setParameter('collection', $theCollection->getId());

                    return $qb;
                },
                'attr' => [
                    'help' => $this->translator->trans('The primary medium is used as collection thumbnail. It must be part of the collection.', [], 'cmfcmfmediamodule')
                ]
            ]);
        }
    }
}
