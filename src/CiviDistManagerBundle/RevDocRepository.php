<?php

namespace CiviDistManagerBundle;
use Google\Cloud\Core\Timestamp;

/**
 * Class RevDocRepository
 * @package CiviDistManagerBundle
 *
 * Manage the list of *revision documents*.
 *
 * Compare with BuildRepository:
 *  - The BuildRepository tracks a list of individual files. Each file has proprties (like filename and timestamp).
 *  - The RevDocRepository tracks a list of revisions. Each revision can have multiple files.
 */
class RevDocRepository {
  const CACHE_TTL = 300;

  /**
   * @var \Google\Cloud\Storage\Bucket
   */
  protected $bucket;

  /**
   * @var \Doctrine\Common\Cache\Cache
   */
  protected $cache;

  /**
   * @var array|null
   */
  protected $tree;

  /**
   * @var \CiviDistManagerBundle\BuildRepository
   */
  protected $buildRepo;

  /**
   * @var string
   *   Ex: "+90 min".
   */
  protected $urlTtl;

  /**
   * RevDocRepository constructor.
   * @param \Google\Cloud\Storage\Bucket $bucket
   * @param \Doctrine\Common\Cache\Cache $cache
   */
  public function __construct(\Google\Cloud\Storage\Bucket $bucket, \Doctrine\Common\Cache\Cache $cache, \CiviDistManagerBundle\BuildRepository $buildRepo, $urlTtl) {
    $this->bucket = $bucket;
    $this->cache = $cache;
    $this->buildRepo = $buildRepo;
    $this->urlTtl = $urlTtl;
  }

  /**
   * Get the list of per-revision specifications.
   *
   * @return array
   *   A list of revSpec documents. Each item has:
   *    - version: string. Ex: '4.7.30'.
   *    - rev: string. Ex: '4.7.30-201801140252'.
   *    - branch: string (optional). Ex: 'master'.
   *    - tar: array
   *      - Backdrop: string, url.
   *      - Drupal: string, url.
   *      - Drupal6: string, url.
   *      - Joomla: string, url.
   *      - L10n: string, url.
   *      - WordPress: string, url.
   *    - timestamp: array
   *       - epoch: int. Ex: 1515898320
   *       - pretty: string. Ex: "Sat, 13 Jan 2018 18:52:00 -0800"
   */
  public function getRevDocs() {
    $cacheKey = __CLASS__ . '::' . $this->bucket->name() . '::revSpecs';
    if (!$this->cache->contains($cacheKey)) {
      $this->cache->save($cacheKey, $this->createRevDocs(), self::CACHE_TTL);
    }
    return $this->cache->fetch($cacheKey);
  }

  /**
   * Filter the list of revdocs; among matches, pick the one with the most
   * recent timestamp.
   *
   * @param callable $filter
   *   Function($revDoc) => bool.
   * @return array|NULL
   *   The latest rev-doc which matches the filter.
   *   If none match, then NULL.
   */
  public function findLatest($filter = NULL) {
    if ($filter) {
      $revDocs = array_filter($this->getRevDocs(), $filter);
    }
    else {
      $revDocs = $this->getRevDocs();
    }

    $max = NULL;
    foreach ($revDocs as $revDoc) {
      if ($max === NULL || $max['timestamp']['epoch'] < $revDoc['timestamp']['epoch']) {
        $max = $revDoc;
      }
    }
    return $max;
  }

  /**
   * @return array
   */
  public function createRevDocs() {
    $revDocs = array();
    foreach ($this->buildRepo->getFiles() as $file) {
      $tarName = $this->getTarName($file);
      if (!$tarName) {
        continue;
      }

      $revSpecId = implode('::', [$file['branch'], $file['rev']]);
      if (!isset($revDocs[$revSpecId])) {
        $revDocs[$revSpecId] = [
          'version' => $file['version'],
          'rev' => $file['rev'],
          'branch' => $file['branch'],
          'tar' => array(),
          'timestamp' => array(
            'epoch' => $file['timestamp'],
            'pretty' => date('r', $file['timestamp']),
          ),
        ];
      }
      $revDocs[$revSpecId]['tar'][$tarName] = $this->bucket->object($file['file'])->signedUrl(new Timestamp(new \DateTime($this->urlTtl)));
    }
    return $revDocs;
  }

  /**
   * @param array $file
   *   A full file.
   *   Ex: ['basename' => 'civicrm-X.Y.Z-drupal.tar.gz'].
   * @return string|null
   *   The symbolic name of this tar file.
   *   Ex: 'Drupal' or 'Drupal6'.
   */
  protected function getTarName($file) {
    if (!preg_match(';^civicrm-(.*)-(backdrop|drupal|drupal6|joomla|joomla5|joomla5bc|joomla-alt|standalone|starterkit|wordpress|wporg|l10n)(-[0-9]+)?\.(zip|tar.gz|tgz)$;i', $file['basename'], $matches)) {
      return NULL;
    }
    $cms = $matches[2];
    $myMap = array(
      'backdrop' => 'Backdrop',
      'drupal' => 'Drupal',
      'drupal6' => 'Drupal6',
      'joomla' => 'Joomla',
      'joomla5' => 'Joomla5',
      'joomla5bc' => 'Joomla5BC',
      'joomla-alt' => 'JoomlaAlt',
      'standalone' => 'Standalone',
      'starterkit' => 'StarterKit',
      'wordpress' => 'WordPress',
      'wporg' => 'WPOrg',
      'l10n' => 'L10n',
    );
    return $myMap[$cms];
  }

}
