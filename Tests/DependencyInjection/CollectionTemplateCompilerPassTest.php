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

namespace Cmfcmf\Module\MediaModule\Tests\DependencyInjection;

use Cmfcmf\Module\MediaModule\DependencyInjection\Compiler\CollectionTemplateCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CollectionTemplateCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testNothingHappensIfCollectionDoesNotExist()
    {
        $containerMock = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['has'])
            ->getMock()
        ;
        $containerMock->expects($this->once())
            ->method('has')
            ->with($this->equalTo('cmfcmf_media_module.collection_template_collection'))
            ->willReturn(false)
        ;
        $containerMock->expects($this->never())
            ->method('findDefinition')
        ;

        $compilerPass = new CollectionTemplateCompilerPass();
        $compilerPass->process($containerMock);
    }

    public function testNothingHappensIfNoTaggedServices()
    {
        $containerMock = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['has', 'findDefinition', 'findTaggedServiceIds'])
            ->getMock()
        ;
        $containerMock->expects($this->once())
            ->method('has')
            ->with($this->equalTo('cmfcmf_media_module.collection_template_collection'))
            ->willReturn(true)
        ;
        $containerMock->expects($this->any())
            ->method('findDefinition')
            ->with($this->equalTo('cmfcmf_media_module.collection_template_collection'))
            ->willReturn(null)
        ;
        $containerMock->expects($this->any())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo('cmfcmf_media_module.collection_template'))
            ->willReturn([])
        ;

        $compilerPass = new CollectionTemplateCompilerPass();
        $compilerPass->process($containerMock);
    }

    public function testItWorksIfTaggedServicesAvailable()
    {
        $containerMock = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['has', 'findDefinition', 'findTaggedServiceIds'])
            ->getMock()
        ;
        $containerMock->expects($this->once())
            ->method('has')
            ->with($this->equalTo('cmfcmf_media_module.collection_template_collection'))
            ->willReturn(true)
        ;
        $definitionMock = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->setMethods(['addMethodCall'])
            ->getMock()
        ;
        $definitionMock->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive([
                'addCollectionTemplate',
                [new Reference('foo')],
            ], [
                'addCollectionTemplate',
                [new Reference('bar')]
            ])
        ;
        $containerMock->expects($this->any())
            ->method('findDefinition')
            ->with($this->equalTo('cmfcmf_media_module.collection_template_collection'))
            ->willReturn($definitionMock)
        ;
        $containerMock->expects($this->any())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo('cmfcmf_media_module.collection_template'))
            ->willReturn([
                'foo' => [],
                'bar' => ['a', 'b']
            ])
        ;

        $compilerPass = new CollectionTemplateCompilerPass();
        $compilerPass->process($containerMock);
    }
}
