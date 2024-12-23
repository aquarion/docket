<?php

/**
 * This is the main index file for the application.
 * php version 7.2
 *
 * @category Personal
 * @package  Radiator
 * @author   "Nicholas Avenell" <nicholas@istic.net>
 * @license  BSD-3-Clause https://opensource.org/license/bsd-3-clause
 * @link     https://docket.hubris.house
 */

define('HOME_DIR', __DIR__ . '/..');

require HOME_DIR . '/vendor/autoload.php';
require HOME_DIR . '/lib/radiator.lib.php';


$client = new Google_Client();
$client->setApplicationName('Radiator');
$client->addScope("https://www.googleapis.com/auth/photoslibrary.readonly");
$client->addScope("https://www.googleapis.com/auth/calendar.readonly");
$client->setAuthConfig(HOME_DIR . '/etc/credentials.json');
$client->setAccessType('offline');

$credentialsPath = HOME_DIR . '/etc/token.json';

$accessToken = json_decode(file_get_contents($credentialsPath), true);

$client->setAccessToken($accessToken);

// Refresh the token if it's expired.
if ($client->isAccessTokenExpired()) {
  $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
  file_put_contents($credentialsPath, json_encode($client->getAccessToken()), LOCK_EX);
}

if ($_GET['request']) {
  $client->setRedirectUri('https://' . $_SERVER['HTTP_HOST'] . '/renew.php');
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
}
