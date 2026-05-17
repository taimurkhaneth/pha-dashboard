<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('Kurri Houses Updated Final.xlsx');
$worksheet = $spreadsheet->getActiveSheet();

$data = [];
$i = 0;
foreach ($worksheet->getRowIterator(2, 4) as $row) {
    $rowData = [];
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    foreach ($cellIterator as $cell) {
        $rowData[] = $cell->getValue();
    }
    $data[] = $rowData;
    $i++;
}

echo json_encode($data, JSON_PRETTY_PRINT);
