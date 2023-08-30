<?php
namespace CiviDistManagerBundle;

class LegacyRedirect {

  public static function pickUrl($path) {
    // ex: /civicrm-4.7.10-drupal.tar.gz
    // ex: https://storage.googleapis.com/civicrm/civicrm-stable/4.7.12/civicrm-4.7.12-backdrop-unstable.tar.gz
    $base = "https://storage.googleapis.com/civicrm";
    if (preg_match(";^/civicrm-([0-9a-z\.]+)(-drupal|-joomla|-wordpress|-l10n|-back|-standalone|-starter|\.MD5|\.SHA256|\.json);", $path, $matches)) {
      $version = $matches[1];
      if (preg_match(';(alpha|beta);', $version)) {
        return "$base/civicrm-testing/$version$path";
      }
      else {
        return "$base/civicrm-stable/$version$path";
      }
    }
    return NULL;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|NULL
   */
  public static function onHandle(\Symfony\Component\HttpFoundation\Request $request) {
    $url = self::pickUrl($request->getPathInfo());
    if ($url) {
      return new \Symfony\Component\HttpFoundation\RedirectResponse($url, 302);
    }
    return NULL;
  }

}
