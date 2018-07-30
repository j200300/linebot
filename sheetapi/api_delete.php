<?php 
require('googleCon.php');

if( !empty($_POST['id']) ) $id = $_POST['id'];
else{
    echo json_encode(array("status" => "error"));
    exit(0);
}

// Get the API client and construct the service object.
$googleClient = getClient();
$googleService = new Google_Service_Sheets($googleClient);

$range = '訂位!A'.$id.':H'.$id;
$response = $googleService->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

$newData = array();
if( !empty($values[0][0]) ) $newData[0] = $values[0][0];
else $newData[0] = "";
if( !empty($values[0][1]) ) $newData[1] = $values[0][1];
else $newData[1] = "";
if( !empty($values[0][2]) ) $newData[2] = $values[0][2];
else $newData[2] = "";
if( !empty($values[0][3]) ) $newData[3] = $values[0][3];
else $newData[3] = "";
if( !empty($values[0][4]) ) $newData[4] = $values[0][4];
else $newData[4] = "";
if( !empty($values[0][5]) ) $newData[5] = $values[0][5];
else $newData[5] = "";
if( !empty($values[0][6]) ) $newData[6] = $values[0][6];
else $newData[6] = "";
$newData[7] = "是";

$range = "A".$id.":H".$id;
$valueRange= new Google_Service_Sheets_ValueRange();
$valueRange->setValues(["values" => $newData]); 
$conf = ["valueInputOption" => "RAW"];
$ins = ["insertDataOption" => "INSERT_ROWS"];
$response = $googleService->spreadsheets_values->update($spreadsheetId, $range, $valueRange, $conf, $ins);
/*
$deleteOperation = array(
    'range' => array(
        'sheetId'   => 0, // <======= This mean the very first sheet on worksheet
        'dimension' => 'ROWS',
        'startIndex'=> ($id-1), //Identify the starting point,
        'endIndex'  => $id //Identify where to stop when deleting
    )
);
$deletable_row[] = new Google_Service_Sheets_Request(
    array('deleteDimension' =>  $deleteOperation)
);
$delete_body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
    'requests' => $deletable_row
));
$response = $googleService->spreadsheets->batchUpdate($spreadsheetId, $delete_body);
*/
echo json_encode($response);
?>