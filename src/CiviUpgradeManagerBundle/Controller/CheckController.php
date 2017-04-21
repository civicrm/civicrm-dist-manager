<?php

namespace CiviUpgradeManagerBundle\Controller;

use CiviUpgradeManagerBundle\BuildRepository;
use CiviUpgradeManagerBundle\VersionUtil;
use Doctrine\Common\Cache\Cache;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckController extends Controller {

  const STABLE_DOWNLOAD_URL = 'https://download.civicrm.org';
  const VERSIONS_URL = 'https://latest.civicrm.org/versions.json';
  const NIGHTLY_SUMMARY_URL = 'https://dist.civicrm.org/by-date/latest/summary.json';
  const CACHE_TTL = 120;

  public function checkAction(Request $request) {
    try {
      switch ($request->get('stability')) {
        case 'rc':
          $latestRc = $this->getLatestRc();
          $stable = $this->getLatestStable();
          $result
            = ($latestRc && version_compare($latestRc['version'], $stable['version'], '>='))
            ? $latestRc : $stable;
          return $this->createJson($result, 200);

        case 'stable':
          return $this->createJson($this->getLatestStable(), 200);

        case 'nightly':
          return $this->createJson($this->getLatestNightly(), 200);

        default:
          return $this->createJson(array(
            'rev' => NULL,
            'message' => 'Missing required argument: stability=(nightly|rc|stable)',
          ), 404);
      }
    } catch (\RuntimeException $e) {
      /** @var Logger $logger */
      $logger = $this->get('logger');
      $logger->error('Failed to check on available tarballs', array(
        'exception' => $e,
      ));
      return $this->createJson(array(
        'rev' => NULL,
        'message' => 'Unexpected exception',
      ), 500);
    }
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
    return $this->getLatestRevByBranch($targetBranch);
  }

  /**
   * @return array
   *   Ex: $result['tar']['Drupal'] = 'https://dist.civicrm.org/foo/civicrm-4.7.12-drupal-20160902.tar.gz';
   */
  protected function getLatestNightly() {
    return $this->getLatestRevByBranch('master');
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
    return array(
      'version' => $rev,
      'rev' => $rev,
      'tar' => array(
        'Backdrop' => sprintf('%s/civicrm-%s-backdrop-unstable.tar.gz',
          self::STABLE_DOWNLOAD_URL, $rev),
        'Drupal' => sprintf('%s/civicrm-%s-drupal.tar.gz',
          self::STABLE_DOWNLOAD_URL, $rev),
        'Drupal6' => sprintf('%s/civicrm-%s-drupal6.tar.gz',
          self::STABLE_DOWNLOAD_URL, $rev),
        'Joomla' => sprintf('%s/civicrm-%s-joomla.zip',
          self::STABLE_DOWNLOAD_URL, $rev),
        // 'Joomla-Alt' => 'https://download.civicrm.org/civicrm-4.7.12-joomla-alt.zip',
        'L10n' => sprintf('%s/civicrm-%s-l10n.tar.gz',
          self::STABLE_DOWNLOAD_URL, $rev),
        'WordPress' => sprintf('%s/civicrm-%s-wordpress.zip',
          self::STABLE_DOWNLOAD_URL, $rev),
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
   * Find all the latest revision (within a branch) and list out the
   * various files/metadata.
   *
   * @param string $targetBranch
   * @return array
   */
  protected function getLatestRevByBranch($targetBranch) {
    /** @var BuildRepository $buildRepo */
    $buildRepo = $this->container->get('build_repository');

    $targetRev = VersionUtil::max($buildRepo->getOptions('rev', function ($file) use ($targetBranch) {
      return $file['branch'] === $targetBranch;
    }));

    list ($targetTimestamp) = $buildRepo->getOptions('timestamp', function ($file) use ($targetBranch, $targetRev) {
      return $file['branch'] === $targetBranch && $file['rev'] == $targetRev;
    });

    list ($targetVersion) = $buildRepo->getOptions('version', function ($file) use ($targetBranch, $targetRev) {
      return $file['branch'] === $targetBranch && $file['rev'] == $targetRev;
    });

    $def = array(
      'version' => $targetVersion,
      'rev' => $targetRev,
      'tar' => array(),
      'timestamp' => array(
        'epoch' => $targetTimestamp,
        'pretty' => date('r', $targetTimestamp),
      )
    );

    foreach ($buildRepo->getFiles() as $file) {
      if ($file['branch'] === $targetBranch && $file['rev'] === $targetRev) {
        $def['tar'][$file['uf']] = $file['url'];
      }
    }

    return $def;
  }

}
