<?php 
header("Access-Control-Allow-Origin: *");

date_default_timezone_set("Asia/Taipei"); // 設定時區為台北時區

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1qJ17PEPVs5LhTODjOcnmylAk5QrqPFBmvTvt20B91qw/edit
$spreadsheetId = '1qJ17PEPVs5LhTODjOcnmylAk5QrqPFBmvTvt20B91qw';

//GOOGLE API
require('../googleapi/vendor/autoload.php');
function getClient()
{
    $googleClient = new Google_Client();
    $googleClient->setApplicationName('Google Sheets API PHP Quickstart');
    $googleClient->setScopes(Google_Service_Sheets::DRIVE);
    $googleClient->setAuthConfig('../credentials.json');
    $googleClient->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = '../token.json';
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $googleClient->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = "4/AACEE0jfeAwz98wFgb2G9D52SjKXkUgT2sT3Pe2hJf1j-83bQSDkCGU";

        // Exchange authorization code for an access token.
        $accessToken = $googleClient->fetchAccessTokenWithAuthCode($authCode);

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $googleClient->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($googleClient->isAccessTokenExpired()) {
        $googleClient->fetchAccessTokenWithRefreshToken($googleClient->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($googleClient->getAccessToken()));
    }
    return $googleClient;
}
?>