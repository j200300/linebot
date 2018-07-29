<?php 
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
// Get the API client and construct the service object.
$googleClient = getClient();
$googleService = new Google_Service_Sheets($googleClient);
// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1qJ17PEPVs5LhTODjOcnmylAk5QrqPFBmvTvt20B91qw/edit
$spreadsheetId = '1qJ17PEPVs5LhTODjOcnmylAk5QrqPFBmvTvt20B91qw';
$range = '訂位!A2:H';
$response = $googleService->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

$bookingData = array();
for( $i = 0; $i < count($values); $i++ ){
   $data = array();
    if( !empty($values[$i][0]) ) $data['date'] = $values[$i][0];
    else $data['date'] = "";
    if( !empty($values[$i][1]) ) $data['time'] = $values[$i][1];
    else $data['time'] = "";
    if( !empty($values[$i][2]) ) $data['table'] = $values[$i][2];
    else $data['date'] = "";
    if( !empty($values[$i][3]) ) $data['name'] = $values[$i][3];
    else $data['name'] = "";
    if( !empty($values[$i][4]) ) $data['price'] = $values[$i][4];
    else $data['price'] = "";
    if( !empty($values[$i][5]) ) $data['cellphone'] = $values[$i][5];
    else $data['cellphone'] = "";
    if( !empty($values[$i][6]) ) $data['note'] = $values[$i][6];
    else $data['note'] = "";
    if( !empty($values[$i][7]) ) $data['staff'] = $values[$i][7];
    else $data['staff'] = "";

    array_push($bookingData, $data);
}

return json_encode($bookingData, 256);
?>