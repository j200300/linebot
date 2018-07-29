<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

date_default_timezone_set("Asia/Taipei"); // 設定時區為台北時區
//line setting
require_once('./LINEBotTiny.php');
$channelAccessToken = 'qXvWIHCqpbqFp9i0Bcfo9sQcWBGYPLiHJA5ipBU8vgTaf03N6s/g2MI+k9mD6ukl3rXan1DqeIgoIifbCuVsL0G2gBBDJ+mx9wjnXWG4SPpRi6c8SfL+8GFbp77JBspiTiDQDz5w2KGCEC86GBePowdB04t89/1O/w1cDnyilFU=';
$channelSecret = '23eea8afd64654d2d35f131d439ffcbd';
$lineClient = new LINEBotTiny($channelAccessToken, $channelSecret);
//GOOGLE API
require('googleapi/vendor/autoload.php');
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
function getClient()
{
    $googleClient = new Google_Client();
    $googleClient->setApplicationName('Google Sheets API PHP Quickstart');
    $googleClient->setScopes(Google_Service_Sheets::DRIVE);
    $googleClient->setAuthConfig('credentials.json');
    $googleClient->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = 'token.json';
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
    //日期格式 Y/n/j g:i
    if( !isset($bookingData[$values[$i][0]]) ) $bookingData[$values[$i][0]] = array();

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

    array_push($bookingData[$values[$i][0]], $data);
}

foreach ($lineClient->parseEvents() as $event) {
    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text':
                    if( substr($message['text'], 0, 1) == "+" ){
                        $range = "A1:H";
                        $valueRange= new Google_Service_Sheets_ValueRange();
                        $valueRange->setValues(["values" => ['2018/07/20', '15:00', "桌位", "姓名", "價位", "電話"]]); 
                        $conf = ["valueInputOption" => "RAW"];
                        $ins = ["insertDataOption" => "INSERT_ROWS"];
                        $response = $googleService->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf, $ins);
                        $lineClient->replyMessage(array(
                            'replyToken' => $event['replyToken'],
                            'messages' => array(
                                array(
                                    'type' => 'text',
                                    'text' => $message['text'].json_encode($response, 256)
                                )
                            )
                        ));
                    }
                    else{
                        //查詢
                        $result = array(array(
                            'type' => 'text',
                            'text' => "查無資料"
                        ));

                        $date = "";
                        if( strlen($message['text']) == 4 ){
                            $year = date("Y");
                            $date = date("Y/n/j", strtotime($year."/".substr($message['text'], 0, 2)."/".substr($message['text'], 2, 2)));
                        }
                        else if( strlen($message['text']) == 6 ){
                            $date = date("Y/n/j", strtotime("20".substr($message['text'], 0, 2)."/".substr($message['text'], 2, 2)."/".substr($message['text'], 4, 2)));
                        }
                        else if( strlen($message['text']) == 8 ){
                            $date = date("Y/n/j", strtotime(substr($message['text'], 0, 4)."/".substr($message['text'], 4, 2)."/".substr($message['text'], 6, 2)));
                        }

                        if( isset($bookingData[$date]) ){
                            $result = array();
                            for( $i = 0; $i < count($bookingData[$date]); $i++ ){
                                array_push($result, array(
                                    'type' => 'text',
                                    'text' => $bookingData[$date][$i]['time']." ".$bookingData[$date][$i]['table']." ".$bookingData[$date][$i]['name']
                                ));
                            }
                        }

                        $lineClient->replyMessage(array(
                            'replyToken' => $event['replyToken'],
                            'messages' => $result
                        ));
                    }
                    
                    break;
                default:
                    error_log("Unsupporeted message type: " . $message['type']);
                    break;
            }
            break;
        default:
            error_log("Unsupporeted event type: " . $event['type']);
            break;
    }
};
?>