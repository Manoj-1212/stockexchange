<?php
require_once 'vendor/autoload.php';
require_once 'config.php';
  ini_set('max_execution_time', 3000); //300 seconds = 5 minutes

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
  
$url = 'https://api.kite.trade/instruments/NFO';
      
    // Use basename() function to return the base name of file
    $file_name = "instruments.csv";
      
    // Use file_get_contents() function to get the file
    // from url and use file_put_contents() function to
    // save the file by using base name
//file_put_contents($file_name, file_get_contents($url));
  
    $file_mimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      
      
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

        $spreadsheet = $reader->load($file_name);
  
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                echo "INSERT INTO instruments (instrument_token, trading_symbol, name, expiry, lot_size, instrument_type, segment, exchange, created_at, updated_at) SELECT ".$sheetData[$i][0].", '".$sheetData[$i][2]."', '".$sheetData[$i][3]."', '".$sheetData[$i][5]."', ".$sheetData[$i][8].", '".$sheetData[$i][9]."', '".$sheetData[$i][10]."', '".$sheetData[$i][11]."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."' WHERE NOT EXISTS (SELECT 1 FROM instruments WHERE instrument_token=".$sheetData[$i][0].");";
                echo "<br />";
                //$db->query("INSERT INTO instruments (instrument_token, trading_symbol, name, expiry, lot_size, instrument_type, segment, exchange, created_at, updated_at) VALUES (".$sheetData[$i][0].", '".$sheetData[$i][2]."', '".$sheetData[$i][3]."', '".$sheetData[$i][5]."', ".$sheetData[$i][8].", '".$sheetData[$i][9]."', '".$sheetData[$i][10]."', '".$sheetData[$i][11]."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')");
            }
        }
        echo "Records inserted successfully.";
    /*

    $autoFilter = $spreadsheet->getActiveSheet()->getAutoFilter();
        $columnFilter = $autoFilter->getColumn('K');
        $columnFilter->setFilterType(
            PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column::AUTOFILTER_FILTERTYPE_FILTER
        );

        $columnFilter->createRule()
    ->setRule(
        \PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column\Rule::AUTOFILTER_COLUMN_RULE_EQUAL,
        'NFO-FUT'
    );

foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row) {
    if ($spreadsheet->getActiveSheet()
        ->getRowDimension($row->getRowIndex())->getVisible()) {
        echo '    Row number - ' , $row->getRowIndex() , ' ';
        echo $spreadsheet->getActiveSheet()
            ->getCell(
                'C'.$row->getRowIndex()
            )
            ->getValue(), ' ';
        echo $spreadsheet->getActiveSheet()
            ->getCell(
                'D'.$row->getRowIndex()
            )->getFormattedValue(), ' ';
        echo PHP_EOL;
    }
}

*/
?>