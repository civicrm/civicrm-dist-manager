# civicrm-dist-manager

This application manages the `civicrm.org` distribution/download frontend
service, including redirects and reporting.

### Setup: Baseline

```
git clone https://github.com/civicrm/civicrm-upgrade-manager
cd civicrm-upgrade-manager
composer install
php bin/console doctrine:schema:create
php bin/console server:run
```

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


### Setup: httpd redirects

Some simple redirects are implemented at the httpd level.  See
`nginx/site-example.conf` and `nginx/common.conf`.

These are required for correctly providing service, but they are not
required for local development of the PHP logic.

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

### Route: `POST /report` (Web service)

Use this end-point report about the upgrade.  Generally, the intent is to
report about each step of the upgrade as it happens, and the data-model
matches the entity `UpgradeReport`.  Submissions should include some
combination of these fields:

 * Common (_required for all requests_)
   * `siteId` (`string`): A stable/long-term site id. (Reused across many upgrades.) Aim for 16-32 bytes. (Note: `siteId` is kept private.)
   * `name` (`string`: A unique identifier for this upgrade-invocation. Aim for 32 bytes. (Suggestion: Use a random number with 128-bits of entropy.) (Note: The `name` may be discussed among various developers/contributors.)
 * Create report (_required for first submission_)
   * `cvVersion` (`string`, write-once): The revision of `cv` (e.g. `0.4.5`).
   * `revision` (`string`, write-once): The name of the precise revision being downloaded (e.g. `4.7.16-201701020304`).
   * `reporter` (`string`, write-once): The email address of the site administrator.
   * `downloadUrl` (`string`, write-once): The URL of the tarball being downloaded.
 * Complete the download stage
   * `downloaded` (`int`, write-once): Time at which download completed. Seconds since epoch.
 * Complete the extraction stage
   * `extracted` (`int`, write-once): Time at which extraction completed. Seconds since epoch.
 * Complete the upgrade stage
   * `upgraded` (`int`, write-once): Time at which extraction completed. Seconds since epoch.
   * `upgradeReport` (`string`, write-once): JSON-encoded data about the upgrade. (Generally, `System.get`.)
 * Finalize the main upgrade report
   * `finished` (`int`, write-once): Time at which extraction completed. Seconds since epoch.
   * `finishReport` (`string`, write-once): Report about the system's post-upgrade configuration. (Generally, `System.get`.) JSON-encoded.
 * Post an advisory test report about additional tests
   * `testReport` (`string`, write-once): Optional, open-ended report about custom inhouse tests. JSON-encoded data.
 * Report a fatal problem with the upgrade
   * `failed` (`int`, write-once): Time at which the failure arose. Seconds since epoch.
   * `problem` (`string`, write-once):

### Use Case: Performing an automated upgrade (pseudocode)

```
## Find or create an ID for this site
$site_id = "my-example-staging-site";

## Generate a unique ID for this upgrade run
$name = md5(rand() . rand() . rand() . uniqid());

## What is the appropriate tarball?
$check = json_decode(GET http://localhost/check?stability=rc)

## Report: The upgrade is starting
POST http://localhost/report
  name => $name
  siteId => $site_id
  reporter => me@example.org
  revision => $check['rev']
  cvVersion => $versionString,
  downloadUrl => $check['tar']['Drupal']
  started => time()
  startReport => civicrm_api3('System', 'get')

## Download the TAR ball
GET ($check['tar']['Drupal'])

## Report: The download completed
POST http://localhost/report
  name => $name
  siteId => $site_id
  downloaded => time()

## Put the code into your build
tar xvzf civicrm-X.Y.Z.tar.gz

## Report: The extract completed
POST http://localhost/report
  name => $name
  siteId => $site_id
  extracted => time()

## Upgrade the DB schema
$upgrade = new CRM_Upgrade_Headless()
$messages = $upgrade->run()

## Report: The DB schema upgrade completed
POST http://localhost/report
  name => $name
  siteId => $site_id
  upgraded => time()
  upgradeReport => $messages

## Report: The upgrade finished
POST http://localhost/report
  name => $name
  siteId => $site_id
  finished => time()
  finishReport => civicrm_api3('System', 'get')
```

If at any point there is an error, then report that:

```
POST http://localhost/report
  name => $name
  siteId => $site_id
  failed => time()
  problem => $message
```
