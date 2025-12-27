<?php

namespace CiviDistManagerBundle\Controller;

use CiviDistManagerBundle\GStorageUrlFacade;
use Google\Cloud\Core\Timestamp;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GenericGcloudController
 * @package CiviDistManagerBundle\Controller
 *
 * This is a generic UI for browsing a subdirectory in the Google Cloud
 * Storage system.
 */
class GenericGcloudController extends Controller {

  /**
   * @var string
   *   Ex: 'gs://my-bucket/my-folder'
   */
  protected $baseUrl;

  /**
   * @var GStorageUrlFacade
   */
  protected $gsu;

  protected $title;

  /**
   * GenericGcloudController constructor.
   *
   * @param ContainerInterface $container
   * @param GStorageUrlFacade $gsu
   * @param string $baseUrl
   *   Ex: 'gs://my-bucket/my-folder'
   * @param string $title
   *   Ex: 'The Totallymostawesome Folder'
   */
  public function __construct($container, $gsu, $baseUrl, $title) {
    $this->setContainer($container);
    $this->gsu = $gsu;
    $this->baseUrl = $baseUrl;
    $this->title = $title;
  }

  /**
   * Browse the items in a path
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $path
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function browseAction(Request $request, $path) {
    $gsu = $this->gsu;
    $path = $this->filterPath($path);
    $url = "$this->baseUrl/$path";

    if (!$gsu->exists($url)) {
      throw $this->createNotFoundException("File not found: $path");
    }

    if (empty($path) || $path[strlen($path) - 1] === '/') {
      return $this->render('CiviDistManagerBundle:GenericGcloud:browse.html.twig', array(
        'title' => $this->title . (empty($path) ? "" : " ($path)"),
        'children' => $gsu->getChildren($url),
      ));
    }
    else {
      $signedUrl = $gsu->createObject($url)->signedUrl(new Timestamp(new \DateTime($this->container->getParameter('gcloud_url_ttl'))));
      return $this->redirect($signedUrl);
    }
  }

  protected function filterPath($path) {
    if ($path === NULL || $path === FALSE || $path === '') {
      return '';
    }
    if ($path[0] === '/') {
      throw new \Exception("Invalid path - Should not begin with '/'");
    }
    if (preg_match(';[^a-zA-Z0-9\-\._\+\/];', $path)) {
      throw new \Exception("Invalid path - Unsupported characters");
    }
    if (preg_match(';^\.\.;', $path)) {
      throw new \Exception("Invalid path - No relative expressions allowed");
    }
    if (preg_match(';/\.+/;', $path)) {
      throw new \Exception("Invalid path - No relative expressions allowed");
    }
    return $path;
  }

}
