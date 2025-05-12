<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $headers = [
        'A1' => 'Employee Code',
        'B1' => 'Base Salary',
        'C1' => 'Allowances',
        'D1' => 'Bonuses',
        'E1' => 'Deductions',
        'F1' => 'Pay Period Start',
        'G1' => 'Pay Period End',
        'H1' => 'Notes'
    ];

    // Set header values and styles
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    // Style the header row
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ]
    ];

    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(15);  // Employee Code
    $sheet->getColumnDimension('B')->setWidth(15);  // Base Salary
    $sheet->getColumnDimension('C')->setWidth(15);  // Allowances
    $sheet->getColumnDimension('D')->setWidth(15);  // Bonuses
    $sheet->getColumnDimension('E')->setWidth(15);  // Deductions
    $sheet->getColumnDimension('F')->setWidth(20);  // Pay Period Start
    $sheet->getColumnDimension('G')->setWidth(20);  // Pay Period End
    $sheet->getColumnDimension('H')->setWidth(30);  // Notes

    // Add data validation for Pay Period Start and End
    $validation = $sheet->getCell('F2')->getDataValidation();
    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE);
    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(false);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setShowDropDown(true);
    $validation->setErrorTitle('Input error');
    $validation->setError('Please enter a valid date');
    $validation->setPromptTitle('Select date');
    $validation->setPrompt('Please select a date');

    // Apply validation to range
    $sheet->setDataValidation('F2:F1000', $validation);
    $sheet->setDataValidation('G2:G1000', $validation);

    // Add sample data
    $sampleData = [
        ['EMP001', 5000000, 1000000, 500000, 0, '2024-03-01', '2024-03-31', 'March 2024 Salary'],
        ['EMP002', 6000000, 1200000, 600000, 0, '2024-03-01', '2024-03-31', 'March 2024 Salary']
    ];

    $row = 2;
    foreach ($sampleData as $data) {
        $sheet->fromArray($data, null, "A$row");
        $row++;
    }

    // Style the data rows
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];

    $sheet->getStyle('A2:H' . ($row - 1))->applyFromArray($dataStyle);

    // Set number format for salary columns
    $sheet->getStyle('B2:E' . ($row - 1))->getNumberFormat()
        ->setFormatCode('#,##0');

    // Set date format for date columns
    $sheet->getStyle('F2:G' . ($row - 1))->getNumberFormat()
        ->setFormatCode('yyyy-mm-dd');

    // Add instructions
    $sheet->setCellValue('A' . ($row + 2), 'Instructions:');
    $sheet->setCellValue('A' . ($row + 3), '1. Employee Code must match existing employee codes in the system');
    $sheet->setCellValue('A' . ($row + 4), '2. All salary amounts should be in VND');
    $sheet->setCellValue('A' . ($row + 5), '3. Pay Period dates must be valid dates');
    $sheet->setCellValue('A' . ($row + 6), '4. Leave cells empty if no value is needed');

    // Style instructions
    $sheet->getStyle('A' . ($row + 2))->getFont()->setBold(true);
    $sheet->getStyle('A' . ($row + 3) . ':A' . ($row + 6))->getFont()->setItalic(true);

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="payroll_template.xlsx"');
    header('Cache-Control: max-age=0');

    // Save file to PHP output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    error_log("Export template error: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error generating template: ' . $e->getMessage()
    ]);
} 