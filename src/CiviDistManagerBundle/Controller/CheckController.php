<?php

namespace CiviDistManagerBundle\Controller;

use CiviDistManagerBundle\BuildRepository;
use CiviDistManagerBundle\VersionUtil;
use Doctrine\Common\Cache\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckController extends Controller {

  const STABLE_DOWNLOAD_URL = 'https://storage.googleapis.com/civicrm/civicrm-stable';
  const VERSIONS_URL = 'https://latest.civicrm.org/versions.json';
  const CACHE_TTL = 120;

  /**
   * Get metadata about the various tarballs available for the
   * stable, rc, or nightly release. (JSON)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function checkAction(Request $request) {
    try {
      $revSpec = $this->findRevByStability(strtolower($request->get('stability')));
      return $this->createJson($revSpec, $revSpec['rev'] === NULL ? 404 : 200);
    }
    catch (\RuntimeException $e) {
      $this->get('logger')->error('Failed to check on available tarballs', array(
        'exception' => $e,
      ));
      return $this->createJsonError('Unexpected exception');
    }
  }

  /**
   * Display a list of (logical) files. (HTML)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function downloadListAction(Request $request) {
    $logicalFiles = array();
    $suffixes = array(
      'backdrop.tar.gz',
      'drupal.tar.gz',
      'drupal6.tar.gz',
      'joomla.zip',
      'wordpress.zip',
      'l10n.tar.gz',
    );
    foreach (array('STABLE', 'RC', 'NIGHTLY', '46NIGHTLY') as $stability) {
      foreach ($suffixes as $suffix) {
        $basename = "civicrm-$stability-$suffix";
        $revSpec = $this->findRevByFilename($basename);
        $logicalFiles[$basename] = array(
          'rev' => $revSpec['rev'],
          'basename' => $basename,
          'url' => $this->generateUrl('download_file', array(
            'file' => $basename,
          )),
          'inspect_url' => NULL,
        );

        if (strpos($revSpec['rev'], '-') !== FALSE) {
          $logicalFiles[$basename]['inspect_url'] = $this->generateUrl('inspect_file', array(
            'file' => $basename,
          ));
        }
      }
    }

    /** @var BuildRepository $buildRepo */
    $buildRepo = $this->container->get('build_repository');
    $branches = array();
    foreach ($buildRepo->getOptions('branch') as $branch) {
      $branches[$branch] = $this->generateUrl('browse_branch', array(
        'branch' => $branch,
      ));
    }
    ksort($branches);

    return $this->render('CiviDistManagerBundle:Check:downloadList.html.twig', array(
      'logicalFiles' => $logicalFiles,
      'branches' => $branches,
    ));
  }

  /**
   * Get the download for the stable, rc, or nightly tarball.
   *
   * Ex: "GET /latest/civicrm-NIGHTLY-joomla.zip".

   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function downloadAction(Request $request) {
    try {
      $url = $this->findDownloadUrl($request->get('file'));
      return $url ? $this->redirect($url) : $this->createJsonError("File not found", 404);
    }
    catch (\RuntimeException $e) {
      $this->get('logger')->error('Failed to check on available tarballs', array(
        'exception' => $e,
      ));
      return $this->createJsonError('Unexpected exception');
    }
  }

  /**
   * View the build report for the file
   *
   * Ex: "GET /latest/civicrm-NIGHTLY-joomla.zip".

   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function inspectAction(Request $request) {
    /** @var BuildRepository $buildRepo */
    $buildRepo = $this->container->get('build_repository');

    $fileUrl = $this->findDownloadUrl($request->get('file'));
    $jsonDef = $buildRepo->fetchJsonDef($fileUrl);

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

  private function findRevByFilename($file) {
    if (!preg_match(';^civicrm-(46nightly|stable|rc|nightly)-;i', $file, $matches)) {
      return array('rev' => NULL, 'message' => 'Unrecognized stability or CMS');
    }
    $stability = strtolower($matches[1]);
    $revSpec = $this->findRevByStability($stability);
    return $revSpec;
  }

  /**
   * @param string $desiredFile
   *   Ex: 'civicrm-RC-drupal.tar.gz'
   * @return string|NULL
   */
  protected function findDownloadUrl($desiredFile) {
    $revSpec = $this->findRevByFilename($desiredFile);
    if ($revSpec['rev'] !== NULL) {
      $expectExt = $this->parseFileExt($desiredFile);
      foreach ($revSpec['tar'] as $possibleUrl) {
        if ($expectExt === $this->parseFileExt($possibleUrl)) {
          return $possibleUrl;
        }
      }
    }
    return NULL;
  }

  /**
   * @param string $file
   *   Ex: '/var/foo/civicrm-46NIGHTLY-drupal.tar.gz'
   *   Ex: 'http://example.org/civicrm-4.7.30-wordpress-201801010101.zip'
   * @return string|NULL
   *   Ex: 'drupal.tar.gz'.
   *   Ex: 'wordpress.zip'.
   */
  private function parseFileExt($file) {
    $file = basename($file);
    if (!preg_match(';^civicrm-([0-9\.]+|46nightly|stable|rc|nightly)-([a-zA-Z0-9\-_]+)\.(zip|tar.gz|tgz|json)$;i', $file, $matches)) {
      return NULL;
    }
    $middle = preg_replace(';(-\d+)$;', '', $matches[2]);
    $ext = $matches[3];
    return $middle . '.' . $ext;
  }

  /**
   * @return Cache
   */
  protected function getCache() {
    return $this->container->get('civi_upgrade_manager.dist_cache');
  }

  /**
   * @return array
   *   Ex: $result['tar']['Drupal'] = 'https://dist.civicrm.org/foo/civicrm-4.7.12-drupal-20160902.tar.gz';
   */
  protected function getLatestStable() {
    // Ex: $result['4.7']['releases'][0]['version'] == '4.7.alpha1';
    $versions = $this->fetchJson(self::VERSIONS_URL);
    $rev = '0';
    foreach ($versions as $major => $majorSpec) {
      foreach ($majorSpec['releases'] as $release) {
        if (version_compare($release['version'], $rev, '>')) {
          $rev = $release['version'];
        }
      }
    }

    return $this->createBackfilledStableMetadata($rev);
  }

  /**
   * @return array
   *   Ex: $result['tar']['Drupal'] = 'https://dist.civicrm.org/foo/civicrm-4.7.12-drupal-20160902.tar.gz';
   */
  protected function getLatestRc() {
    /** @var BuildRepository $buildRepo */
    $buildRepo = $this->container->get('build_repository');
    $targetBranch = VersionUtil::max($buildRepo->getOptions('branch', function ($file) {
      return (bool) preg_match(';^[0-9\.]+-rc$;', $file['branch']);
    }));
    return $this->container->get('rev_doc_repository')->findLatest(function($rev) use ($targetBranch) {
      return $rev['branch'] === $targetBranch;
    });
  }

  /**
   * Create a Response object of json data.
   *
   * @param array $data
   *   Data to return in the response. (It will be serialized.)
   * @param int $status
   *   HTTP response code. Ex: 200, 404.
   * @return \Symfony\Component\HttpFoundation\Response
   */
  protected function createJson($data, $status) {
    return new Response(json_encode($data), $status, array(
      'Content-type' => 'application/json',
    ));
  }

  /**
   * Create a Response object of json data -- with just an error message.
   *
   * @param string $errorMessage
   * @param int $status
   * @return \Symfony\Component\HttpFoundation\Response
   */
  protected function createJsonError($errorMessage, $status = 500) {
    return $this->createJson(array(
      'rev' => NULL,
      'message' => $errorMessage,
    ), $status);
  }

  /**
   *
   * @return array
   */
  protected function fetchJson($url) {
    /** @var Cache $cache */
    $cache = $this->getCache();
    if (!$cache->contains($url)) {
      $data = file_get_contents($url);
      if ($data) {
        $cache->save($url, $data, self::CACHE_TTL);
      }
      else {
        throw new \RuntimeException('Failed to fetch URL: ' . $url);
      }
    }
    return json_decode($cache->fetch($url), 1);
  }

  /**
   * @param $rev
   * @return array
   */
  protected function createBackfilledStableMetadata($rev) {
    $backdropFile = version_compare('4.7.20', $rev, '<=')
      ? 'backdrop-unstable' : 'backdrop';

    return array(
      'version' => $rev,
      'rev' => $rev,
      'tar' => array(
        'Backdrop' => sprintf('%s/%s/civicrm-%s-%s.tar.gz',
          self::STABLE_DOWNLOAD_URL, $rev, $rev, $backdropFile),
        'Drupal' => sprintf('%s/%s/civicrm-%s-drupal.tar.gz',
          self::STABLE_DOWNLOAD_URL, $rev, $rev),
        'Drupal6' => sprintf('%s/%s/civicrm-%s-drupal6.tar.gz',
          self::STABLE_DOWNLOAD_URL, $rev, $rev),
        'Joomla' => sprintf('%s/%s/civicrm-%s-joomla.zip',
          self::STABLE_DOWNLOAD_URL, $rev, $rev),
        // 'Joomla-Alt' => 'https://download.civicrm.org/civicrm-4.7.12-joomla-alt.zip',
        'L10n' => sprintf('%s/%s/civicrm-%s-l10n.tar.gz',
          self::STABLE_DOWNLOAD_URL, $rev, $rev),
        'WordPress' => sprintf('%s/%s/civicrm-%s-wordpress.zip',
          self::STABLE_DOWNLOAD_URL, $rev, $rev),
      ),
      'git' => array(
        'civicrm-core' => array('commit' => $rev),
        'civicrm-joomla' => array('commit' => $rev),
        'civicrm-backdrop@1.x' => array('commit' => "1.x-$rev"),
        'civicrm-packages' => array('commit' => $rev),
        'civicrm-drupal@6.x' => array('commit' => "6.x-$rev"),
        'civicrm-drupal@7.x' => array('commit' => "7.x-$rev"),
        'civicrm-drupal@8.x' => array('commit' => "8.x-$rev"),
        'civicrm-wordpress' => array('commit' => $rev),
      ),
    );
  }

  /**
   * @param string $stability
   *   Ex: 'rc', 'stable', 'nightly', '46nightly'.
   * @return array
   */
  protected function findRevByStability($stability) {
    switch ($stability) {
      case 'rc':
        $latestRc = $this->getLatestRc();
        $stable = $this->getLatestStable();
        $result
          = ($latestRc && version_compare($latestRc['version'], $stable['version'], '>='))
          ? $latestRc : $stable;
        break;

      case 'stable':
        $result = $this->getLatestStable();
        break;

      case 'nightly':
        $result = $this->container->get('rev_doc_repository')->findLatest(function($rev) {
          return $rev['branch'] === 'master';
        });
        break;

      case '46nightly':
        $result = $this->container->get('rev_doc_repository')->findLatest(function($rev) {
          return $rev['branch'] === '4.6';
        });
        break;

      default:
        $result = array(
          'rev' => NULL,
          'message' => 'Missing required argument: stability=(nightly|rc|stable)',
        );
        return $result;
    }
    return $result;
  }

}
