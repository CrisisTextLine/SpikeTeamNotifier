<?php

namespace SpikeTeam\StatsDashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class StatsController extends Controller
{
    /**
     * @Route("/stats/", name="getStats")
     * @Method("GET")
     * @Template()
     */
    public function statsAction()
    {
        return array();
    }

    /**
     * @Route("/stats/", name="postStats")
     * @Method("POST")
     */
    public function updateStats(Request $request)
    {
        if ($request->request->get('api_key') === $this->container->getParameter('api_key')) {
            $uploadedFile = $request->files->get('stats');
            $file = $uploadedFile->move('../web/', 'stats.csv');
            // $response = new Response(json_encode($request->headers->get('content_type')));
            // return $response;
            return array();
        } else {
            $response = new Response(json_encode(array("wrong" => "answer")));
            return $response; 
        }
        
    }
}