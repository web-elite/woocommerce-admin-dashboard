<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class Excel_Processor {

    public static function process_upload($file_path) {
        try {
            $spreadsheet = IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            array_shift($rows);

            $updated_count = 0;
            $created_count = 0;
            $errors = array();

            foreach ($rows as $index => $row) {
                // Assuming columns: نام محصول, قیمت, درصد تخفیف, موجودی انبار
                $product_name = trim($row[0] ?? '');
                $regular_price = trim($row[1] ?? '');
                $discount_percent = trim($row[2] ?? '');
                $stock_quantity = trim($row[3] ?? '');

                // Validate required fields
                if (empty($product_name)) {
                    $errors[] = "ردیف " . ($index + 2) . ": نام محصول نمی‌تواند خالی باشد";
                    continue;
                }

                if (empty($regular_price) || !is_numeric($regular_price)) {
                    $errors[] = "ردیف " . ($index + 2) . ": قیمت نامعتبر برای محصول '{$product_name}'";
                    continue;
                }

                // Calculate sale price if discount is provided
                $sale_price = null;
                if (!empty($discount_percent) && is_numeric($discount_percent) && $discount_percent > 0 && $discount_percent < 100) {
                    $sale_price = $regular_price * (1 - $discount_percent / 100);
                }

                $result = self::update_product($product_name, $regular_price, $sale_price, $stock_quantity);

                if ($result === 'updated') {
                    $updated_count++;
                } elseif ($result === 'created') {
                    $created_count++;
                } else {
                    $errors[] = "ردیف " . ($index + 2) . ": " . $result;
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

    private static function update_product($name, $regular_price, $sale_price, $stock_quantity) {
        try {
            // Find product by name (case-insensitive search)
            global $wpdb;

            $product_id = $wpdb->get_var($wpdb->prepare("
                SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'product'
                AND post_title = %s
                LIMIT 1
            ", $name));

            if ($product_id) {
                // Update existing product
                /** @disregard */
                $product = wc_get_product($product_id);

                if (!$product) {
                    return "محصول '{$name}' یافت نشد";
                }

                $product->set_regular_price($regular_price);

                if ($sale_price !== null) {
                    $product->set_sale_price($sale_price);
                } else {
                    $product->set_sale_price(''); // Remove sale price
                }

                if (!empty($stock_quantity) && is_numeric($stock_quantity)) {
                    $product->set_stock_quantity($stock_quantity);
                    $product->set_manage_stock(true);
                }

                $product->save();
                return 'updated';
            } else {
                // Create new product
                /** @disregard */
                $product = new WC_Product_Simple();
                $product->set_name($name);
                $product->set_regular_price($regular_price);

                if ($sale_price !== null) {
                    $product->set_sale_price($sale_price);
                }

                if (!empty($stock_quantity) && is_numeric($stock_quantity)) {
                    $product->set_stock_quantity($stock_quantity);
                    $product->set_manage_stock(true);
                } else {
                    $product->set_stock_quantity(0);
                    $product->set_manage_stock(true);
                }

                $product->set_status('publish');
                $product->save();
                return 'created';
            }
        } catch (Exception $e) {
            return "خطا در بروزرسانی محصول '{$name}': " . $e->getMessage();
        }
    }

    public static function generate_sample_file() {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('محصولات');

            // Set headers
            $sheet->setCellValue('A1', 'نام محصول');
            $sheet->setCellValue('B1', 'قیمت');
            $sheet->setCellValue('C1', 'درصد تخفیف');
            $sheet->setCellValue('D1', 'موجودی انبار');

            // Style headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '667EEA'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                ],
            ];

            $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(35);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(18);
            $sheet->getColumnDimension('D')->setWidth(18);

            // Add sample data with different scenarios
            $sampleData = [
                // نمونه 1: محصول با تخفیف
                ['گوشی موبایل سامسونگ Galaxy A54', '4500000', '15', '25'],
                // نمونه 2: محصول بدون تخفیف
                ['لپ تاپ ایسوس ROG Strix G15', '28500000', '', '12'],
                // نمونه 3: محصول با تخفیف کم
                ['هدفون سونی WH-1000XM5', '3200000', '5', '40'],
                // نمونه 4: محصول با موجودی زیاد
                ['کیبورد مکانیکی Logitech MX Keys', '1200000', '10', '85'],
                // نمونه 5: محصول بدون موجودی مشخص
                ['ماوس بی‌سیم Logitech MX Master 3S', '950000', '8', ''],
                // نمونه 6: محصول ارزان با تخفیف بالا
                ['کیس گوشی سیلیکونی سامسونگ', '25000', '20', '200'],
                // نمونه 7: محصول گران بدون تخفیف
                ['مانیتور ایسوس ROG Swift PG279Q', '12500000', '', '8'],
                // نمونه 8: محصول با تخفیف متوسط
                ['اسپیکر بلوتوثی JBL GO 3', '450000', '12', '65'],
                // نمونه 9: محصول دیجیتال
                ['نرم‌افزار Adobe Photoshop 2024', '1500000', '25', '999'],
                // نمونه 10: محصول با تخفیف ویژه
                ['تبلت سامسونگ Galaxy Tab S9', '8500000', '18', '15'],
            ];

            $row = 2;
            foreach ($sampleData as $data) {
                $sheet->setCellValue('A' . $row, $data[0]);
                $sheet->setCellValue('B' . $row, $data[1]);
                $sheet->setCellValue('C' . $row, $data[2]);
                $sheet->setCellValue('D' . $row, $data[3]);

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

                $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($dataStyle);

                // Format price column as number
                if (!empty($data[1])) {
                    $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
                }

                // Format discount column as percentage
                if (!empty($data[2])) {
                    $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('0');
                }

                // Format stock column as number
                if (!empty($data[3])) {
                    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
                }

                $row++;
            }

            // Add empty rows for user input
            for ($i = 0; $i < 10; $i++) {
                $sheet->setCellValue('A' . $row, '');
                $sheet->setCellValue('B' . $row, '');
                $sheet->setCellValue('C' . $row, '');
                $sheet->setCellValue('D' . $row, '');

                $emptyStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'E2E8F0'],
                        ],
                    ],
                ];

                $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($emptyStyle);
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
                ['نام محصول', 'الزامی', 'نام کامل محصول را وارد کنید'],
                ['قیمت', 'الزامی', 'قیمت اصلی به تومان (فقط عدد)'],
                ['درصد تخفیف', 'اختیاری', 'درصد تخفیف (0-99، خالی برای بدون تخفیف)'],
                ['موجودی انبار', 'اختیاری', 'تعداد موجودی (فقط عدد، خالی برای نامشخص)'],
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
                ['گوشی موبایل سامسونگ', '4500000', '15', '25', 'قیمت فروش ویژه: 3,825,000 تومان'],
                ['لپ تاپ ایسوس', '28500000', '', '12', 'بدون تخفیف'],
                ['هدفون سونی', '3200000', '5', '', 'قیمت فروش ویژه: 3,040,000 تومان'],
            ];

            $row = 13;
            foreach ($examples as $example) {
                $instructionsSheet->setCellValue('A' . $row, $example[0]);
                $instructionsSheet->setCellValue('B' . $row, $example[1]);
                $instructionsSheet->setCellValue('C' . $row, $example[2]);
                $instructionsSheet->setCellValue('D' . $row, $example[3]);
                $instructionsSheet->setCellValue('E' . $row, $example[4]);
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