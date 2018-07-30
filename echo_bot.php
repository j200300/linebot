<?php
if(!isset($_SESSION)) session_start();
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

foreach ($lineClient->parseEvents() as $event) {
    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text':
                    $userData = $lineClient->getProfile($event['source']['userId']);
                    
                    if( substr($message['text'], 0, 1) == "+" ){
                        $range = "A1:H";
                        $valueRange= new Google_Service_Sheets_ValueRange();
                        $valueRange->setValues(["values" => ['2018/07/20', '15:00', "桌位", "姓名", "價位", "電話"]]); 
                        $conf = ["valueInputOption" => "RAW"];
                        $ins = ["insertDataOption" => "INSERT_ROWS"];
                        $response = $googleService->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf, $ins);
                        $_SESSION['test'] = json_encode($event, 256);
                        $result = array(
                            array(
                                'type' => 'text',
                                'text' => $message['text'].json_encode($_SESSION, 256)
                            )
                        );
                    }
                    else{
                        $date = "";
                        if( $message['text'] == "#今日訂席" ){
                            $date = date("Y/n/j");
                        }
                        else if( strlen($message['text']) == 4 ){
                            $year = date("Y");
                            $date = date("Y/n/j", strtotime($year."/".substr($message['text'], 0, 2)."/".substr($message['text'], 2, 2)));
                        }
                        else if( strlen($message['text']) == 6 ){
                            $date = date("Y/n/j", strtotime("20".substr($message['text'], 0, 2)."/".substr($message['text'], 2, 2)."/".substr($message['text'], 4, 2)));
                        }
                        else if( strlen($message['text']) == 8 ){
                            $date = date("Y/n/j", strtotime(substr($message['text'], 0, 4)."/".substr($message['text'], 4, 2)."/".substr($message['text'], 6, 2)));
                        }

                        $result = bookingMessage( $date );
                    }
                    
                    $response = $lineClient->replyMessage(array(
                        'replyToken' => $event['replyToken'],
                        'messages' => $result
                    ));
                    
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

function bookingMessage( $date ){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://threeya.azurewebsites.net/linebot/sheetapi/api_get.php?date=".$date);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $getResult = curl_exec($ch);
    $decodeResult = json_decode($getResult, true);
    $bookingData = $decodeResult[$date];
    curl_close($ch);

    //查詢
    $result = array(array(
        'type' => 'text',
        'text' => "查無資料"
    ));
    if( !isset($bookingData['status']) ){
        $returnData = array(array(
            'type' => 'text',
            'text' => $userData['displayName']." 為您查詢".date("Y年n月j日", strtotime($date))
        ));
        $countCarousel = 1;

        $returnMessage = array(
            'type' => 'flex',
            'altText' => 'test',
            'contents' => array(
                'type' => 'carousel',
                'contents' => array()
            )
        );

        $countMessage = 1;
        foreach( $bookingData as $period => $bookingList ){
            for( $i = 0; $i < count($bookingList); $i++ ){
                $bookingContent = array();
                if( !empty($bookingList[$i]['time']) ){
                    array_push($bookingContent, array(
                        'type' => 'text',
                        'text' => "時間：".$bookingList[$i]['time'],
                        'weight' => "bold",
                        "size" => "xl",
                        'wrap' => true
                    ));
                    array_push($bookingContent, array(
                        'type' => 'separator',
                        'margin' => 'md'
                    ));
                }
                if( !empty($bookingList[$i]['table']) || !empty($bookingList[$i]['price']) ){
                    array_push($bookingContent, array(
                        'type' => 'text',
                        'text' => "桌位：".$bookingList[$i]['table']." 價位：".$bookingList[$i]['price'],
                        "margin" => "md",
                        'weight' => "bold",
                        "size" => "md",
                        'wrap' => true
                    ));
                    array_push($bookingContent, array(
                        'type' => 'separator',
                        'margin' => 'md'
                    ));
                }
                if( !empty($bookingList[$i]['name']) ){
                    array_push($bookingContent, array(
                        'type' => 'text',
                        'text' => "名稱：".$bookingList[$i]['name'],
                        "margin" => "md",
                        'weight' => "bold",
                        "size" => "md",
                        'wrap' => true
                    ));
                }
                if( !empty($bookingList[$i]['cellphone']) ){
                    array_push($bookingContent, array(
                        'type' => 'text',
                        'text' => "電話：".$bookingList[$i]['cellphone'],
                        "margin" => "md",
                        'weight' => "bold",
                        "size" => "md",
                        'wrap' => true
                    ));
                }
                if( !empty($bookingList[$i]['note']) ){
                    array_push($bookingContent, array(
                        'type' => 'separator',
                        'margin' => 'md'
                    ));
                    array_push($bookingContent, array(
                        'type' => 'text',
                        'text' => "備註：".$bookingList[$i]['note'],
                        "margin" => "md",
                        'wrap' => true
                    ));
                }

                if( $countMessage > 10 ){
                    array_push($returnData, $returnMessage);
                    $returnMessage['contents']['contents'] = array();

                    $countMessage = 0;
                    $countCarousel++;
                }
                if( $countMessage <= 10 ){
                    array_push($returnMessage['contents']['contents'], array(
                        'type' => 'bubble',
                        'body' => array(
                            'type' => 'box',
                            'layout' => 'vertical',
                            'contents' => $bookingContent
                        )
                    ));

                    $countMessage++;
                }
            }
        }
        if( count($returnMessage['contents']['contents']) > 0 ) array_push($returnData, $returnMessage);
        $result = $returnData;
    }

    return $result;
}
?>