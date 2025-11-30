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

            // Skip first 1 row (header) - start from row 2
            array_shift($rows); // Remove row 1 (Header)

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
                $name = trim($row[1] ?? ''); // ستون 2: نام محصول
                $regular_price_str = trim($row[16] ?? ''); // ستون 17: قیمت محصول
                $discount_percent_str = trim($row[17] ?? ''); // ستون 18: درصد تخفیف
                $stock_quantity_str = trim($row[18] ?? ''); // ستون 19: موجودی

                // پاک کردن کاماها و تبدیل به عدد
                $regular_price = str_replace(',', '', $regular_price_str);
                $discount_percent = str_replace(',', '', $discount_percent_str);
                $excel_stock = str_replace(',', '', $stock_quantity_str);

                // Validate required fields
                if (empty($sku) && empty($name)) {
                    $errors[] = "ردیف " . ($index + 3) . ": نام محصول یا کد SKU الزامی است";
                    continue;
                }

                $identifier = !empty($sku) ? "SKU '{$sku}'" : "نام '{$name}'";

                if (empty($regular_price) || !is_numeric($regular_price)) {
                    $errors[] = "ردیف " . ($index + 3) . ": قیمت نامعتبر برای محصول با {$identifier}";
                    continue;
                }

                if ($excel_stock === '' || !is_numeric($excel_stock)) {
                    $errors[] = "ردیف " . ($index + 3) . ": موجودی نامعتبر برای محصول با {$identifier}";
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
                    $product_id = 0;
                    
                    if (!empty($sku)) {
                        $product_id = $wpdb->get_var($wpdb->prepare("
                            SELECT post_id FROM {$wpdb->postmeta}
                            WHERE meta_key = '_sku' AND meta_value = %s
                            LIMIT 1
                        ", $sku));
                    }
                    
                    if (!$product_id && !empty($name)) {
                        $product = get_page_by_title($name, OBJECT, 'product');
                        if ($product) {
                            $product_id = $product->ID;
                        }
                    }

                    $warehouse_limited = 0;
                    if ($product_id) {
                        $warehouse_limited = (int) get_post_meta($product_id, 'product_warehouse_limited', true);
                    }

                    $final_stock = max(0, $excel_stock - $warehouse_limited);
                }

                $result = self::update_product($sku, $name, $regular_price, $sale_price, $final_stock);

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

    /**
     * Update product by SKU or Name
     */
    private static function update_product($sku, $name, $regular_price, $sale_price, $stock_quantity)
    {
        try {
            $product_id = 0;

            // Try to find by SKU
            if (!empty($sku)) {
                $product_id = wc_get_product_id_by_sku($sku);
            }

            // If not found by SKU, try by Name
            if (!$product_id && !empty($name)) {
                $product = get_page_by_title($name, OBJECT, 'product');
                if ($product) {
                    $product_id = $product->ID;
                }
            }

            if (!$product_id) {
                return "محصول با SKU '{$sku}' یا نام '{$name}' یافت نشد";
            }

            $product = wc_get_product($product_id);

            if (!$product) {
                return "خطا در بارگذاری محصول";
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
                
                // Update stock status based on quantity
                if ($stock_quantity > 0) {
                    $product->set_stock_status('instock');
                } else {
                    $product->set_stock_status('outofstock');
                }
            }

            $product->save();
            return 'updated';

        } catch (Exception $e) {
            return "خطا در بروزرسانی محصول: " . $e->getMessage();
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
                // نمونه 1: محصول استاندارد (بدون تخفیف)
                ['1001', 'زعفران سرگل یک مثقال پاکت', 'کالا', 'عدد', 'زعفران', 'زعفران', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'True', '9000000', '0', '150'],
                // نمونه 2: محصول با تخفیف
                ['1002', 'زعفران سرگل نیم مثقال پاکت', 'کالا', 'عدد', 'زعفران', 'زعفران', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'True', '5000000', '10', '200'],
                // نمونه 3: محصول ناموجود (موجودی صفر)
                ['1003', 'زعفران سرگل ربع مثقال پاکت', 'کالا', 'عدد', 'زعفران', 'زعفران', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'True', '3000000', '0', '0'],
                // نمونه 4: محصول غیرفعال
                ['1004', 'زعفران نگین یک مثقال', 'کالا', 'عدد', 'زعفران', 'زعفران', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'False', '12000000', '0', '50'],
                // نمونه 5: محصول با تخفیف بالا
                ['1005', 'پک هدیه زعفران', 'کالا', 'بسته', 'هدایا', 'پک', 'True', 'True', '', 'دارد', '', '', '', '', 'False', 'True', '25000000', '25', '10'],
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

    public static function export_products($filters = array(), $fields = array())
    {
        try {
            // Build query args
            $args = array(
                'post_type' => 'product',
                'post_status' => array('publish', 'draft', 'private'),
                'posts_per_page' => -1,
                'fields' => 'ids' // Get IDs first to save memory
            );

            // Filter by Category
            if (!empty($filters['category']) && $filters['category'] !== 'all') {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $filters['category']
                    )
                );
            }

            // Filter by Stock Status
            if (!empty($filters['stock_status']) && $filters['stock_status'] !== 'all') {
                $args['meta_query'] = array(
                    array(
                        'key' => '_stock_status',
                        'value' => $filters['stock_status']
                    )
                );
            }

            // Search
            if (!empty($filters['search'])) {
                $args['s'] = $filters['search'];
            }

            $product_ids = get_posts($args);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('محصولات');

            // Set headers (Same as generate_sample_file)
            $headers = [
                'A' => 'کد SKU',
                'B' => 'نام محصول',
                'C' => 'نوع داده',
                'D' => 'واحد شمارش',
                'E' => 'دسته بندی اصلی',
                'F' => 'دسته بندی فرعی',
                'G' => 'ارزش افزوده خرید',
                'H' => 'ارزش افزوده فروش',
                'I' => 'ارزش افزوده درصد',
                'J' => 'بارکد دارد یا خیر',
                'K' => 'شماره فنی',
                'L' => 'انتخاب ویزیتور',
                'M' => 'مشخصه ها',
                'N' => 'طبقه کالا',
                'O' => 'کنترل سریال',
                'P' => 'وضعیت فعال یا غیرفعال',
                'Q' => 'قیمت محصول',
                'R' => 'درصد تخفیف',
                'S' => 'موجودی'
            ];

            foreach ($headers as $col => $text) {
                $sheet->setCellValue($col . '1', $text);
            }

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
            ];
            $sheet->getStyle('A1:S1')->applyFromArray($headerStyle);

            $row = 2;
            foreach ($product_ids as $product_id) {
                $product = wc_get_product($product_id);
                if (!$product) continue;

                // Calculate Discount Percent
                $regular_price = (float) $product->get_regular_price();
                $sale_price = (float) $product->get_sale_price();
                $discount_percent = 0;
                
                if ($regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price) {
                    $discount_percent = round((($regular_price - $sale_price) / $regular_price) * 100);
                }

                // Get Categories
                $cats = get_the_terms($product_id, 'product_cat');
                $main_cat = '';
                $sub_cat = '';
                if ($cats && !is_wp_error($cats)) {
                    // Sort by parent (parents first)
                    usort($cats, function($a, $b) {
                        return $a->parent - $b->parent;
                    });
                    
                    if (isset($cats[0])) $main_cat = $cats[0]->name;
                    if (isset($cats[1])) $sub_cat = $cats[1]->name;
                }

                // Map data
                $sheet->setCellValue('A' . $row, $product->get_sku());
                $sheet->setCellValue('B' . $row, $product->get_name());
                $sheet->setCellValue('C' . $row, 'کالا'); // Default
                $sheet->setCellValue('D' . $row, 'عدد'); // Default
                $sheet->setCellValue('E' . $row, $main_cat);
                $sheet->setCellValue('F' . $row, $sub_cat);
                $sheet->setCellValue('G' . $row, 'True'); // Default
                $sheet->setCellValue('H' . $row, 'True'); // Default
                $sheet->setCellValue('I' . $row, '');
                $sheet->setCellValue('J' . $row, 'دارد');
                $sheet->setCellValue('K' . $row, '');
                $sheet->setCellValue('L' . $row, '');
                $sheet->setCellValue('M' . $row, '');
                $sheet->setCellValue('N' . $row, '');
                $sheet->setCellValue('O' . $row, 'False');
                $sheet->setCellValue('P' . $row, $product->get_status() === 'publish' ? 'True' : 'False');
                $sheet->setCellValue('Q' . $row, $regular_price);
                $sheet->setCellValue('R' . $row, $discount_percent > 0 ? $discount_percent : '0');
                $sheet->setCellValue('S' . $row, $product->get_stock_quantity() ?? 0);

                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'S') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Save file
            $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.xlsx';
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
                'error' => 'خطا در برون‌بری محصولات: ' . $e->getMessage()
            );
        }
    }
}
