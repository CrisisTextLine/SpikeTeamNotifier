<?php

namespace SpikeTeam\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SpikeTeam\UserBundle\Form\SpikerType;
use SpikeTeam\UserBundle\Entity\Spiker;

/**
 * Spiker controller.
 *
 * @Route("/spikers", options={"expose"=true})
 */
class SpikerController extends Controller
{

    protected $container;
    protected $em;
    protected $repo;
    protected $gRepo;
    protected $userHelper;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->em = $this->getDoctrine()->getManager();
        $this->repo = $this->getDoctrine()->getRepository('SpikeTeamUserBundle:Spiker');
        $this->gRepo = $this->getDoctrine()->getRepository('SpikeTeamUserBundle:SpikerGroup');
        $this->userHelper = $this->get('spike_team.user_helper');
    }

    /**
     * Showing all spikers here
     * @Route("/{group}", name="spikers", requirements={"group": "\d+"}, options={"expose":true})
     */
    public function spikersAllAction(Request $request, $group = null)
    {
        $spikers = $this->repo->findAll();

        $newSpiker = new Spiker();
        $form = $this->createForm(new SpikerType(), $newSpiker)
                     ->remove('notificationPreference')
                     ->remove('isCaptain')
                     ->remove('isEnabled');

        $form->handleRequest($request);

        if ($form->isValid()) {
            // If it's valid, go ahead, save, and view the Spiker. Otherwise, redirect back to this form.
            $newSpiker->setIsEnabled(true);
            $this->em->persist($newSpiker);
            $this->em->flush();
            return $this->redirect($this->generateUrl('spikers'));

            $newSpiker->setCohort(intval($newSpiker->getCohort()));
        } else {
            $errors = $form->getErrors();
        }

        // Sorting by group, then captain, then first name
        usort($spikers, function($a, $b) {
            $aGid = $a->getGroup()->getId();
            $bGid = $b->getGroup()->getId();
            if ($aGid == $bGid) {
                if ($a->getIsCaptain()) {
                    return false;
                } else if ($b->getIsCaptain()) {
                    return true;
                } else {
                    return $a->getFirstName() >= $b->getFirstName();
                }
            } else {
                return $aGid > $bGid;
            }
        });

        $groupEnabled = true;
        if (isset($group)) {
            $groupEnabled = $this->gRepo->find($group)->getEnabled();
            $count = count($this->repo->findByGroup($group));
        } else {
            $count = count($spikers);
        }

        // send to template
        return $this->render('SpikeTeamUserBundle:Spiker:spikersAll.html.twig', array(
            'spikers' => $spikers,
            'form' => $form->createView(),
            'groupList' => $this->em->getRepository('SpikeTeamUserBundle:SpikerGroup')->findAll(),
            'group' => $group,
            'group_enabled' => $groupEnabled,
            'count' => $count,
            'errors' => (isset($errors)) ? $errors : null
        ));
    }

    /**
     * Mass enable/disable Spikers and set Groups here
     * @Route("/enabler", name="spikers_enable")
     */
    public function spikerEnablerAction(Request $request)
    {
        // AJAX request/fire event here, instead of HTML redirect?
        $spikers = $this->repo->findAllNonCaptain();
        $data = $request->request->all();
        foreach ($spikers as $spiker) {
            $sid = $spiker->getId();
            if (isset($data[$sid.'-enabled']) && $data[$sid.'-enabled'] == '1') {
                $spiker->setIsEnabled(true);
            } else {
                $spiker->setIsEnabled(false);
            }
            $group = $this->gRepo->find($data[$sid.'-group']);
            $spiker->setGroup($group);
            $this->em->persist($spiker);
        }
        $this->em->flush();

        $returnGroup = (isset($data['group'])) ? $data['group']: null;

        return $this->redirect($this->generateUrl('spikers', array('group' => $returnGroup)));
    }

    /**
     * Spike team signup
     * @Route("/signup", name="spiker_signup")
     */
    public function spikerSignupAction(Request $request)
    {
        $newSpiker = new Spiker();
        $form = $this->createForm(new SpikerType(), $newSpiker)
                     ->remove('isSupervisor')
                     ->remove('isEnabled')
                     ->remove('isCaptain')
                     ->remove('cohort');

        $form->handleRequest($request);

        if ($form->isValid()) {
            $newSpiker->setIsEnabled(true);
            $newSpiker->setIsSupervisor(false);
            $this->em->persist($newSpiker);
            $this->em->flush();

            $this->get('spike_team.spiker_signup_helper')->newSignup($newSpiker);

            return $this->render('SpikeTeamUserBundle:Spiker:success.html.twig', array(
                'spiker' => $newSpiker
            ));
        } else {
            $errors = $form->getErrors();
        }

        // send to template
        return $this->render('SpikeTeamUserBundle:Spiker:signup.html.twig', array(
            'form' => $form->createView(),
            'errors' => (isset($errors)) ? $errors : null
        ));
    }

    /**
     * Showing individual spiker here
     * @Route("/edit/{input}", name="spikers_edit")
     */
    public function spikerEditAction($input, Request $request)
    {
        $allUrl = $this->generateUrl('spikers');
        $editUrl = $this->generateUrl('spikers_edit', array('input' => $input));

        $spiker = $this->repo->findOneByPhoneNumber($input);
        $oldGroup = $spiker->getGroup();
        $oldIsCaptain = $spiker->getIsCaptain();

        $groupEditAttr = ($spiker->getIsCaptain()) ? array('disabled' => true) : [];

        $options =  array(
            'cohortRequired' => false,
            'lastNameRequired' => false
        );

        $form = $this->createForm(new SpikerType($options), $spiker);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // Deal w/ disabled group select / keep spiker from changing groups if previously captain
            if ($spiker->getGroup() == null
                || ($oldIsCaptain && $spiker->getGroup() !== $oldGroup)) {
                $spiker->setGroup($oldGroup);
            }

            if ($spiker->getIsCaptain()) {
                if (!$spiker->getIsEnabled()) {
                    $spiker->setIsEnabled(true);
                }
                $this->em->persist($spiker);
                $this->get('spike_team.user_helper')->setCaptain($spiker, $spiker->getGroup());
            }

            $this->em->persist($spiker);
            $this->em->flush();
            return $this->redirect($allUrl);
        } else {
            $errors = $form->getErrors();
        }

        return $this->render('SpikeTeamUserBundle:Spiker:spikerForm.html.twig', array(
            'spiker' => $spiker,
            'form' => $form->createView(),
            'errors' => (isset($errors)) ? $errors : null
        ));
    }

    /**
     * Delete individual spiker here
     * @Route("/delete/{input}", name="spikers_delete")
     */
    public function spikerDeleteAction($input)
    {
        if ($input) {
            $spiker = $this->repo->findOneByPhoneNumber($input);
            $this->em->remove($spiker);
            $this->em->flush();
        }
        return $this->redirect($this->generateUrl('spikers'));
    }

    /**
     * CSV Export spikers here
     * @Route("/export/{gid}", name="spikers_export", options={"expose":true})
     *
     * @param int $gid
     */
    public function spikersExportAction($gid = null)
    {
        $response = new StreamedResponse();
        $response->setCallback(function() use ($gid) {

            $handle = fopen('php://output', 'w+');
            fputcsv($handle, array(
                'First Name',
                'Last Name',
                'Phone',
                'Email',
                'Group',
                'Captain?',
                'Cohort',
                'Enabled?',
                'Supervisor?',
            ),',');
            $spikers = $this->repo->findAll();
            foreach ($spikers as $spiker) {
                if ($spiker->getGroup() == $gid || $gid == null) {
                    fputcsv($handle, array(
                        $spiker->getFirstName(),
                        $spiker->getLastName(),
                        $spiker->getPhoneNumber(),
                        $spiker->getEmail(),
                        $spiker->getGroup(),
                        ($spiker->getIsCaptain()) ? 'Yes' : 'No',
                        $spiker->getCohort(),
                        ($spiker->getIsEnabled()) ? 'Yes' : 'No',
                        ($spiker->getIsSupervisor()) ? 'Yes' : 'No',
                    ),',');
                }
            }
            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition','attachment; filename="spikers.csv"');

        return $response;
    }
}
