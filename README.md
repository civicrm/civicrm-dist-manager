# civicrm-upgrade-manager

A Symfony project created on September 26, 2016, 9:26 am.

### Setup

```
git clone https://github.com/civicrm/civicrm-upgrade-manager
cd civicrm-upgrade-manager
composer install
php bin/console doctrine:schema:create
```

Next, you'll need credentials for scanning data in the Google Cloud Storage
system. For example:
 * Login to https://console.cloud.google.com/iam-admin/projects
 * Select "Project => CiviCRM"
 * Select "Service Accounts"
 * Click "Create a Service Account"
   * The account does not need any special permissions. It just needs read-access to public resources.
   * You'll want to create key (JSON).
 * Copy the JSON file to `app/config/gcloud.json`

Finally, start the web server, e.g.

```
php bin/console server:run
```

### Route: `GET /check`

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

### Route: `POST /report`

Use this end-point report about the upgrade. Generally, the intent is to
report about each step of the upgrade as it happens. The basic rules of
this end-point are:

 * The data model matches the entity `UpgradeReport`.
 * The `name` is a unique identifier for this upgrade-invocation. (Suggestion: Use a random number with 128-bits of entropy.)
 * The `name` and `siteId` are required on all requests. (For updates, the `name` identifies the updated recorded, and the `siteId` authenticates.)
 * The fields `reporter`, `revision` and `downloadUrl` should be included as part of the first submission.
 * The fields `status` and `stage` are autocomputed.
 * All other fields can be submitted once.
 * Once written, fields are immutable.

### Putting it together (pseudocode)

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
