<?php

namespace App\Repository;

use App\Entity\Annonces;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Annonces>
 *
 * @method Annonces|null find($id, $lockMode = null, $lockVersion = null)
 * @method Annonces|null findOneBy(array $criteria, array $orderBy = null)
 * @method Annonces[]    findAll()
 * @method Annonces[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnoncesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Annonces::class);
    }

    /**
     * Recherche les annonces en fonction du formulaire de recherche
     *
     * @return void
     */
    public function search($mots = null, $categorie = null)
    {
        $query = $this->createQueryBuilder('a');
        $query->where('a.active = 1');
        if ($mots != null) {
            // utilisé setParameter pcq :mots est un paramètre dans la query
            $query->andWhere('MATCH_AGAINST(a.title, a.content) AGAINST(:mots boolean)>0')
                ->setParameter('mots', $mots);
        }

        if ($categorie != null) {
            $query->leftJoin('a.categories', 'c');
            $query->andWhere('c.id = :id')
                ->setParameter('id', $categorie);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Return annonces between 2 dates
     */
    public function selectInterval($from, $to, $cat = null)
    {
        // Utilisation de createQuery
        // $query = $this->getEntityManager()->createQuery("
        // SELECT a FROM App\Entity\Annonces a WHERE a.created_at > :from AND a.created_at < :to
        // ")
        //     ->setParameter(':from', $from)
        //     ->setParameter(':to', $to);

        // return $query->getResult();

        // Utilisation de queryBuilder avoir plusieurs choix possible entre les query
        $query = $this->createQueryBuilder('a')
            ->where('a.created_at > :from')
            ->andWhere('a.created_at < :to')
            ->setParameter(':from', $from)
            ->setParameter(':to', $to);
        if ($cat != null) {
            $query->leftjoin('a.categories', 'c')
                ->andWhere('c.id = :cat')
                ->setParameter(':cat', $cat);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Return Annonces per page
     * @return void
     */
    public function getPaginatedAnnonces($page, $limit)
    {
        $query = $this->createQueryBuilder('a')
            ->where('a.active = 1')
            ->orderBy('a.created_at')
            ->setFirstResult(($page * $limit) - $limit)
            ->setMaxResults($limit);

        return $query->getQuery()->getResult();
    }

    /**
     * Returns number of Annonces
     *
     * @return void
     */
    public function getTotalAnnonces()
    {
        $query = $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.active = 1');
        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Annonces $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Annonces $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Annonces[] Returns an array of Annonces objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Annonces
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}