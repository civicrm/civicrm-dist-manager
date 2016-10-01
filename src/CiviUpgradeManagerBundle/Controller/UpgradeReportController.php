<?php

namespace CiviUpgradeManagerBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use CiviUpgradeManagerBundle\Entity\UpgradeReport;

/**
 * UpgradeReport controller.
 *
 * @Route("/UpgradeReport")
 */
class UpgradeReportController extends Controller {

  /**
   * Lists all UpgradeReport entities.
   *
   * @Route("/", name="UpgradeReport_index")
   * @Method("GET")
   */
  public function indexAction() {
    $em = $this->getDoctrine()->getManager();

    $upgradeReports = $em->getRepository('CiviUpgradeManagerBundle:UpgradeReport')
      ->findAll();

    return $this->render('upgradereport/index.html.twig', array(
      'upgradeReports' => $upgradeReports,
    ));
  }

  /**
   * Finds and displays a UpgradeReport entity.
   *
   * @Route("/{id}", name="UpgradeReport_show")
   * @Method("GET")
   */
  public function showAction(UpgradeReport $upgradeReport) {

    return $this->render('upgradereport/show.html.twig', array(
      'upgradeReport' => $upgradeReport,
    ));
  }

}
