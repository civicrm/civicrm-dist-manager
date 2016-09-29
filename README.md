# civicrm-upgrade-manager

A Symfony project created on September 26, 2016, 9:26 am.

### Setup

```
git clone https://github.com/civicrm/civicrm-upgrade-manager
cd civicrm-upgrade-manager
composer install
php bin/console server:run
```

### Route: `/check`

Use this end-point to locate the latest upgrade. The route accepts
one mandatory parameter, `stability` (`nightly`, `rc`, or `stable`).

For example:

 * `http://localhost/check?stability=nightly` will return a list of tarballs
   produced for the `master` branch. These are not intended for production use.
 * `http://localhost/check?stability=stable` will return a list of tarballs
   for the most recent release. Thse are intended for production use.
 * `http://localhost/check?stability=rc` will return a list of tarballs
   for the most recent release-candidate. (Alternatively, if the RC has been
   superceded by the final/stable release, then it will return that.)
