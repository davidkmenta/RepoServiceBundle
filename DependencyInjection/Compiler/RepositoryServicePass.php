<?php

namespace DavidKmenta\RepoServiceBundle\DependencyInjection\Compiler;

use DavidKmenta\RepoServiceBundle\Doctrine\ORM\Manager\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RepositoryServicePass implements CompilerPassInterface
{
    const DOCTRINE_REPOSITORY_TAG = 'doctrine.repository';
    const CLASS_METADATA_FACTORY_ID = 'doctrine.class_metadata_factory';

    /**
     * @var ContainerBuilder
     */
    private $container;

    public function process(ContainerBuilder $container)
    {
        $this->container = $container;

        if (!($this->container->has($this->getDefaultEntityManagerId()))) {
            return;
        }

        $taggedRepositories = $this->container->findTaggedServiceIds(self::DOCTRINE_REPOSITORY_TAG);

        if (count($taggedRepositories)) {
            $this->defineEntityManager($taggedRepositories);
            $this->defineClassMetadataFactory();
        }

        foreach ($taggedRepositories as $repositoryId => $tags) {
            $this->processRepositoryService($repositoryId);
        }
    }

    /**
     * @param array $taggedRepositories
     */
    private function defineEntityManager(array $taggedRepositories)
    {
        $this->container->setParameter('doctrine.orm.entity_manager.class', EntityManager::class);

        $repositories = [];
        foreach ($taggedRepositories as $repositoryId => $tags) {
            $repositories[$repositoryId] = new Reference($repositoryId);
        }

        $entityManagerDefinition = $this->container->getDefinition($this->getDefaultEntityManagerId());
        $entityManagerDefinition
            ->setClass(EntityManager::class)
            ->addMethodCall('setRepositories', [$repositories]);
    }

    private function defineClassMetadataFactory()
    {
        $classMetadataFactoryDefinition = $this->container->register(
            self::CLASS_METADATA_FACTORY_ID,
            ClassMetadataFactory::class
        );

        $classMetadataFactoryDefinition
            ->addMethodCall(
                'setEntityManager',
                [new Reference($this->getDefaultEntityManagerId())]
            )
            ->setPublic(false);
    }

    /**
     * @param string $repositoryServiceId
     */
    private function processRepositoryService($repositoryServiceId)
    {
        $repositoryDefinition = $this->container->getDefinition($repositoryServiceId);

        $repositoryDefinition
            ->addMethodCall(
                'setEntityManager',
                [new Reference($this->getDefaultEntityManagerId())]
            )
            ->addMethodCall(
                'setClassMetadataFactory',
                [new Reference(self::CLASS_METADATA_FACTORY_ID)]
            )
            ->setLazy(true);
    }

    /**
     * @return string
     */
    private function getDefaultEntityManagerId()
    {
        if ($this->container->hasParameter('doctrine.default_entity_manager')) {
            return sprintf(
                'doctrine.orm.%s_entity_manager',
                $this->container->getParameter('doctrine.default_entity_manager')
            );
        }

        return 'doctrine.orm.default_entity_manager';
    }
}
