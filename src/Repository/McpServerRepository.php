<?php

namespace App\Repository;

use App\Entity\McpServer;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<McpServer>
 */
class McpServerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, McpServer::class);
    }

    /** @return McpServer[] */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.project = :project')
            ->setParameter('project', $project->getId(), 'uuid')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByAccessToken(string $token): ?McpServer
    {
        return $this->findOneBy(['accessToken' => $token]);
    }
}
