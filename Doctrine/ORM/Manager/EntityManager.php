<?php

namespace DavidKmenta\RepoServiceBundle\Doctrine\ORM\Manager;

use DavidKmenta\RepoServiceBundle\Repository\EntityRepository;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as BaseEntityManager;
use Doctrine\ORM\ORMException;

class EntityManager extends BaseEntityManager
{
    /**
     * @var EntityRepository[]
     */
    private $repositories;

    /**
     * @param mixed $conn
     * @param Configuration $config
     * @param EventManager|null $eventManager
     *
     * @return static
     * @throws DBALException
     * @throws ORMException
     */
    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        if (!$config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        switch (true) {
            case is_array($conn):
                $conn = DriverManager::getConnection(
                    $conn, $config, ($eventManager ?: new EventManager())
                );
                break;

            case $conn instanceof Connection:
                if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                    throw ORMException::mismatchedEventManager();
                }
                break;

            default:
                throw new \InvalidArgumentException('Invalid argument: ' . $conn);
        }

        return new static($conn, $config, $conn->getEventManager());
    }

    public function getRepository($entityName)
    {
        foreach ($this->repositories as $repository) {
            if ($repository->getMappedEntityName() === $entityName) {
                return $repository;
            }
        }

        return parent::getRepository($entityName);
    }

    /**
     * @param EntityRepository[] $repositories
     */
    public function setRepositories(array $repositories)
    {
        $this->repositories = $repositories;
    }

    /**
     * @param string $repositoryServiceId
     * @param EntityRepository $repositoryService
     */
    public function addRepositoryService($repositoryServiceId, EntityRepository $repositoryService)
    {
        $this->repositories[$repositoryServiceId] = $repositoryService;
    }
}
