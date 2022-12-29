<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\ReservationState;
use App\Entity\Resource;
use AppendIterator;
use DateInterval;
use DatePeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Iterator;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function save(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Reservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getActiveReservations(Resource $resource)
    {
        return $this->getActiveReservationsQueryBuilder($resource)
            ->getQuery()
            ->getResult();
    }

    public function getReservedDates(Resource $resource): Iterator
    {
        $reservations = $this->getActiveReservationsQueryBuilder($resource)
            ->getQuery()
            ->getResult();

        $reservedDates = new AppendIterator();
        foreach ($reservations as $res) {
            $period = new DatePeriod(
                $res->getStartsAt(), new DateInterval('P1D'), $res->getEndsAt()->modify('+1 day')
            );
            $reservedDates->append($period->getIterator());
        }

        return $reservedDates;
    }

    /**
     * @param Resource $resource
     */
    public function getActiveReservationsQueryBuilder(Resource $resource): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->where('r.resource = :resource AND r.state != :canceled_state')
            ->setParameters(
                [
                    'resource' => $resource,
                    'canceled_state' => ReservationState::Canceled,
                ]
            );
    }
}
