<?php

namespace DavidKmenta\RepoServiceBundle\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

abstract class EntityRepository extends BaseEntityRepository
{
    public function __construct()
    {
        // disabling parent constructor
    }

    /**
     * @param EntityManager $entityManager
     */
    final public function setEntityManager(EntityManager $entityManager)
    {
        $this->_em = $entityManager;
    }

    /**
     * @param ClassMetadataFactory $classMetadataFactory
     */
    final public function setClassMetadataFactory(ClassMetadataFactory $classMetadataFactory)
    {
        $classMetadata = $classMetadataFactory->getMetadataFor($this->getMappedEntityName());

        $this->_entityName = $classMetadata->getName();
        $this->_class = $classMetadata;
    }

    /**
     * @return string
     */
    abstract protected function getMappedEntityName();
}
