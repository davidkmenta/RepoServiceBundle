<?php

namespace DavidKmenta\RepoServiceBundle\Tests\DependencyInjection\Compiler;

use DavidKmenta\RepoServiceBundle\Doctrine\ORM\Manager\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use DavidKmenta\RepoServiceBundle\DependencyInjection\Compiler\RepositoryServicePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RepositoryServicePassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RepositoryServicePass
     */
    private $doctrineRepositoryPass;

    /**
     * @var ContainerBuilder
     */
    private $containerBuilder;

    /**
     * @var Definition
     */
    private $defaultEntityManagerDefinition;

    /**
     * @var Definition
     */
    private $repositoryServiceOne;

    /**
     * @var Definition
     */
    private $repositoryServiceTwo;

    protected function setUp()
    {
        $this->containerBuilder = new ContainerBuilder();

        $this->containerBuilder
            ->setDefinition('repository.service.one', $this->repositoryServiceOne = new Definition());
        $this->containerBuilder
            ->setDefinition('repository.service.two', $this->repositoryServiceTwo = new Definition());
        $this->containerBuilder
            ->setDefinition('ordinary.service', new Definition());

        $this->doctrineRepositoryPass = new RepositoryServicePass();
    }

    public function testShouldDoNothingIfDoctrineEntityManagerDoesNotExists()
    {
        $this->doctrineRepositoryPass->process($this->containerBuilder);

        $this->assertEmpty($this->containerBuilder->getDefinition('repository.service.one')->getMethodCalls());
        $this->assertEmpty($this->containerBuilder->getDefinition('repository.service.two')->getMethodCalls());
    }

    public function testShouldNotDefineClassMetadataFactoryIfNoTaggedServicesExist()
    {
        $this->setDefaultEntityManagerToContainer();

        $this->doctrineRepositoryPass->process($this->containerBuilder);

        $this->assertFalse($this->containerBuilder->has('doctrine.class_metadata_factory'));
    }

    public function testShouldOverrideDefaultEntityManangerAndSetRepositoriesToIt()
    {
        $this->setDefaultEntityManagerToContainer();

        $this->repositoryServiceOne->addTag('doctrine.repository');
        $this->repositoryServiceTwo->addTag('doctrine.repository');

        $this->doctrineRepositoryPass->process($this->containerBuilder);

        $entityManagerDefinition = $this->containerBuilder->getDefinition('doctrine.orm.default_entity_manager');

        $this->assertSame(
            EntityManager::class,
            $this->containerBuilder->getParameter('doctrine.orm.entity_manager.class')
        );
        $this->assertSame(EntityManager::class, $entityManagerDefinition->getClass());
        $this->assertEquals(
            [
                ['setRepositories', [
                    [
                        'repository.service.one' => new Reference('repository.service.one'),
                        'repository.service.two' => new Reference('repository.service.two'),
                    ]
                ]],
            ],
            $entityManagerDefinition->getMethodCalls()
        );
    }

    public function testShouldDefineClassMetadataFactoryIfAnyTaggedServicesExist()
    {
        $this->setDefaultEntityManagerToContainer();

        $this->repositoryServiceOne->addTag('doctrine.repository');
        $this->repositoryServiceTwo->addTag('doctrine.repository');

        $this->doctrineRepositoryPass->process($this->containerBuilder);

        $this->assertTrue($this->containerBuilder->has('doctrine.class_metadata_factory'));

        $classMetadataFactoryDefinition = $this->containerBuilder->getDefinition('doctrine.class_metadata_factory');

        $this->assertSame(ClassMetadataFactory::class, $classMetadataFactoryDefinition->getClass());
        $this->assertFalse($classMetadataFactoryDefinition->isPublic());
        $this->assertEquals(
            [
                ['setEntityManager', [new Reference('doctrine.orm.default_entity_manager')]],
            ],
            $classMetadataFactoryDefinition->getMethodCalls()
        );
    }

    public function testShouldInjectEntityManagerAndClassMetadataFactoryToTaggedServices()
    {
        $this->setDefaultEntityManagerToContainer();

        $this->repositoryServiceOne->addTag('doctrine.repository');
        $this->repositoryServiceTwo->addTag('doctrine.repository');

        $this->doctrineRepositoryPass->process($this->containerBuilder);

        $repositoryServiceOne = $this->containerBuilder->getDefinition('repository.service.one');
        $repositoryServiceTwo = $this->containerBuilder->getDefinition('repository.service.two');
        $ordinaryService = $this->containerBuilder->getDefinition('ordinary.service');

        $this->assertTrue($repositoryServiceOne->isLazy());
        $this->assertTrue($repositoryServiceTwo->isLazy());
        $this->assertFalse($ordinaryService->isLazy());

        $methodCalls = [
            ['setEntityManager', [new Reference('doctrine.orm.default_entity_manager')]],
            ['setClassMetadataFactory', [new Reference('doctrine.class_metadata_factory')]],
        ];

        $this->assertEquals($methodCalls, $repositoryServiceOne->getMethodCalls());
        $this->assertEquals($methodCalls, $repositoryServiceTwo->getMethodCalls());
        $this->assertEmpty($ordinaryService->getMethodCalls());
    }

    public function testShouldAssembleAndUseCustomEntityManagerServiceId()
    {
        $containerMock = $this->createMock(ContainerBuilder::class);
        $containerMock
            ->expects($this->once())
            ->method('hasParameter')
            ->with('doctrine.default_entity_manager')
            ->willReturn(true);
        $containerMock
            ->expects($this->once())
            ->method('getParameter')
            ->with('doctrine.default_entity_manager')
            ->willReturn('CUSTOM');
        $containerMock
            ->expects($this->once())
            ->method('has')
            ->with('doctrine.orm.CUSTOM_entity_manager')
            ->willReturn(false);

        $this->doctrineRepositoryPass->process($containerMock);
    }

    private function setDefaultEntityManagerToContainer()
    {
        $this->containerBuilder
            ->setDefinition(
                'doctrine.orm.default_entity_manager',
                $this->defaultEntityManagerDefinition = new Definition(EntityManager::class)
            );
    }
}
