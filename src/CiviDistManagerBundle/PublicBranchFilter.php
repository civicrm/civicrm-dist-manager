<?php
namespace CiviDistManagerBundle;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class PublicBranchFilter
 * @package CiviDistManagerBundle
 *
 * Filters the list of available builds to only show builds based on
 * one of these branches:
 * - Current stable branch
 * - Current RC branch (i.e. newer than stable)
 * - Current master branch (i.e. newer than stable)
 * - Whitelisted LTS/ESR branch
 */
class PublicBranchFilter implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'build_repository.getFiles' => 'onGetFiles',
    ];
  }

  /**
   * @var \Doctrine\Common\Cache\Cache
   */
  protected $cache;

  /**
   * @var array
   *   These branches should be treated as public, even if they're not the
   *   current stable/rc/master
   */
  protected $whitelist = ['4.6'];

  /**
   * @var string
   *   The URL for looking up the current stable version.
   *   This is used to infer stable/rc/master versions.
   */
  protected $versionsUrl = 'https://latest.civicrm.org/stable.php';

  protected $cacheTtl = 3600;

  /**
   * PublicStableFilter constructor.
   * @param \Doctrine\Common\Cache\Cache $cache
   */
  public function __construct(\Doctrine\Common\Cache\Cache $cache) {
    $this->cache = $cache;
  }

  public function onGetFiles(GenericEvent $e) {
    $stableBranch = $this->getStableBranch();
    $e['files'] = array_values(array_filter($e['files'], function($file) use ($stableBranch) {
      return version_compare($file['version'], $stableBranch, '>=') || in_array($file['branch'], $this->whitelist);
    }));
  }

  protected function getStableBranch() {
    $cacheKey = __CLASS__ . '::stableBranch';
    if (!$this->cache->contains($cacheKey)) {
      $version = file_get_contents($this->versionsUrl);
      if (preg_match(';^([0-9]+\.[0-9]+)\.;', $version, $matches)) {
        $this->cache->save($cacheKey, $matches[1], $this->cacheTtl);
      }
      else {
        throw new \Exception("Failed to parse stable branch from " . $this->versionsUrl);
      }
    }
    return $this->cache->fetch($cacheKey);
  }


}