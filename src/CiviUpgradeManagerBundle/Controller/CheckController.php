<?php

namespace CiviUpgradeManagerBundle\Controller;

use Doctrine\Common\Cache\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckController extends Controller {

  const STABLE_DOWNLOAD_URL = 'https://download.civicrm.org';
  const VERSIONS_URL = 'http://latest.civicrm.org/versions.json';
  const CACHE_TTL = 120;

  public function checkAction(Request $request) {
    switch ($request->get('stability')) {
      case 'rc':
        return $this->createJson($this->getLatestRc(), 200);

      case 'stable':
        return $this->createJson($this->getLatestStable(), 200);

      case 'nightly':
        return $this->createJson($this->getLatestRc(), 200);

      default:
        return $this->createJson(array(
          'rev' => NULL,
          'message' => 'Missing required argument: stability=(nightly|rc|stable)'
        ), 404);
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
   */
  protected function getLatestStable() {
    $versions = $this->getVersions();
    $rev = '0';
    foreach ($versions as $major => $majorSpec) {
      foreach ($majorSpec['releases'] as $release) {
        if (version_compare($release['version'], $rev, '>')) {
          $rev = $release['version'];
        }
      }
    }

    return array(
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
        'civicrm-core' => $rev,
        'civicrm-joomla' => $rev,
        'civicrm-backdrop@1.x' => "1.x-$rev",
        'civicrm-packages' => $rev,
        'civicrm-drupal@6.x' => "6.x-$rev",
        'civicrm-drupal@7.x' => "7.x-$rev",
        'civicrm-drupal@8.x' => "8.x-$rev",
        'civicrm-wordpress' => $rev,
      ),
    );
  }

  /**
   * @return array
   */
  protected function getLatestRc() {
    return $data = array(
      'rev' => 'c6ef392f2d68e4bc940d30e10c0e26b6-0003',
      'tar' => array(
        'Backdrop' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-backdrop-unstable-20160925.tar.gz',
        'Drupal' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-drupal-20160925.tar.gz',
        'Drupal6' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-drupal6-20160925.tar.gz',
        'Joomla' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-joomla-20160925.zip',
        // 'Joomla-Alt' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-joomla-alt-20160925.zip',
        'L10n' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-l10n-20160925.tar.gz',
        'WordPress' => 'https://dist.civicrm.org/by-hash/c6ef392f2d68e4bc940d30e10c0e26b6-0003/civicrm-4.7.12-wordpress-20160925.zip',
      ),
      'git' => array(
        'civicrm-core' => 'FIXME',
        'civicrm-joomla' => 'FIXME',
        'civicrm-backdrop' => 'FIXME',
        'civicrm-packages' => 'FIXME',
        'civicrm-drupal' => 'FIXME',
        'civicrm-wordpress' => 'FIXME',
      ),
    );
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

  /**
   * @return array
   *   Ex: $result['4.7']['releases'][0]['version'] == '4.7.alpha1';
   */
  protected function getVersions() {
    /** @var Cache $cache */
    $cache = $this->getCache();
    if (!$cache->contains('versions.json')) {
      $data = file_get_contents(self::VERSIONS_URL);
      if ($data) {
        $cache->save('versions.json', $data);
      }
      else {
        throw new \RuntimeException('Failed to fetch list of available versions');
      }
    }
    return json_decode($cache->fetch('versions.json'), 1);
  }

}
