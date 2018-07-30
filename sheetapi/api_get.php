<?php 
require('googleCon.php');
// Get the API client and construct the service object.
$googleClient = getClient();
$googleService = new Google_Service_Sheets($googleClient);

$range = '訂位!A2:H';
$response = $googleService->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

$date = NULL;
if( !empty($_GET['date']) ){
    $date = date("Y/n/j", strtotime($_GET['date']));
}

$month = NULL;
if( !empty($_GET['month']) ){
    $month = $_GET['month'];
    $month = date("Y/n", strtotime($month));
}

$id = NULL;
if( isset($_GET['id']) ){
    $id = $_GET['id'];
}

$bookingData = array();
for( $i = 0; $i < count($values); $i++ ){
    if( empty($values[$i][7]) ){
        $data = array( "id" => ($i+2) );
        if( !empty($values[$i][0]) ) $data['date'] = $values[$i][0];
        else $data['date'] = "";
        if( !empty($values[$i][1]) ) $data['time'] = $values[$i][1];
        else $data['time'] = "";
        if( !empty($values[$i][2]) ) $data['table'] = $values[$i][2];
        else $data['table'] = "";
        if( !empty($values[$i][3]) ) $data['name'] = $values[$i][3];
        else $data['name'] = "";
        if( !empty($values[$i][4]) ) $data['price'] = $values[$i][4];
        else $data['price'] = "";
        if( !empty($values[$i][5]) ) $data['cellphone'] = $values[$i][5];
        else $data['cellphone'] = "";
        if( !empty($values[$i][6]) ) $data['note'] = $values[$i][6];
        else $data['note'] = "";

        array_push($bookingData, $data);
    }
}

usort($bookingData, function ($a, $b){
    if( strcmp($a['date'], $b['date']) == 0 ){
        if( strcmp($a['time'], $b['time']) == 0 ){
            return strcmp($a['table'], $b['table']);
        }
        else{
            return strcmp($a['time'], $b['time']);
        }
    }
    else{
        return strcmp($a['date'], $b['date']);
    }
});

$returnData = array();
for( $i = 0; $i < count($bookingData); $i++ ){
    $data = $bookingData[$i];

    $addThis = false;
    if( $month != NULL ){
        if( strpos($data['date'], $month) !== false ) $addThis = true;
    }
    else if( $date != NULL ){
        if( $data['date'] == $date ) $addThis = true;
    }
    else{
        $addThis = true;
    }

    if( $id != NULL ){
        if( $data['id'] == $id ) $returnData = $data;
    }
    else if( $addThis ){
        //日期格式 Y/n/j g:i
        if( !isset($returnData[$data['date']]) ) $returnData[$data['date']] = array("noon" => array(), "night" => array());

        $timeArr = explode(":", $data['time']);
        //15點後=晚上
        if( (int)$timeArr[0] > 15 || ((int)$timeArr[0] == 15 && (int)$timeArr[0] > 0) ){
            array_push($returnData[$data['date']]["night"], $data);
        }
        else{
            array_push($returnData[$data['date']]["noon"], $data);
        }
    }
}

if( count($returnData) == 0 ){
    echo json_encode(array("status" => "null"), 256);
}
else{
    echo json_encode($returnData, 256);
}
?>