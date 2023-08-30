<?php

namespace CiviDistManagerBundle;

class GitBrowsers {
  public static function getAll() {
    return [
      'civicrm-core' => 'https://github.com/civicrm/civicrm-core/commits',
      'civicrm-packages' => 'https://github.com/civicrm/civicrm-packages/commits',
      'civicrm-joomla' => 'https://github.com/civicrm/civicrm-joomla/commits',
      'civicrm-backdrop@1.x' => 'https://github.com/civicrm/civicrm-backdrop/commits',
      'civicrm-drupal@6.x' => 'https://github.com/civicrm/civicrm-drupal/commits',
      'civicrm-drupal@7.x' => 'https://github.com/civicrm/civicrm-drupal/commits',
      'civicrm-drupal@8.x' => 'https://github.com/civicrm/civicrm-drupal/commits',
      'civicrm-drupal-8' => 'https://github.com/civicrm/civicrm-drupal-8/commits',
      'civicrm-standalone' => 'https://github.com/civicrm/civicrm-standalone/commits', // Not currently used, but who knows
      'civicrm-wordpress' => 'https://github.com/civicrm/civicrm-wordpress/commits',
    ];
  }

}
