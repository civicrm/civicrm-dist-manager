<?php
namespace CiviDistManagerBundle;

class CmsMap {

  /**
   * @return array
   *   Array(string $fileSnippet => string $ufName).
   */
  public static function getMap() {
    return array(
      'backdrop' => 'Backdrop',
      'backdrop-unstable' => 'Backdrop',
      'drupal' => 'Drupal',
      'drupal6' => 'Drupal6',
      'wordpress' => 'WordPress',
      'wporg' => 'WordPress',
      'joomla' => 'Joomla',
      'joomla-alt' => 'Joomla',
      'l10n' => 'L10n',
      'starterkit' => 'Drupal',
    );
  }

}
