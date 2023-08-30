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
