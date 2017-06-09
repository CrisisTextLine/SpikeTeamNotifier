<?php

namespace SpikeTeam\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SpikerGroupRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SpikerRepository extends EntityRepository
{
    /**
     * Returns Spikers from enabled groups, in reverse ID order
     */
    public function findByEnabledGroup()
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.group', 'g')
            ->where('g.enabled = 1')
            ->orderBy('s.id', 'DESC');

        try {
            return $qb->getQuery()->getResult();
        }  catch(\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }

    /**
     * Returns Spikers from specified group, in reverse ID order
     */
    public function findByGroupDesc($group)
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.group', 'g')
            ->where('g.id = :gid')
            ->orderBy('s.id', 'DESC')
            ->setParameter('gid', $group);

        try {
            return $qb->getQuery()->getResult();
        }  catch(\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }

    /**
     * Returns Spikers from enabled groups
     */
    public function findAllNonCaptain()
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.isCaptain is null OR s.isCaptain <> 1');

        try {
            return $qb->getQuery()->getResult();
        }  catch(\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }

    /**
     * Checks if spiker by the number exists
     */
    public function phoneNumberExists($phone)
    {
        $qb = $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->where('s.phoneNumber = :phone')
            ->setParameter('phone', $phone);

        try {
            return ($qb->getQuery()->getSingleScalarResult()) ? true : false;
        }  catch(\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }

    /**
     * Checks if spiker by that email exists
     */
    public function emailExists($email)
    {
        $qb = $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->where('s.email = :email')
            ->setParameter('email', $email);

        try {
            return ($qb->getQuery()->getSingleScalarResult()) ? true : false;
        }  catch(\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}
