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

  public function getFile($filters) {
    $matches = $this->getFilesByFilter($filters);
    if (count($matches) === 0) {
      return NULL;
    }
    elseif (count($matches) === 1) {
      return $matches[0];
    }
    else {
      throw new \RuntimeException("Found too many matches");
    }
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
   * @param array $filters
   * @return array
   */
  public function getFilesByFilter($filters) {
    $matches = array();
    foreach ($this->getFiles() as $file) {
      $match = TRUE;
      foreach ($filters as $filterKey => $filterValue) {
        $match = $match && ($file[$filterKey] === $filterValue);
      }
      if ($match) {
        $matches[] = $file;
      }
    }
    return $matches;
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
   * For a given tarball, lookup the corresponding JSON build def.
   *
   * @param string $tarFileUrl
   * @return array
   * @throws \Exception
   */
  public function fetchJsonDef($tarFileUrl) {
    if (!preg_match(';/([0-9a-zA-Z\.\-]+)/civicrm-([0-9\.]+(alpha|beta)?[0-9]*)-([a-zA-Z0-9]+)(-unstable|-alt)?-(\d+)\.(tar.gz|zip|tgz)(\?.*)?$;', $tarFileUrl, $matches)) {
      throw new \Exception("Failed to determine JSON metadata URL");
    }

    list ($full, $branch, $version, $ign, $cmsFile, $ignore2, $ts, $ext) = $matches;
    $jsonUrl = $this->getUrl("$branch/civicrm-$version-$ts.json");
    if (!$this->cache->contains($jsonUrl)) {
      $content = file_get_contents($jsonUrl);
      $this->cache->save($jsonUrl, $content, self::CACHE_TTL);
    }
    return json_decode($this->cache->fetch($jsonUrl), TRUE);
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
   *   Example:
   *   - file: 'master/civicrm-4.7.19-joomla-201704210350.zip'
   *   - basename: 'civicrm-4.7.19-joomla-201704210350.zip'
   *   - url: 'https://foo.bar/civicrm-4.7.19-joomla-201704210350.zip'
   *   - branch: 'master'
   *   - version: '4.7.19'
   *   - rev: '4.7.19-201704210350'
   *   - uf': 'Joomla'
   *   - ts: '201704210350'
   *   - timestamp: 12345678
   */
  private function parseFileRecord($file) {
    $result = NULL;
    if (preg_match(';^([0-9a-zA-Z\.\-]+)/civicrm-([0-9\.]+(alpha|beta)?[0-9]*)-([a-zA-Z0-9]+)(-unstable|-alt)?-(\d+)\.(tar.gz|zip|tgz)$;', $file, $matches)) {
      $cmsMap = CmsMap::getMap();
      list ($full, $branch, $version, $ign, $cmsFile, $ignore2, $ts, $ext) = $matches;
      $tsEpoch = $this->parseTimestamp($ts);
      if (isset($cmsMap[$cmsFile])) {
        $result = array(
          'file' => $file,
          'basename' => basename($file),
          'url' => $this->getUrl($file),
          'branch' => $branch,
          'version' => $version,
          'rev' => "$version-$ts",
          'uf' => $cmsMap[$cmsFile],
          'ts' => $ts,
          'timestamp' => $tsEpoch,
          'bucket' => $this->bucket->name(),
        );
      }
    }
    return $result;
  }

}
