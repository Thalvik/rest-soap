<?php

namespace App\Controller\Soap;

use SoapServer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SoapController extends Controller
{
    /**
     * @Route("/soap", name="soap")
     */
    public function indexAction()
    {
        //Get soap path
        $soapPath = $this->get('kernel')->getProjectDir() . '/public/soap.wsdl';

        //Init the server
        $server = new SoapServer($soapPath);
        $server->setObject($this->get('soap_service'));

        //Since controller expects to return response
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        ob_start();
        $server->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }
}
