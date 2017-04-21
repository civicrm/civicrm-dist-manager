<?php
namespace CiviUpgradeManagerBundle;

class CmsMap {

  /**
   * @return array
   *   Array(string $fileSnippet => string $ufName).
   */
  public static function getMap() {
    return array(
      'backdrop' => 'Backdrop',
      'drupal' => 'Drupal',
      'drupal6' => 'Drupal6',
      'wordpress' => 'WordPress',
      'joomla' => 'Joomla',
      'l10n' => 'L10n',
    );
  }

}