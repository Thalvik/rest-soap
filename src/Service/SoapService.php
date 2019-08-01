<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;

class SoapService
{
    private $em;
    private $serializer;

    public function __construct(EntityManagerInterface $em, Serializer $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * @return string
     */
    public function soap()
    {
        $users = $this->em->getRepository('App:User')->findAll();
        $jsonContent = $this->serializer->serialize($users, 'xml');
        return $jsonContent;
    }
}
