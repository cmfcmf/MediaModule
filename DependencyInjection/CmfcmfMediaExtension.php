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

namespace Cmfcmf\Module\MediaModule\DependencyInjection;

use Cmfcmf\Module\MediaModule\CollectionTemplate\TemplateInterface;
use Cmfcmf\Module\MediaModule\Font\FontLoaderInterface;
use Cmfcmf\Module\MediaModule\Importer\ImporterInterface;
use Cmfcmf\Module\MediaModule\MediaType\MediaTypeInterface;
use Cmfcmf\Module\MediaModule\Security\CollectionPermission\CollectionPermissionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Loads the services.yml file.
 */
class CmfcmfMediaExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->registerForAutoconfiguration(CollectionPermissionInterface::class)
            ->addTag('cmfcmf_media_module.collection_permission')
//             ->setPublic(true)
        ;

        $container->registerForAutoconfiguration(TemplateInterface::class)
            ->addTag('cmfcmf_media_module.collection_template')
//             ->setPublic(true)
        ;

        $container->registerForAutoconfiguration(MediaTypeInterface::class)
            ->addTag('cmfcmf_media_module.media_type')
//             ->setPublic(true)
        ;

        $container->registerForAutoconfiguration(ImporterInterface::class)
            ->addTag('cmfcmf_media_module.importer')
//             ->setPublic(true)
        ;

        $container->registerForAutoconfiguration(FontLoaderInterface::class)
            ->addTag('cmfcmf_media_module.font_loader')
//             ->setPublic(true)
        ;
    }
}
