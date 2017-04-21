<?php

namespace CiviDistManagerBundle;

/**
 * Class BuildRepository
 * @package CiviDistManagerBundle
 *
 * The BuildRepository keeps a list of all available builds.
 */
class BuildRepository {

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
   * BuildRepository constructor.
   * @param \Google\Cloud\Storage\Bucket $bucket
   * @param \Doctrine\Common\Cache\Cache $cache
   */
  public function __construct(\Google\Cloud\Storage\Bucket $bucket, \Doctrine\Common\Cache\Cache $cache) {
    $this->bucket = $bucket;
    $this->cache = $cache;
  }

  /**
   * @return array
   */
  public function getFiles() {
    $cacheKey = __CLASS__ . '::' . $this->bucket->name() . '::files';
    if (!$this->cache->contains($cacheKey)) {
      foreach ($this->fetchFileNames() as $fileName) {
        $file = $this->parseFileRecord($fileName);
        if ($file) {
          $files[] = $file;
        }
      }
      $this->cache->save($cacheKey, $files, self::CACHE_TTL);
    }
    return $this->cache->fetch($cacheKey);
  }

  /**
   * @param string $filter
   *   Ex: 'master/civicrm-*drupal*'.
   * @return array
   *   List of file records.
   */
  public function getFilesByWildcard($filter) {
    $regex = preg_quote($filter, ';');
    $regex = str_replace('\\*', '[^/]*', $regex);
    $files = array_filter($this->getFiles(), function ($file) use ($regex) {
      return preg_match(";^$regex;", $file['file']);
    });
    return $files;
  }

  /**
   * Get a list of options that appear for a given field.
   *
   * @param string $field
   *   Ex: 'rev'.
   * @param callable|null $filter
   *   Only includes options that match a filter.
   *   Ex: function($file) {return $file['branch'] === 'master';}
   * @return array
   *   Ex: array('4.7.10-123', '4.7.10-456')
   */
  public function getOptions($field, $filter = NULL) {
    $files = $this->getFiles();
    if ($filter) {
      $files = array_filter($files, $filter);
    }
    $options = array_column($files, $field);
    return array_unique($options);
  }

  /**
   * @param string $file
   *   Ex: 'master/civicrm-4.7.19-joomla-201704210350.zip'
   * @return string
   */
  public function getUrl($file) {
    return 'https://storage.googleapis.com/' . $this->bucket->name() . '/' . $file;
  }

  /**
   * @return array
   */
  private function fetchFileNames() {
    $this->tree = NULL;
    $data = array();
    foreach ($this->bucket->objects() as $object) {
      /** @var \Google\Cloud\Storage\StorageObject $object */
      $data[] = $object->name();
    }
    return $data;
  }

  /**
   * @param string $ts
   *   YYYYMMDDhhmm (utc)
   *   Ex: '201704210350'.
   * @return int
   *   Seconds since epoch.
   */
  private function parseTimestamp($ts) {
    $y = substr($ts, 0, 4);
    $m = substr($ts, 4, 2);
    $d = substr($ts, 6, 2);
    $hr = substr($ts, 8, 2);
    $min = substr($ts, 10, 2);
    return strtotime("$y-$m-$d $hr:$min UTC");
  }

  /**
   * @param string $file
   *   Ex: 'master/civicrm-4.7.19-joomla-201704210350.zip'
   * @return array|null
   */
  private function parseFileRecord($file) {
    $result = NULL;
    if (preg_match(';^([0-9a-zA-Z\.\-]+)/civicrm-([0-9\.]+(alpha|beta)?[0-9]*)-([a-zA-Z0-9]+)(-unstable)?-(\d+)\.(tar.gz|zip|tgz)$;', $file, $matches)) {
      $cmsMap = CmsMap::getMap();
      list ($full, $branch, $version, $ign, $cmsFile, $ignore2, $ts, $ext) = $matches;
      $tsEpoch = $this->parseTimestamp($ts);
      if (isset($cmsMap[$cmsFile])) {
        $result = array(
          'file' => $file,
          'url' => $this->getUrl($file),
          'branch' => $branch,
          'version' => $version,
          'rev' => "$version-$ts",
          'uf' => $cmsMap[$cmsFile],
          'timestamp' => $tsEpoch,
        );
      }
    }
    return $result;
  }

}
