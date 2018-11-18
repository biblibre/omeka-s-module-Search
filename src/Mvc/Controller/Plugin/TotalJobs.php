<?php
namespace Search\Mvc\Controller\Plugin;

use Doctrine\ORM\EntityManager;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class TotalJobs extends AbstractPlugin
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get the number of jobs acoording to their status.
     *
     * @param string $class Job class
     * @param bool|array $statusesOrProcessing If true, running jobs. If false, ended jobs.
     * If array, list of statuses to check.
     * @param int $ownerId
     * @return int
     */
    public function __invoke($class = null, $statusesOrProcessing = [], $ownerId = null)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $result = $qb
            ->select('COUNT(job.id)')
            ->from(\Omeka\Entity\Job::class, 'job');
        if ($class) {
            $qb->andWhere($qb->expr()->eq('job.class', ':class'))
                ->setParameter('class', $class);
        }
        if (!is_array($statusesOrProcessing)) {
            if ($statusesOrProcessing) {
                $statusesOrProcessing = [
                    \Omeka\Entity\Job::STATUS_STARTING,
                    \Omeka\Entity\Job::STATUS_STOPPING,
                    \Omeka\Entity\Job::STATUS_IN_PROGRESS,
                ];
            } else {
                $statusesOrProcessing = [
                    \Omeka\Entity\Job::STATUS_COMPLETED,
                    \Omeka\Entity\Job::STATUS_STOPPED,
                    \Omeka\Entity\Job::STATUS_ERROR,
                ];
            }
        }
        if ($statusesOrProcessing) {
            $connection = $this->entityManager->getConnection();
            $quoted = implode(',', array_map([$connection, 'quote'], $statusesOrProcessing));
            $qb->andWhere('job.status IN (' . $quoted . ')');
        }
        if ($ownerId) {
            $qb->andWhere($qb->expr()->eq('job.owner_id', 'owner'))
                ->setParameter('owner', (int) $ownerId);
        }
        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
        return $result;
    }
}
