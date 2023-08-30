<?php
namespace CiviDistManagerBundle;

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;

/**
 * Class GStorageUrlFacade
 * @package CiviDistManagerBundle
 *
 * This is a helper class for working with the Google Cloud Storage system.
 * It allows you to access resources using a URL notation ("gs://bucket/folder/file").
 */
class GStorageUrlFacade {

  const CACHE_TTL = 300;

  /**
   * @var \Doctrine\Common\Cache\Cache
   */
  protected $cache;

  /**
   * @var StorageClient
   */
  protected $storage;

  /**
   * @var array
   *   Ex: ['mybucket' => Bucket]
   */
  protected $buckets = [];

  /**
   * GStorageUrlFacade constructor.
   * @param \Doctrine\Common\Cache\Cache $cache
   * @param \Google\Cloud\Storage\StorageClient $storage
   */
  public function __construct(\Doctrine\Common\Cache\Cache $cache, \Google\Cloud\Storage\StorageClient $storage) {
    $this->cache = $cache;
    $this->storage = $storage;
  }

  /**
   * Get a list of all direct and indirect children of the URL.
   *
   * @param string $url
   *   Ex: 'gs://mybucket/myfolder/foo'
   * @return array
   *   Ex: ['bar' => 'gs://mybucket/myfolder/foo/bar']
   *   Note: Directories from files are distinguished by the trailing "/".
   */
  public function getAll($url) {
    list ($bucketName, $prefix) = $this->parseUrl($url);

    $cacheKey = __CLASS__ . '::' . $bucketName . '::files';
    if (!$this->cache->contains($cacheKey)) {
      /** @var Bucket $bucket */
      $bucket = $this->storage->bucket($bucketName);
      $files = [];

      foreach ($bucket->objects() as $object) {
        /** @var \Google\Cloud\Storage\StorageObject $object */
        $files[$object->name()] = $object->name();

        // Gcloud is inconsistent about report the existence of parents.
        // Infer ancestor existence.
        foreach ($this->ancestors($object->name()) as $ancestor) {
          if (!isset($files[$ancestor])) {
            $files[$ancestor] = $ancestor;
          }
          else {
            break;
          }
        }
      }

      ksort($files);
      $this->cache->save($cacheKey, $files, self::CACHE_TTL);
    }
    $files = $this->cache->fetch($cacheKey);

    $result = [];
    foreach ($files as $file) {
      if ($prefix === '') {
        $result[$file] = 'gs://' . $bucketName . '/' . $file;
      }
      elseif ($file !== $prefix && strpos($file, $prefix) === 0) {
        // Formula: $result[ relativePath ] = absPath;
        $result[(string) substr($file, strlen($prefix))] = 'gs://' . $bucketName . '/' . $file;
      }
    }
    return $result;
  }

  /**
   * Get a list of files and directories immediately beneath $url.
   *
   * @param string $url
   *   Ex: 'gs://mybucket/myfolder/foo'
   * @return array
   *   Ex: ['bar/' => 'gs://mybucket/myfolder/foo/bar/']
   *   Note: Directories from files are distinguished by the trailing "/".
   */
  public function getChildren($url) {
    $items = [];
    foreach ($this->getAll($url) as $relPath => $absPath) {
      $trimmed = rtrim($relPath, '/');
      if (strpos($trimmed, '/') === FALSE) {
        $items[$relPath] = $absPath;
      }
    }
    return $items;
  }

  /**
   * Get a list of directories immediately beneath $url.
   *
   * @param string $url
   *   Ex: 'gs://mybucket/myfolder/foo'
   * @return array
   *   Ex: ['bar/' => 'gs://mybucket/myfolder/foo/bar/']
   */
  public function getDirectories($url) {
    $dirs = [];
    foreach ($this->getChildren($url) as $relPath => $absPath) {
      if ($relPath[strlen($relPath) - 1] !== '/') {
        continue;
      }
      $dirs[$relPath] = $absPath;
    }
    return $dirs;
  }

  /**
   * Get a list of files immediately beneath $url.
   *
   * @param string $url
   *   Ex: 'gs://mybucket/myfolder/foo'
   * @return array
   *   Ex: ['bar.txt' => 'gs://mybucket/myfolder/foo/bar.txt']
   */
  public function getFiles($url) {
    $files = [];
    foreach ($this->getChildren($url) as $relPath => $absPath) {
      if ($relPath[strlen($relPath) - 1] === '/') {
        continue;
      }
      $files[$relPath] = $absPath;
    }
    return $files;
  }

  /**
   * @param string $url
   *   Ex: 'gs://mybucket/myfolder/'
   *   Ex: 'gs://mybucket/myfolder/foo.txt'
   * @return bool
   *   TRUE if the $url actually exists.
   */
  public function exists($url) {
    return in_array($url, $this->getAll(dirname($url)));
  }

  /**
   * @param string $url
   *   Ex: 'gs://mybucket/myfolder/foo.txt'
   * @return StorageObject
   */
  public function createObject($url) {
    list ($bucketName, $path) = $this->parseUrl($url);
    $bucket = $this->storage->bucket($bucketName);
    return $bucket->object($path);
  }

  protected function parseUrl($url) {
    $url = parse_url($url);
    if ($url['scheme'] !== 'gs') {
      throw new \Exception("Invalid URL scheme");
    }
    return [
      $url['host'],
      isset($url['path']) ? ltrim($url['path'], '/') : '',
    ];
  }

  /**
   * @param string $path
   *   Ex: 'foo/bar/whiz/bang';
   * @return array
   *   Ex: ['foo/bar/whiz/', 'foo/bar/', 'foo/']
   */
  protected function ancestors($path) {
    $ancestors = [];
    $path = dirname($path);
    while (!empty($path) && $path !== dirname($path)) {
      $ancestors[] = "$path/";
      $path = dirname($path);
    }
    return $ancestors;
  }

}
