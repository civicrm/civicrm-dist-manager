<?php

namespace CiviDistManagerBundle;

use Google\Cloud\Storage\StorageClient;

class GCloudFactory {

  /**
   * @param $credFile
   * @param $projectId
   * @return \Google\Cloud\Storage\StorageClient
   */
  public static function createStorageClient($credFile, $projectId) {
    if (file_exists($credFile)) {
      putenv("GOOGLE_APPLICATION_CREDENTIALS=$credFile");
    }
    $storage = new StorageClient([
      'projectId' => $projectId,
    ]);
    return $storage;
  }

  /**
   * @param \Google\Cloud\Storage\StorageClient $client
   * @param string $bucketName
   * @return \Google\Cloud\Storage\Bucket
   */
  public static function createBucket(StorageClient $client, $bucketName) {
    return $client->bucket($bucketName);
  }

}
