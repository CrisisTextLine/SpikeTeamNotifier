<?php

namespace SpikeTeam\ButtonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use SpikeTeam\ButtonBundle\Entity\ButtonPush;
use SpikeTeam\ButtonBundle\Event\AlertEvent;
use SpikeTeam\UserBundle\Helper\SpikerGroupHelper;

class ButtonController extends Controller
{
    /**
     * @Route("/", name="button", options={"expose"=true})
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $spikerGroupHelper = $this->get('spike_team.spiker_group_helper');
        $securityContext = $this->get('security.context');
        $mostRecent = $em->getRepository('SpikeTeamButtonBundle:ButtonPush')->findMostRecent();
        $currentGroupId = $spikerGroupHelper->getCurrentGroupId();

        $currentGroup = $this->getDoctrine()
                     ->getRepository('SpikeTeamUserBundle:SpikerGroup')
                     ->find($currentGroupId);

        $maxAlerts = $this->get('config')->get('alerts_per_day', 2);

        $message = $em->getRepository('SpikeTeamSettingBundle:Setting')
                      ->findOneByName('twilio_message')
                      ->getSetting();

        $canPush = $currentGroup->getRecentPushesCount() < $maxAlerts;

        if (!$securityContext->isGranted('ROLE_ADMIN')) {
            $canPush = false;
        }

        $weekdayNames = array(
            1 => 'Sunday',
            2 => 'Monday',
            3 => 'Tuesday',
            4 => 'Wednesday',
            5 => 'Thursday',
            6 => 'Friday',
            7 => 'Saturday'
        );

        return $this->render('SpikeTeamButtonBundle:Button:index.html.twig', array(
            'goUrl'         => $this->generateUrl('goteamgo', array('gid' => $currentGroup)),
            'canPush'       => $canPush,
            'message'       => $message,
            'mostRecent'    => $mostRecent,
            'currentGroup'  => $currentGroup,
            'weekdayNames'  => $weekdayNames
        ));
    }

    /**
     * @Route("/goteamgo/{gid}", name="goteamgo", options={"expose"=true})
     */
    public function goAction($gid)
    {
        $em = $this->getDoctrine()->getManager();
        $securityContext = $this->get('security.context');
        $spikerGroupHelper = $this->get('spike_team.spiker_group_helper');

        if ($gid == 'all') {
            $canPush = true;
        } else {
            $group = $this->getDoctrine()
                          ->getRepository('SpikeTeamUserBundle:SpikerGroup')
                          ->find($gid);

            $canPush = $group->getRecentPushesCount() < 2;
        }

        if (!$securityContext->isGranted('ROLE_ADMIN')) {
            $canPush = false;
        }

        if ($canPush) {
            // Dispatch alert event, to appropriate group if specified
            switch($gid) {
                case 'all':
                    $spikers = $this->getDoctrine()
                                    ->getRepository('SpikeTeamUserBundle:Spiker')
                                    ->findByEnabledGroup();
                    break;
                default:
                    $spikers = $this->getDoctrine()
                                    ->getRepository('SpikeTeamUserBundle:Spiker')
                                    ->findByGroup($group);
                    break;
            }
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('alert.send', new AlertEvent($spikers));

            // Record button push
            $push = new ButtonPush($this->getUser()->getId());
            if (isset($group)) {
                $push->setGroup($group);
            }

            $em->persist($push);
            $em->flush();

            // Send back latest push info
            $id = ($push->getGroup() == null) ? 'All Spikers' : $push->getGroup()->getName() . ' Group';
            return new JsonResponse(array(
                'id' => $id,
                'time' => $push->getPushTime()->format('G:i, m/d/y'),
                'enabled' => $this->getDoctrine()->getRepository('SpikeTeamUserBundle:SpikerGroup')->getAllIds()
            ));
        } else {
            return new JsonResponse(false);
        }
    }
}
