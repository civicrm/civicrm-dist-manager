<?php

namespace CiviDistManagerBundle\Controller;

use CiviDistManagerBundle\BuildRepository;
use CiviDistManagerBundle\VersionUtil;
use Doctrine\Common\Cache\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BrowseController extends Controller {

  const STABLE_DOWNLOAD_URL = 'https://storage.googleapis.com/civicrm/civicrm-stable';
  const CACHE_TTL = 120;

  /**
   * Display a list of builds and files. (HTML)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function browseAction(Request $request) {
    /** @var BuildRepository $buildRepo */
    $buildRepo = $this->container->get('build_repository');

    $files = $buildRepo->getFilesByWildcard($request->get('branch') . '/');
    $byTimestamp = array();
    foreach ($files as $file) {
      $key = $file['ts'];
      if (!isset($byTimestamp[$key])) {
        $byTimestamp[$key] = array(
          'fmt' => date('Y-m-d H:i:s T', $file['timestamp']),
          'timestamp' => $file['timestamp'],
          'ts' => $file['ts'],
          'files' => array(),
        );
      }
      $file['_download_url'] = $this->generateUrl('download_branch_file', array(
        'branch' => $file['branch'],
        'ts' => $file['ts'],
        'basename' => $file['basename'],
      ));
      $file['_inspect_url'] = $this->generateUrl('inspect_branch_build', array(
        'branch' => $file['branch'],
        'ts' => $file['ts'],
        'basename' => $file['basename'],
      ));
      $byTimestamp[$key]['files'][] = $file;
    }
    uasort($byTimestamp, function($a, $b){
      return -1 * strnatcmp($a['ts'], $b['ts']);
    });

    return $this->render('CiviDistManagerBundle:Browse:browse.html.twig', array(
      'targetBranch' => $request->get('branch'),
      'byTimestamp' => $byTimestamp,
    ));
  }

  /**
   * Download a particular build (redirect).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function downloadAction(Request $request) {
    /** @var BuildRepository $buildRepo */
    $buildRepo = $this->container->get('build_repository');

    $file = $buildRepo->getFile([
      'branch' => $request->get('branch'),
      'basename' => $request->get('basename'),
    ]);
    if (!$file) {
      throw $this->createNotFoundException();
    }

    return $this->redirect($file['url']);
  }

  /**
   * View the build report for the build (HTML).
   *
   * Ex: "GET /latest/branch/master/201801140252/inspect".

   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function inspectAction(Request $request) {
    /** @var BuildRepository $buildRepo */
    $buildRepo = $this->container->get('build_repository');

    $file = $buildRepo->getFile([
      'branch' => $request->get('branch'),
      'basename' => $request->get('basename'),
    ]);
    if (!$file) {
      throw $this->createNotFoundException();
    }

    $jsonDef = $buildRepo->fetchJsonDef($file['url']);

    return $this->render('CiviDistManagerBundle:Check:inspect.html.twig', array(
      'jsonDef' => $jsonDef,
      'gitBrowsers' => array(
        'civicrm-core' => 'https://github.com/civicrm/civicrm-core/commits',
        'civicrm-packages' => 'https://github.com/civicrm/civicrm-packages/commits',
        'civicrm-joomla' => 'https://github.com/civicrm/civicrm-joomla/commits',
        'civicrm-backdrop@1.x' => 'https://github.com/civicrm/civicrm-backdrop/commits',
        'civicrm-drupal@6.x' => 'https://github.com/civicrm/civicrm-drupal/commits',
        'civicrm-drupal@7.x' => 'https://github.com/civicrm/civicrm-drupal/commits',
        'civicrm-drupal@8.x' => 'https://github.com/civicrm/civicrm-drupal/commits',
        'civicrm-wordpress' => 'https://github.com/civicrm/civicrm-wordpress/commits',
      ),
    ));
  }

}
