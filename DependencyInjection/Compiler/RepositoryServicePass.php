<?php

namespace DavidKmenta\RepoServiceBundle\DependencyInjection\Compiler;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RepositoryServicePass implements CompilerPassInterface
{
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

        $taggedRepositories = $this->container->findTaggedServiceIds('doctrine.repository');

        if (count($taggedRepositories)) {
            $this->defineClassMetadataFactory();
        }

        foreach ($taggedRepositories as $repositoryId => $tags) {
            $this->processRepositoryService($repositoryId);
        }
    }

    private function defineClassMetadataFactory()
    {
        $classMetadataFactoryDefinition = $this->container->setDefinition(
            'doctrine.class_metadata_factory',
            new Definition(ClassMetadataFactory::class)
        );

        $classMetadataFactoryDefinition
            ->addMethodCall(
                'setEntityManager',
                [$this->container->getDefinition($this->getDefaultEntityManagerId())]
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
                [$this->container->getDefinition($this->getDefaultEntityManagerId())]
            )
            ->addMethodCall(
                'setClassMetadataFactory',
                [$this->container->getDefinition('doctrine.class_metadata_factory')]
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
