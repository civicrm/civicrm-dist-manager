<?php

namespace CiviDistManagerBundle;

class GitBrowsers {
  /**
   * @param string $subPath
   *   Ex: '/commits' or '/tree'
   * @return string[]
   */
  public static function getAll(string $subPath = '') {
    return [
      'civicrm-core' => "https://github.com/civicrm/civicrm-core" . $subPath,
      'civicrm-packages' => "https://github.com/civicrm/civicrm-packages" . $subPath,
      'civicrm-joomla' => "https://github.com/civicrm/civicrm-joomla" . $subPath,
      'civicrm-backdrop@1.x' => "https://github.com/civicrm/civicrm-backdrop" . $subPath,
      'civicrm-drupal@6.x' => "https://github.com/civicrm/civicrm-drupal" . $subPath,
      'civicrm-drupal@7.x' => "https://github.com/civicrm/civicrm-drupal" . $subPath,
      'civicrm-drupal@8.x' => "https://github.com/civicrm/civicrm-drupal" . $subPath,
      'civicrm-drupal-8' => "https://github.com/civicrm/civicrm-drupal-8" . $subPath,
      'civicrm-standalone' => "https://github.com/civicrm/civicrm-standalone" . $subPath,
      'civicrm-wordpress' => "https://github.com/civicrm/civicrm-wordpress" . $subPath,
    ];
  }

}
