# civicrm-dist-manager

This application manages the `civicrm.org` distribution/download frontend
service, including redirects and reporting.

### Setup: Baseline (*Option 1, nix-shell*)

Install system requirements:

* [nix package manager](https://nixos.org/download)

Then:

```
git clone https://github.com/civicrm/civicrm-dist-manager distmgr
cd civicrm-dist-manager
nix-shell
composer install
# Optional: If you don't want to use localhost:8000, then edit .loco/loco.yml.
loco run
```

### Setup: Baseline (*Option 2, manual*)

Install system requirements:

* PHP 7.4
* composer
* nginx

Then:

```
git clone https://github.com/civicrm/civicrm-dist-manager
cd civicrm-dist-manager
composer install
```

Finally, add this new folder to your `nginx` configuration. See `nginx/site-example.conf` and `nginx/common.conf` for recommendations.

### Setup: Retrieve list of automated builds

To display automated builds (such as nightlies and RC's), you'll need
credentials for Google Cloud Storage system.

If you do not have access to CiviCRM's Google Cloud Storage, then ask
someone on the civicrm-infra team for a read-access key.

If you do have access to Google Cloud Storage, then you can create an
account:

 * Login to https://console.cloud.google.com/iam-admin/projects
 * Select "Project => CiviCRM"
 * Select "Service Accounts"
 * Click "Create a Service Account"
   * The account does not need any special permissions. It just needs read-access to public resources.
   * You'll want to create key (JSON).
 * Copy the JSON file to `app/config/gcloud.json`


### Test Suite

The tests are implemented with PHPUnit. Simply go to the project root and run:

```
phpunit4
```

### Route: `GET /civicrm-{version}-{cms}.{ext}` (Redirect)

This allows you download a specific version of CiviCRM.

It works a bit different from normal Symfony routing -- the file
`app/LegacyRouter.php` is hardwired into the `AppKernel`. There's
probably a better way to do this.

### Route: `GET /latest` (HTML)

Display a web page listing the (synthetic) download links.

### Route: `GET /latest/civicrm-{stability}-{cms}.{ext}` (Redirect)

Use this end-point to download the latest stable, rc, or nightly archive.

For example:

 * `http://localhost/latest/civicrm-NIGHTLY-drupal6.tar.gz`
 * `http://localhost/latest/civicrm-STABLE-drupal.tar.gz`
 * `http://localhost/latest/civicrm-STABLE-wordpress.zip`
 * `http://localhost/latest/civicrm-STABLE-standalone.zip`
 * `http://localhost/latest/civicrm-RC-joomla.zip`


### Route: `GET /latest/branch/{branch}/{ts}/civicrm-{stability}-{cms}-{ts}.{ext}` (Redirect)

Use this end-point to download a particular build of a particular branch.

For example:

 * `http://localhost/latest/branch/master/201801140252/civicrm-4.7.31-drupal-201801140252.tar.gz`
 * `http://localhost/latest/branch/4.6/201801140252/civicrm-4.6.35-wordpress-201801140252.zip`

### Route: `GET /esr/(.*)` (HTML or Redirect)

Browse the extended security release folder.

Directories are displayed as HTML pages; files are provided as authenticated
redirects.

### Route: `GET /check` (Web service)

Use this end-point to check for metadata about an upgrade
based on a desired `stability` level (`nightly`, `rc`, or `stable`).

For example:

 * `http://localhost/check?stability=nightly` will return a list of tarballs
   produced for the `master` branch. These are not intended for production use.
 * `http://localhost/check?stability=stable` will return a list of tarballs
   for the most recent release. Thse are intended for production use.
 * `http://localhost/check?stability=rc` will return a list of tarballs
   for the most recent release-candidate. (Alternatively, if the RC has been
   superceded by the final/stable release, then it will return that.)
