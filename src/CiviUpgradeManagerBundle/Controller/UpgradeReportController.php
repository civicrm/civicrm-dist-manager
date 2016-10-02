<?php

namespace CiviUpgradeManagerBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use CiviUpgradeManagerBundle\Entity\UpgradeReport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * UpgradeReport controller.
 *
 * @Route("/")
 */
class UpgradeReportController extends Controller {

  /**
   * Lists all UpgradeReport entities.
   *
   * @Route("/report", name="UpgradeReport_submit")
   * @Method("POST")
   */
  public function submitAction(Request $request) {
    $em = $this->getDoctrine()->getManager();

    foreach (array('siteId', 'name') as $field) {
      if (!$request->get($field)) {
        return $this->createJson(array('message' => "Missing required argument: $field"), 400); // FIXME: Status code
      }
    }

    /** @var UpgradeReport $upgradeReport */
    $upgradeReport = $em->getRepository('CiviUpgradeManagerBundle:UpgradeReport')
      ->find($request->get('name'));

    if ($upgradeReport && $upgradeReport->getSiteId() !== $request->get('siteId')) {
      return $this->createJson(array('message' => "Report already exists. Claimed by different siteId."), 400); // FIXME: Status code
    }

    if (!$upgradeReport) {
      $upgradeReport = new UpgradeReport();
      $upgradeReport->setName($request->get('name'));
      $upgradeReport->setSiteId($request->get('siteId'));

      if (!$request->get('revision')) {
        return $this->createJson(array('message' => "Missing required argument: revision"), 400); // FIXME: Status code
      }
      $upgradeReport->setRevision($request->get('revision'));
      $em->persist($upgradeReport);
    }

    $fields = array(
      'reporter'=> 'basic',
      'downloadUrl'=> 'basic',
      'startReport'=> 'basic',
      'upgradeReport'=> 'basic',
      'finishReport'=> 'basic',
      'problem'=> 'basic',
      'started' => 'ts',
      'downloaded' => 'ts',
      'extracted' => 'ts',
      'upgraded' => 'ts',
      'finished' => 'ts',
      'failed' => 'ts',
    );
    foreach ($fields as $field => $type) {
      if (!$request->get($field)) {
        continue;
      }

      if ($upgradeReport->get($field)) {
        return $this->createJson(array('message' => "Field \"$field\" has already been set"), 400); // FIXME: Status code
      }
      else {
        switch ($type) {
          case 'basic':
            $upgradeReport->set($field, $request->get($field));
            break;
          case 'ts':
            $upgradeReport->set($field, new \DateTime(date('r', $request->get($field))));
            break;
          default:
            throw new \RuntimeException("Unrecognized field type: $type");
        }
      }
    }

    $upgradeReport->updateComputedFields();
    $em->flush();

    return $this->createJson(array('message' => 'Saved'), 200);
  }


  /**
   * Lists all UpgradeReport entities.
   *
   * @Route("/report/review", name="UpgradeReport_index")
   * @Method("GET")
   */
  public function indexAction() {
    $em = $this->getDoctrine()->getManager();

    $upgradeReports = $em->getRepository('CiviUpgradeManagerBundle:UpgradeReport')
        ->findBy([], ['started' => 'DESC']);

    return $this->render('upgradereport/index.html.twig', array(
      'upgradeReports' => $upgradeReports,
    ));
  }

  /**
   * Finds and displays a UpgradeReport entity.
   *
   * @Route("/report/review/{id}", name="UpgradeReport_show")
   * @Method("GET")
   */
  public function showAction(
    UpgradeReport $upgradeReport
  ) {

    return $this->render('upgradereport/show.html.twig', array(
      'upgradeReport' => $upgradeReport,
    ));
  }

  /**
   * @param $data
   * @param $status
   * @return \Symfony\Component\HttpFoundation\Response
   */
  protected function createJson($data, $status) {
    return new Response(json_encode($data), $status, array(
      'Content-type' => 'application/json',
    ));
  }

}
