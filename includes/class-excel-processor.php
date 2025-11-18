<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class Excel_Processor
{

    public static function process_upload($file_path)
    {
        try {
            $spreadsheet = IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip first 2 rows (headers) - start from row 3
            array_shift($rows); // Remove row 1
            array_shift($rows); // Remove row 2

            $updated_count = 0;
            $created_count = 0;
            $errors = array();

            foreach ($rows as $index => $row) {
                // ستون‌ها بر اساس ساختار جدید:
                // ستون 1: SKU (کد محصول)
                // ستون 17: قیمت محصول
                // ستون 18: درصد تخفیف
                // ستون 19: موجودی

                $sku = trim($row[0] ?? ''); // ستون 1: SKU
                $regular_price_str = trim($row[16] ?? ''); // ستون 17: قیمت محصول
                $discount_percent_str = trim($row[17] ?? ''); // ستون 18: درصد تخفیف
                $stock_quantity_str = trim($row[18] ?? ''); // ستون 19: موجودی

                // پاک کردن کاماها و تبدیل به عدد
                $regular_price = str_replace(',', '', $regular_price_str);
                $discount_percent = str_replace(',', '', $discount_percent_str);
                $excel_stock = str_replace(',', '', $stock_quantity_str);

                // Validate required fields
                if (empty($sku)) {
                    $errors[] = "ردیف " . ($index + 3) . ": کد SKU نمی‌تواند خالی باشد";
                    continue;
                }

                if (empty($regular_price) || !is_numeric($regular_price)) {
                    $errors[] = "ردیف " . ($index + 3) . ": قیمت نامعتبر برای محصول با SKU '{$sku}'";
                    continue;
                }

                // Calculate sale price only if discount > 0
                $sale_price = null;
                if (!empty($discount_percent) && is_numeric($discount_percent) && $discount_percent > 0 && $discount_percent < 100) {
                    $sale_price = $regular_price * (1 - $discount_percent / 100);
                }

                // Calculate final stock: excel_stock - product_warehouse_limited
                $final_stock = null;
                if (!empty($excel_stock) && is_numeric($excel_stock)) {
                    // Get product_warehouse_limited from product meta
                    global $wpdb;
                    $product_id = $wpdb->get_var($wpdb->prepare("
                        SELECT post_id FROM {$wpdb->postmeta}
                        WHERE meta_key = '_sku' AND meta_value = %s
                        LIMIT 1
                    ", $sku));

                    $warehouse_limited = 0;
                    if ($product_id) {
                        $warehouse_limited = (int) get_post_meta($product_id, 'product_warehouse_limited', true);
                    }

                    $final_stock = max(0, $excel_stock - $warehouse_limited);
                }

                $result = self::update_product_by_sku($sku, $regular_price, $sale_price, $final_stock);

                if ($result === 'updated') {
                    $updated_count++;
                } elseif ($result === 'created') {
                    $created_count++;
                } else {
                    $errors[] = "ردیف " . ($index + 3) . ": " . $result;
                }
            }

            $message = "پردازش کامل شد.\n";
            $message .= "محصولات بروزرسانی شده: {$updated_count}\n";
            $message .= "محصولات جدید ایجاد شده: {$created_count}";

            if (!empty($errors)) {
                $message .= "\n\nخطاها:\n" . implode("\n", $errors);
            }

            return $message;
        } catch (Exception $e) {
            return 'خطا در پردازش فایل: ' . $e->getMessage();
        }
    }

    private static function update_product_by_sku($sku, $regular_price, $sale_price, $stock_quantity)
    {
        try {
            // Find product by SKU
            global $wpdb;

            $product_id = $wpdb->get_var($wpdb->prepare("
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_sku' AND meta_value = %s
                LIMIT 1
            ", $sku));

            if ($product_id) {
                // Update existing product
                /** @disregard */
                $product = wc_get_product($product_id);

                if (!$product) {
                    return "محصول با SKU '{$sku}' یافت نشد";
                }

                $product->set_regular_price($regular_price);

                // Only set sale price if discount > 0, otherwise remove it
                if ($sale_price !== null && $sale_price > 0) {
                    $product->set_sale_price($sale_price);
                } else {
                    $product->set_sale_price(''); // Remove sale price
                }

                if ($stock_quantity !== null && is_numeric($stock_quantity)) {
                    $product->set_stock_quantity($stock_quantity);
                    $product->set_manage_stock(true);
                }

                $product->save();
                return 'updated';
            } else {
                // Create new product (if needed, but according to requirements, we should only update existing)
                return "محصول با SKU '{$sku}' یافت نشد - محصول جدید ایجاد نمی‌شود";
            }
        } catch (Exception $e) {
            return "خطا در بروزرسانی محصول با SKU '{$sku}': " . $e->getMessage();
        }
    }

    public static function generate_sample_file()
    {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('محصولات');

            // Set headers for new structure
            $sheet->setCellValue('A1', 'کد SKU');
            $sheet->setCellValue('B1', 'نام محصول');
            $sheet->setCellValue('C1', 'نوع داده');
            $sheet->setCellValue('D1', 'واحد شمارش');
            $sheet->setCellValue('E1', 'دسته بندی اصلی');
            $sheet->setCellValue('F1', 'دسته بندی فرعی');
            $sheet->setCellValue('G1', 'ارزش افزوده خرید');
            $sheet->setCellValue('H1', 'ارزش افزوده فروش');
            $sheet->setCellValue('I1', 'ارزش افزوده درصد');
            $sheet->setCellValue('J1', 'بارکد دارد یا خیر');
            $sheet->setCellValue('K1', 'شماره فنی');
            $sheet->setCellValue('L1', 'انتخاب ویزیتور');
            $sheet->setCellValue('M1', 'مشخصه ها');
            $sheet->setCellValue('N1', 'طبقه کالا');
            $sheet->setCellValue('O1', 'کنترل سریال');
            $sheet->setCellValue('P1', 'وضعیت فعال یا غیرفعال');
            $sheet->setCellValue('Q1', 'قیمت محصول');
            $sheet->setCellValue('R1', 'درصد تخفیف');
            $sheet->setCellValue('S1', 'موجودی');

            // Style headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '667EEA'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                ],
            ];

            $sheet->getStyle('A1:S1')->applyFromArray($headerStyle);

            // Set column widths
            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];
            $widths = [12, 25, 10, 12, 15, 15, 15, 15, 15, 15, 12, 15, 12, 12, 12, 18, 15, 12, 10];

            foreach ($columns as $index => $column) {
                $sheet->getColumnDimension($column)->setWidth($widths[$index]);
            }

            // Add sample data with new structure
            $sampleData = [
                // نمونه 1: محصول با تخفیف
                ['1001', 'زعفران سرگل یک مثقال پاکت', 'کالا', 'عدد', 'زعفران', 'زعفران', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'True', '9000000', '10', '150'],
                // نمونه 2: محصول بدون تخفیف
                ['1002', 'زعفران سرگل نیم مثقال پاکت', 'کالا', 'عدد', 'زعفران', 'زعفران', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'True', '5000000', '0', '200'],
                // نمونه 3: محصول با تخفیف بالا
                ['1003', 'زعفران سرگل ربع مثقال پاکت', 'کالا', 'عدد', 'زعفران', 'زعفران', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'True', '3000000', '15', '300'],
            ];

            $row = 2;
            foreach ($sampleData as $data) {
                foreach ($data as $colIndex => $value) {
                    $columnLetter = $columns[$colIndex];
                    $sheet->setCellValue($columnLetter . $row, $value);
                }

                // Style data rows
                $dataStyle = [
                    'alignment' => [
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'E2E8F0'],
                        ],
                    ],
                ];

                $sheet->getStyle('A' . $row . ':S' . $row)->applyFromArray($dataStyle);

                $row++;
            }

            // Add empty rows for user input (starting from row 3 as data starts from row 3)
            for ($i = 0; $i < 10; $i++) {
                for ($colIndex = 0; $colIndex < count($columns); $colIndex++) {
                    $columnLetter = $columns[$colIndex];
                    $sheet->setCellValue($columnLetter . $row, '');
                }

                $emptyStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'E2E8F0'],
                        ],
                    ],
                ];

                $sheet->getStyle('A' . $row . ':S' . $row)->applyFromArray($emptyStyle);
                $row++;
            }

            // Add instructions sheet
            $instructionsSheet = $spreadsheet->createSheet();
            $instructionsSheet->setTitle('راهنمای استفاده');

            // Title
            $instructionsSheet->setCellValue('A1', 'راهنمای استفاده از فایل بروزرسانی محصولات');
            $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF667EEA'));
            $instructionsSheet->mergeCells('A1:D1');

            // Column descriptions
            $instructionsSheet->setCellValue('A3', 'توضیحات ستون‌ها:');
            $instructionsSheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);

            $columnData = [
                ['کد SKU', 'الزامی', 'کد منحصر به فرد محصول'],
                ['نام محصول', 'الزامی', 'نام کامل محصول'],
                ['نوع داده', 'الزامی', 'نوع محصول (کالا)'],
                ['واحد شمارش', 'الزامی', 'واحد شمارش (عدد/بسته)'],
                ['دسته بندی اصلی', 'الزامی', 'دسته‌بندی اصلی محصول'],
                ['دسته بندی فرعی', 'الزامی', 'دسته‌بندی فرعی محصول'],
                ['ارزش افزوده خرید', 'اختیاری', 'True/False'],
                ['ارزش افزوده فروش', 'اختیاری', 'True/False'],
                ['ارزش افزوده درصد', 'اختیاری', 'درصد ارزش افزوده'],
                ['بارکد دارد یا خیر', 'اختیاری', 'دارد/ندارد'],
                ['شماره فنی', 'اختیاری', 'شماره فنی محصول'],
                ['انتخاب ویزیتور', 'اختیاری', 'اطلاعات ویزیتور'],
                ['مشخصه ها', 'اختیاری', 'مشخصات محصول'],
                ['طبقه کالا', 'اختیاری', 'طبقه‌بندی کالا'],
                ['کنترل سریال', 'الزامی', 'True/False'],
                ['وضعیت فعال', 'الزامی', 'True/False'],
                ['قیمت محصول', 'الزامی', 'قیمت به تومان (با کاما)'],
                ['درصد تخفیف', 'اختیاری', '0-99 (اگر 0 یا خالی، تخفیف حذف می‌شود)'],
                ['موجودی', 'الزامی', 'تعداد موجودی'],
            ];

            $row = 5;
            foreach ($columnData as $data) {
                $instructionsSheet->setCellValue('A' . $row, $data[0]);
                $instructionsSheet->getStyle('A' . $row)->getFont()->setBold(true);
                $instructionsSheet->setCellValue('B' . $row, $data[1]);
                $instructionsSheet->setCellValue('C' . $row, $data[2]);
                $row++;
            }

            // Examples section
            $instructionsSheet->setCellValue('A11', 'نمونه‌های پر شده:');
            $instructionsSheet->getStyle('A11')->getFont()->setBold(true)->setSize(14);

            $examples = [
                ['1001', 'زعفران سرگل یک مثقال پاکت', 'کالا', 'عدد', 'زعفران', 'زعفران', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'True', '9,000,000', '10', '150'],
                ['1002', 'زعفران سرگل نیم مثقال پاکت', 'کالا', 'عدد', 'زعفران', 'زعفران', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'True', '5,000,000', '0', '200'],
            ];

            $row = 13;
            foreach ($examples as $example) {
                foreach ($example as $colIndex => $value) {
                    $columnLetter = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'][$colIndex];
                    $instructionsSheet->setCellValue($columnLetter . $row, $value);
                }
                $row++;
            }

            // Important notes
            $instructionsSheet->setCellValue('A18', 'نکات بسیار مهم:');
            $instructionsSheet->getStyle('A18')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFE53E3E'));

            $notes = [
                'اگر محصول با نام مشابه وجود داشته باشد، بروزرسانی می‌شود',
                'اگر محصول وجود نداشته باشد، محصول جدید ایجاد می‌شود',
                'قیمت فروش ویژه به طور خودکار بر اساس درصد تخفیف محاسبه می‌شود',
                'برای حذف تخفیف، ستون درصد تخفیف را خالی بگذارید',
                'قیمت‌ها باید فقط عدد باشند (بدون تومان یا کاما)',
                'درصد تخفیف باید بین 0 تا 99 باشد',
                'موجودی انبار باید عدد مثبت باشد',
                'نام محصول نمی‌تواند تکراری باشد',
            ];

            $row = 20;
            foreach ($notes as $note) {
                $instructionsSheet->setCellValue('A' . $row, '• ' . $note);
                $row++;
            }

            // Set column widths for instructions sheet
            $instructionsSheet->getColumnDimension('A')->setWidth(50);
            $instructionsSheet->getColumnDimension('B')->setWidth(15);
            $instructionsSheet->getColumnDimension('C')->setWidth(15);
            $instructionsSheet->getColumnDimension('D')->setWidth(40);
            $instructionsSheet->getColumnDimension('E')->setWidth(35);

            // Style instructions sheet
            $instructionsSheet->getStyle('A3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            $instructionsSheet->getStyle('A11')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            $instructionsSheet->getStyle('A18')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');

            // Save file
            $filename = 'sample_products_update_' . date('Y-m-d_H-i-s') . '.xlsx';
            $filepath = wp_upload_dir()['path'] . '/' . $filename;

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filepath);

            $file_url = wp_upload_dir()['url'] . '/' . $filename;

            return array(
                'success' => true,
                'file_url' => $file_url,
                'filename' => $filename
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => 'خطا در ایجاد فایل نمونه: ' . $e->getMessage()
            );
        }
    }
}
