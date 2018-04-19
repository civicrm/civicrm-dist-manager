<?php

namespace CiviDistManagerBundle\Controller;

use CiviDistManagerBundle\BuildRepository;
use CiviDistManagerBundle\VersionUtil;
use Doctrine\Common\Cache\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BrowseController extends Controller {

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

    if (!empty($files)) {
      $now = time();
      $basenames = [
        'civicrm-X.Y.Z-backdrop-LATEST.tar.gz',
        'civicrm-X.Y.Z-drupal-LATEST.tar.gz',
        'civicrm-X.Y.Z-drupal6-LATEST.tar.gz',
        'civicrm-X.Y.Z-joomla-LATEST.zip',
        'civicrm-X.Y.Z-joomla-alt-LATEST.zip',
        'civicrm-X.Y.Z-l10n-LATEST.tar.gz',
        'civicrm-X.Y.Z-starterkit-LATEST.tgz',
        'civicrm-X.Y.Z-wordpress-LATEST.zip',
        'civicrm-X.Y.Z-wporg-LATEST.zip',
      ];

      foreach ($basenames as $basename) {
        $files[] = [
          'file' => 'LATEST/' . $basename,
            'basename' => $basename,
            'branch' => $request->get('branch'),
            'version' => 'X.Y.Z',
            // 'url' => NULL,
            'rev' => 'X.Y.Z-LATEST',
            // 'uf' => 'Backdrop',
            'ts' => 'LATEST',
            'timestamp' => $now,
        ];
      }
    }

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
    $file = $this->getFile($request->get('branch'), $request->get('ts'), $request->get('basename'));

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

    $file = $this->getFile($request->get('branch'), $request->get('ts'), $request->get('basename'));

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

  /**
   * Locate the file record from the buildrepo.
   *
   * @param string $branch
   *   Ex: '5.0', 'master'.
   * @param string $ts
   *   Ex: 'LATEST', '201704210350'.
   * @param string $basename
   *   Ex: 'civicrm-X.Y.Z-drupal-LATEST.tar.gz'.
   * @return array
   */
  protected function getFile($branch, $ts, $basename) {
    /** @var BuildRepository $buildRepo */
    $buildRepo = $this->container->get('build_repository');

    if ($ts === 'LATEST') {
      $wildBasename = strtr($basename, [
        'X.Y.Z' => '*',
        'LATEST' => '*',
      ]);
      $files = $buildRepo->getFilesByWildcard($branch . '/' . $wildBasename);
      usort($files, function($a, $b) {
        return -1 * strnatcmp($a['ts'], $b['ts']);
      });
      $file = isset($files[0]) ? $files[0] : NULL;
    }
    else {
      $file = $buildRepo->getFile([
        'branch' => $branch,
        'basename' => $basename,
      ]);
    }

    if (!$file) {
      throw $this->createNotFoundException();
    }
    return $file;
  }

}
