<?PHP

$scopes = ['https://www.googleapis.com/auth/photoslibrary.readonly', 'https://www.googleapis.com/auth/calendar.readonly'];


use Google\Auth\Credentials\UserRefreshCredentials;

function getGoogleCreds(){

    global $scopes;

    $token  = json_decode(file_get_contents(HOME_DIR . '/etc/token.json'));
    $creds  = json_decode(file_get_contents(HOME_DIR . '/etc/credentials.json'));

    $access = [
        'client_id'     => $creds->installed->client_id,
        'refresh_token' => $token->refresh_token,
        'client_secret' =>  $creds->installed->client_secret,
    ];

    return new UserRefreshCredentials($scopes, $access );


}
/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Radiator');
    $client->addScope("https://www.googleapis.com/auth/photoslibrary.readonly");
    $client->addScope("https://www.googleapis.com/auth/calendar.readonly");
    $client->setAuthConfig(HOME_DIR . '/etc/credentials.json');
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = HOME_DIR . '/etc/token.json';
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } elseif(php_sapi_name() == 'cli') {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            throw new Exception(join(', ', $accessToken));
        }

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken), LOCK_EX);
        printf("Credentials saved to %s\n", $credentialsPath);
    } else {
        header('HTTP/1.1 500 - Something Bad Happened');
        print "Token not valid, revalidate";
        exit(5);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()), LOCK_EX);
    }
    return $client;
}
