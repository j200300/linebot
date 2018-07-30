<?php 
require('googleCon.php');

if( !empty($_POST['newData']) ) $newData = json_decode($_POST['newData'], true);
else{
    echo json_encode(array("status" => "error"));
    exit(0);
}
// Get the API client and construct the service object.
$googleClient = getClient();
$googleService = new Google_Service_Sheets($googleClient);

$range = "A1:H";
$valueRange= new Google_Service_Sheets_ValueRange();
$valueRange->setValues(["values" => $newData]); 
$conf = ["valueInputOption" => "RAW"];
$ins = ["insertDataOption" => "INSERT_ROWS"];
$response = $googleService->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf, $ins);

echo json_encode($response);
?>