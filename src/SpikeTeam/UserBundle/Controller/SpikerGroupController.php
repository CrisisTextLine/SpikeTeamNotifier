<?php

namespace SpikeTeam\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use SpikeTeam\UserBundle\Entity\SpikerGroup;
use SpikeTeam\UserBundle\Entity\Spiker;
use SpikeTeam\UserBundle\Form\SpikerGroupType;

/**
 * SpikerGroup controller.
 *
 * @Route("/group")
 */
class SpikerGroupController extends Controller
{
    /**
     * Creates a new SpikerGroup entity.
     *
     * @Route("/", name="group_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $this->makeNewGroup();
        return $this->redirect($this->generateUrl('spikers'));
    }

    /**
     * Displays a form to create a new SpikerGroup entity.
     *
     * @Route("/new", name="group_new", options={"expose"=true})
     * @Method("GET")
     */
    public function newAction()
    {
        $this->makeNewGroup();
        return $this->redirect($this->generateUrl('spikers'));
    }

    private function makeNewGroup()
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }
        $em = $this->getDoctrine()->getManager();

        $entity = new SpikerGroup();
        $em->persist($entity);
        $em->flush();
        $entity->setName('Group '.strval($entity->getId()));
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Displays a form to edit an existing SpikerGroup entity.
     *
     * @Route("/{id}/edit", name="group_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SpikeTeamUserBundle:SpikerGroup')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SpikerGroup entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a SpikerGroup entity.
    *
    * @param SpikerGroup $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(SpikerGroup $entity)
    {
        $form = $this->createForm(new SpikerGroupType(), $entity, array(
            'action' => $this->generateUrl('group_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing SpikerGroup entity.
     *
     * @Route("/{id}", name="group_update")
     * @Method("PUT")
     * @Template("SpikeTeamUserBundle:SpikerGroup:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SpikeTeamUserBundle:SpikerGroup')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SpikerGroup entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('group_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a SpikerGroup entity.
     *
     * @Route("/{id}", name="group_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('SpikeTeamUserBundle:SpikerGroup')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SpikerGroup entity.');
            }

            // Checking and resetting Spikers to other groups if already assigned to this one
            $spikers = $em->getRepository('SpikeTeamUserBundle:Spiker')->findByGroup($entity);
            $em->remove($entity);
            foreach($spikers as $spiker) {
                $spiker->setGroup($em->getRepository('SpikeTeamUserBundle:SpikerGroup')->findEmptiest());
                $em->persist($spiker);
            }
            $em->flush();
        }

        return $this->redirect($this->generateUrl('spikers'));
    }

    /**
     * Creates a form to delete a SpikerGroup entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('group_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }

    /**
     * AJAX query, returns JSON response of emptiest group's ID
     *
     * @Route("/emptiest", name="group_emptiest_check", options={"expose"=true})
     */
    public function checkEmptiestAction()
    {
        $em = $this->getDoctrine()->getManager();
        return new JsonResponse(array(
            'emptiest' => $em->getRepository('SpikeTeamUserBundle:SpikerGroup')
                            ->findEmptiest()->getId()
        ));
    }

    /**
     * AJAX query, returns JSON response if group is enabled or not
     *
     * @Route("/status/{id}", name="group_status_check", options={"expose"=true})
     */
    public function checkStatusAction($id = null)
    {
        $em = $this->getDoctrine()->getManager();
        if ($id != null) {
            $return = $em->getRepository('SpikeTeamUserBundle:SpikerGroup')->find($id)->getEnabled();
        } else {
            $return = $em->getRepository('SpikeTeamUserBundle:SpikerGroup')->getAllIds();
        }
        return new JsonResponse(array(
            'enabled' => $return
        ));
    }

    /**
     * AJAX query, set enabled/disabled status of group
     *
     * @Route("/set/{id}/{status}", name="group_status_set", options={"expose"=true})
     */
    public function setStatusAction($id, $status)
    {
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('SpikeTeamUserBundle:SpikerGroup')->find($id);
        $group->setEnabled($status);
        $em->persist($group);
        $em->flush();

        return new JsonResponse();
    }

    /**
     * AJAX query, set captain for group
     *
     * @Route("/captain/{id}/{cid}", name="group_captain_set", options={"expose"=true})
     */
    public function setCaptainAction($id, $cid)
    {
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('SpikeTeamUserBundle:SpikerGroup')->find($id);
        $captain = $em->getRepository('SpikeTeamUserBundle:Spiker')->find($cid);

        if ($captain->getGroup() == $group) {
            $this->get('spike_team.user_helper')->setCaptain($captain, $group);
            $return = true;
        } else {
            $return = false;
        }
        return new JsonResponse($return);
    }
}
