(function ($) {
    $(document).ready(function () {
        // Utility functions for loading states
        function setLoading(button, loadingText = 'در حال بارگذاری...') {
            button.prop('disabled', true).addClass('loading').data('original-text', button.text()).text(loadingText);
        }

        function removeLoading(button) {
            button.prop('disabled', false).removeClass('loading');
            if (button.data('original-text')) {
                button.text(button.data('original-text'));
            }
        }

        function setSectionLoading(section, showOverlay = true) {
            if (showOverlay) {
                section.addClass('loading-overlay');
            }
        }

        function removeSectionLoading(section) {
            section.removeClass('loading-overlay');
        }

        // Show dashboard message
        function showDashboardMessage(message, type = 'info') {
            // Remove existing message
            $('.dashboard-message').remove();

            // Create message element
            const messageEl = $('<div class="dashboard-message fixed top-4 left-1/2 transform -translate-x-1/2 z-50 p-4 rounded-lg shadow-lg max-w-md"></div>');

            // Set message type styles
            if (type === 'info') {
                messageEl.addClass('bg-blue-50 border border-blue-200 text-blue-800');
            } else if (type === 'success') {
                messageEl.addClass('bg-green-50 border border-green-200 text-green-800');
            } else if (type === 'warning') {
                messageEl.addClass('bg-yellow-50 border border-yellow-200 text-yellow-800');
            } else if (type === 'error') {
                messageEl.addClass('bg-red-50 border border-red-200 text-red-800');
            }

            messageEl.html(`
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        ${type === 'info' ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>' :
                          type === 'success' ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>' :
                          type === 'warning' ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>' :
                          '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>'}
                    </div>
                    <div class="mr-3 text-sm font-medium">${message}</div>
                    <button class="dismiss-message flex-shrink-0 mr-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            `);

            // Add to page
            $('body').append(messageEl);

            // Auto dismiss after 5 seconds
            setTimeout(function() {
                messageEl.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Manual dismiss
            messageEl.find('.dismiss-message').on('click', function() {
                messageEl.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }

        // تب‌ها
        $('.nav-item').on('click', function () {
            var tab = $(this).data('page');

            // Remove active classes from all tabs
            $('.nav-item').removeClass('bg-blue-50 text-blue-600');
            // Add active class to clicked tab
            $(this).addClass('bg-blue-50 text-blue-600');

            // Hide all tab contents
            $('.page-content').removeClass('active').addClass('hidden');

            // Show selected tab content
            $('#' + tab + '-page').removeClass('hidden').addClass('active');

            // اگر تب سفارشات فعال شد، داده‌ها را لود کن
            if (tab === 'orders') {
                loadOrdersData();
            }
        });

        // بازه زمانی سفارشی
        $('#export-period').on('change', function () {
            if ($(this).val() === 'custom') {
                $('.custom-date').show();
            } else {
                $('.custom-date').hide();
            }
        });

        // بازه زمانی سفارشی برای آمار
        $('#stats-period').on('change', function () {
            if ($(this).val() === 'custom') {
                $('.custom-date-range').show();
            } else {
                $('.custom-date-range').hide();
            }
        });

        $('#upload-form').on('submit', function (e) {
            e.preventDefault();

            var formData = new FormData();
            formData.append('excel_file', $('#excel-file')[0].files[0]);
            formData.append('action', 'process_excel_upload');
            formData.append('nonce', custom_dashboard.nonce);

            var submitBtn = $(this).find('button[type="submit"]');
            setLoading(submitBtn, 'در حال آپلود...');
            $('#result').html('<p>در حال آپلود...</p>').show();

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    removeLoading(submitBtn);
                    if (response.success) {
                        $('#result').html('<p style="color: green;">' + response.data + '</p>');
                    } else {
                        $('#result').html('<p style="color: red;">' + response.data + '</p>');
                    }
                },
                error: function () {
                    removeLoading(submitBtn);
                    $('#result').html('<p style="color: red;">آپلود ناموفق بود.</p>');
                }
            });
        });

        // دانلود فایل نمونه
        $('#download-sample-btn').on('click', function () {
            var btn = $(this);
            setLoading(btn, 'در حال ایجاد فایل...');

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'generate_sample_file',
                    nonce: custom_dashboard.nonce
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        // Create download link and trigger download
                        var link = document.createElement('a');
                        link.href = response.data.file_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        alert('فایل نمونه با موفقیت دانلود شد.');
                    } else {
                        alert('خطا در ایجاد فایل نمونه: ' + response.data);
                    }
                },
                error: function () {
                    removeLoading(btn);
                    alert('خطا در ایجاد فایل نمونه.');
                }
            });
        });

        // فرم خروجی اکسل
        $('#export-form').on('submit', function (e) {
            e.preventDefault();

            var btn = $('#export-btn');
            setLoading(btn, 'در حال تولید فایل...');
            $('#export-result').html('<p>در حال تولید فایل...</p>').show();

            var period = $('#export-period').val();
            var startDate = $('#export-start-date').val();
            var endDate = $('#export-end-date').val();
            var status = $('#export-status').val();

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'export_orders_excel',
                    nonce: custom_dashboard.nonce,
                    period: period,
                    start_date: startDate,
                    end_date: endDate,
                    status: status
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        $('#export-result').html('<p style="color: green;">فایل با موفقیت تولید شد. <a href="' + response.data + '" target="_blank">دانلود فایل</a></p>');
                    } else {
                        $('#export-result').html('<p style="color: red;">' + response.data + '</p>');
                    }
                },
                error: function () {
                    removeLoading(btn);
                    $('#export-result').html('<p style="color: red;">خطا در تولید فایل.</p>');
                }
            });
        });

        // اعمال فیلترهای آمار
        $('#apply-filters-btn').on('click', function () {
            var btn = $(this);
            setLoading(btn, 'در حال بارگذاری...');
            setSectionLoading($('#orders-tab .orders-stats'));

            loadOrdersData(function () {
                removeLoading(btn);
                removeSectionLoading($('#orders-tab .orders-stats'));
            });
        });

        // کدهای مدیریت سفارشات
        let ordersTable;
        let currentStatus = 'all'; // پیش‌فرض: همه وضعیت‌ها
        let currentDateFilter = 'all';
        let currentSingleDate = '';
        let currentStartDate = '';
        let currentEndDate = '';

        // مقداردهی اولیه DataTable
        function initializeOrdersTable() {
            if (typeof $.fn.DataTable !== 'function') {
                console.error('DataTables is not loaded yet');
                return;
            }

            if (ordersTable) {
                ordersTable.destroy();
            }

            ordersTable = $('#orders-table').DataTable({
                serverSide: true,
                ajax: {
                    url: custom_dashboard.ajax_url,
                    type: 'POST',
                    data: function (d) {
                        d.action = 'get_datatable_orders';
                        d.nonce = custom_dashboard.nonce;
                        d.status_filter = $('#manage-status-filter').val();
                        d.date_filter = $('#manage-date-filter').val();
                        d.single_date = $('#manage-single-date').val();
                        d.start_date = $('#manage-start-date').val();
                        d.end_date = $('#manage-end-date').val();
                    }
                },
                columns: [
                    { data: 0, orderable: true, searchable: true }, // سفارش
                    { data: 1, orderable: false, searchable: true }, // آدرس
                    { data: 2, orderable: false, searchable: true }, // یادداشت
                    { data: 3, orderable: true, searchable: false }, // مجموع
                    { data: 4, orderable: true, searchable: false }, // وضعیت
                    { data: 5, orderable: true, searchable: false }, // تاریخ
                    { data: 6, orderable: false, searchable: false }, // پرینت
                    { data: 7, orderable: false, searchable: false }  // عملیات
                ],
                columnDefs: [
                    { width: '10%', targets: 0 }, // سفارش
                    { width: '20%', targets: 1 }, // آدرس
                    { width: '20%', targets: 2 }, // یادداشت
                    { width: '10%', targets: 3 }, // مجموع
                    { width: '10%', targets: 4 }, // وضعیت
                    { width: '10%', targets: 5 }, // تاریخ
                    { width: '5%', targets: 6 }, // پرینت
                    { width: '10%', targets: 7 }  // عملیات
                ],
                order: [[5, 'desc']], // مرتب‌سازی پیش‌فرض بر اساس تاریخ نزولی
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fa.json'
                },
                initComplete: function () {
                    // اضافه کردن کلاس‌های RTL و فارسی
                    $('.dataTables_wrapper').addClass('rtl');
                    $('.dataTables_filter input').addClass('text-right').attr('placeholder', 'جستجو...');
                }
            });
        }

        // بروزرسانی جدول با فیلترهای جدید
        function refreshOrdersTable() {
            if (ordersTable) {
                ordersTable.ajax.reload();
            }
        }        // ذخیره وضعیت قبلی قبل از تغییر
        $(document).on('focus', '.status-select', function () {
            const selectElement = $(this);
            const currentValue = selectElement.val();
            selectElement.data('previous-status', currentValue);
        });

        // تغییر وضعیت سفارش
        $(document).on('change', '.status-select', function () {
            const selectElement = $(this);
            const orderId = selectElement.data('order-id');
            const newStatus = selectElement.val();
            const oldStatus = selectElement.data('previous-status') || selectElement.val();
            const row = selectElement.closest('tr');

            // غیرفعال کردن کل سطر و نمایش loading
            row.addClass('loading-row');
            row.find('input, select, button').prop('disabled', true);

            // اضافه کردن overlay loading به سطر
            const loadingOverlay = $('<div class="loading-overlay-row"></div>');
            row.css('position', 'relative').append(loadingOverlay);

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_order_status',
                    nonce: custom_dashboard.nonce,
                    order_id: orderId,
                    new_status: newStatus
                },
                success: function (response) {
                    // برداشتن loading
                    row.removeClass('loading-row');
                    row.find('input, select, button').prop('disabled', false);
                    row.find('.loading-overlay-row').remove();

                    if (response.success) {
                        // بروزرسانی وضعیت در جدول
                        const statusCell = row.find('td').eq(4); // ستون وضعیت (index 4)
                        const statusClasses = {
                            'processing': 'bg-yellow-100 text-yellow-800',
                            'completed': 'bg-green-100 text-green-800',
                            'on-hold': 'bg-orange-100 text-orange-800',
                            'cancelled': 'bg-red-100 text-red-800'
                        };

                        statusCell.html(`<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClasses[newStatus] || 'bg-gray-100 text-gray-800'}">${response.data.new_status_name}</span>`);

                        // بروزرسانی select
                        selectElement.val(newStatus);

                        // نمایش پیام موفقیت
                        showDashboardMessage(response.data.message, 'success');
                    } else {
                        // برگرداندن وضعیت قبلی در صورت خطا
                        selectElement.val(oldStatus);
                        showDashboardMessage('خطا: ' + response.data, 'error');
                    }
                },
                error: function () {
                    // برداشتن loading
                    row.removeClass('loading-row');
                    row.find('input, select, button').prop('disabled', false);
                    row.find('.loading-overlay-row').remove();

                    // برگرداندن وضعیت قبلی
                    selectElement.val(oldStatus);
                    showDashboardMessage('خطا در تغییر وضعیت سفارش.', 'error');
                }
            });
        });

        // فیلتر وضعیت
        $('#manage-status-filter').on('change', function () {
            currentStatus = $(this).val();
            refreshOrdersTable();
        });

        // فیلتر تاریخ
        $('#manage-date-filter').on('change', function () {
            var filterValue = $(this).val();
            $('.custom-date-single, .custom-date-range').addClass('hidden');

            if (filterValue === 'custom') {
                $('.custom-date-single').removeClass('hidden');
            } else if (filterValue === 'range') {
                $('.custom-date-range').removeClass('hidden');
            }

            currentDateFilter = filterValue;
            refreshOrdersTable();
        });

        // Date input listeners
        $('#manage-single-date, #manage-start-date, #manage-end-date').on('change', function() {
            refreshOrdersTable();
        });

        // Listen for Jalali Datepicker changes (if the library triggers this event)
        $(document).on('jdp:change', function (e) {
            var target = $(e.target);
            if (target.is('#manage-single-date') || target.is('#manage-start-date') || target.is('#manage-end-date')) {
                refreshOrdersTable();
            }
        });

        // دکمه بروزرسانی
        $('#refresh-orders-btn').on('click', function () {
            var btn = $(this);
            setLoading(btn, 'در حال بروزرسانی...');
            refreshOrdersTable();
            setTimeout(function () {
                removeLoading(btn);
            }, 1000);
        });
        // نمایش مدال جزئیات سفارش
        $(document).on('click', '.view-details-btn', function () {
            const orderId = $(this).data('order-id');
            const btn = $(this);
            setLoading(btn, 'بارگذاری...');

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_order_details',
                    nonce: custom_dashboard.nonce,
                    order_id: orderId
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        showOrderDetailsModal(response.data);
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function () {
                    removeLoading(btn);
                    alert('خطا در بارگذاری جزئیات سفارش.');
                }
            });
        });

        // نمایش مدال جزئیات سفارش
        function showOrderDetailsModal(data) {
            const modal = $('#order-details-modal');
            const content = $('#order-details-content');
            console.log(data);
            let html = `
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">جزئیات سفارش #${data.order_info.id}</h2>
                <div class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${data.order_info.status === 'processing' ? 'bg-yellow-100 text-yellow-800' : data.order_info.status === 'completed' ? 'bg-green-100 text-green-800' : data.order_info.status === 'on-hold' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800'}">
                    ${data.order_info.status_name}
                </div>
            </div>

            <div class="grid gap-6">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات سفارش</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تاریخ ایجاد:</label>
                            <span class="text-sm text-gray-900">${data.order_info.date_created || 'نامشخص'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">آخرین بروزرسانی:</label>
                            <span class="text-sm text-gray-900">${data.order_info.date_modified || 'ندارد'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">مجموع:</label>
                            <span class="text-lg font-bold text-green-600">${data.payment_info ? data.payment_info.total : 'نامشخص'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">روش پرداخت:</label>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.method : 'نامشخص'}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات مشتری</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">نام:</label>
                            <span class="text-sm text-gray-900">${data.customer_info ? data.customer_info.name : 'نامشخص'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ایمیل:</label>
                            <span class="text-sm text-gray-900">${data.customer_info ? (data.customer_info.email || 'ندارد') : 'نامشخص'}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تلفن:</label>
                            <span class="text-sm text-gray-900">${data.customer_info ? (data.customer_info.phone || 'ندارد') : 'نامشخص'}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h4 class="text-md font-medium text-gray-900 mb-2">آدرس صورتحساب</h4>
                        <p class="text-sm text-gray-600">${data.customer_info ? (data.customer_info.billing_address || 'ندارد') : 'نامشخص'}</p>
                    </div>

                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-2">آدرس ارسال</h4>
                        <p class="text-sm text-gray-600">${data.customer_info ? (data.customer_info.shipping_address || 'ندارد') : 'نامشخص'}</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">محصولات سفارش</h3>
                    <div class="space-y-3">
                        ${data.items && data.items.length > 0 ? data.items.map(item => `
                            <div class="bg-white p-4 rounded border border-gray-200">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-medium text-gray-900">${item.name || 'نام محصول ندارد'}</span>
                                        ${item.sku ? `<span class="text-sm text-gray-500 mr-2">SKU: ${item.sku}</span>` : ''}
                                    </div>
                                    <div class="text-left">
                                        <span class="text-sm text-gray-600">تعداد: ${item.quantity || '0'}</span>
                                        <span class="text-sm font-medium text-gray-900 mr-4">${item.price || '0'}</span>
                                    </div>
                                </div>
                            </div>
                        `).join('') : '<p class="text-sm text-gray-500">هیچ محصولی یافت نشد</p>'}
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">جزئیات پرداخت</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">زیرمجموع:</span>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.subtotal : '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">هزینه ارسال:</span>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.shipping : '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">مالیات:</span>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.tax : '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">تخفیف:</span>
                            <span class="text-sm text-gray-900">${data.payment_info ? data.payment_info.discount : '0'}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-300 pt-2">
                            <span class="text-sm font-medium text-gray-900">مجموع:</span>
                            <span class="text-lg font-bold text-green-600">${data.payment_info ? data.payment_info.total : '0'}</span>
                        </div>
                    </div>
                </div>

                ${data.order_info && data.order_info.customer_note ? `
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">یادداشت مشتری</h3>
                    <p class="text-sm text-gray-600">${data.order_info.customer_note}</p>
                </div>
                ` : ''}

                ${data.notes && data.notes.length > 0 ? `
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">تاریخچه سفارش</h3>
                    <div class="space-y-3">
                        ${data.notes.map(note => `
                            <div class="bg-white p-4 rounded border border-gray-200">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-sm font-medium text-gray-900">${note.date || 'بدون تاریخ'}</span>
                                    <span class="text-xs px-2 py-1 rounded-full ${note.type === 'customer' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">${note.type === 'customer' ? 'یادداشت مشتری' : 'یادداشت داخلی'}</span>
                                </div>
                                <div class="text-sm text-gray-600">${note.note || 'بدون یادداشت'}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            </div>
            `;

            content.html(html);
            modal.removeClass('hidden');
        }

        // بستن مدال
        $(document).on('click', '.close', function () {
            $('#order-details-modal').addClass('hidden');
        });

        // بستن مدال با کلیک خارج از آن
        $(window).on('click', function (event) {
            if ($(event.target).is('#order-details-modal')) {
                $('#order-details-modal').addClass('hidden');
            }
        });

        // مقداردهی اولیه DataTable وقتی تب فعال می‌شود
        $('.nav-item[data-page="orders"]').on('click', function () {
            if (!ordersTable) {
                if (typeof $.fn.DataTable === 'function') {
                    initializeOrdersTable();
                }
            }
        });

        // تابع نمایش جزئیات سفارش (برای onclick در HTML)
        window.showOrderDetails = function (orderId) {
            const btn = $(`.view-details-btn[data-order-id="${orderId}"]`);
            setLoading(btn, 'بارگذاری...');

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_order_details',
                    nonce: custom_dashboard.nonce,
                    order_id: orderId
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        showOrderDetailsModal(response.data);
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function () {
                    removeLoading(btn);
                    alert('خطا در بارگذاری جزئیات سفارش.');
                }
            });
        };

        // بارگذاری داده‌های آمار
        function loadOrdersData(callback) {
            var period = $('#stats-period').val();
            var startDate = $('#stats-start-date').val();
            var endDate = $('#stats-end-date').val();

            console.log('Loading orders data with period:', period);

            // Show loading state
            $('#total-orders, #completed-orders, #total-revenue, #avg-order').text('در حال بارگذاری...');

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_orders_stats',
                    nonce: custom_dashboard.nonce,
                    period: period,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function (response) {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        updateStats(response.data.stats);
                        createCharts(response.data.chart_data);

                        // Show message if no data
                        if (response.data.message) {
                            // Show message as a notification
                            showDashboardMessage(response.data.message, 'info');
                        }
                    } else {
                        console.error('AJAX error:', response.data);
                        // Show error message to user
                        $('#total-orders, #completed-orders, #total-revenue, #avg-order').text('خطا در بارگذاری');
                        alert('خطا در بارگذاری داده‌ها: ' + response.data);
                    }
                    if (callback) callback();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    $('#total-orders, #completed-orders, #total-revenue, #avg-order').text('خطا در اتصال');
                    alert('خطا در اتصال به سرور. لطفا دوباره تلاش کنید.');
                    if (callback) callback();
                }
            });
        }

        function createCharts(chartData) {
            // نمودار فروش ماهانه با TradingView
            const chartElement = document.getElementById('monthly-sales-chart');

            // پاک کردن محتوای قبلی
            chartElement.innerHTML = '';

            const chart = LightweightCharts.createChart(chartElement, {
                layout: {
                    background: { color: 'rgba(255, 255, 255, 0.95)' },
                    textColor: '#2d3748',
                    fontSize: 12,
                    fontFamily: 'Vazirmatn, sans-serif'
                },
                grid: {
                    vertLines: { color: 'rgba(0, 0, 0, 0.05)' },
                    horzLines: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                crosshair: {
                    mode: LightweightCharts.CrosshairMode.Normal
                },
                rightPriceScale: {
                    borderColor: 'rgba(102, 126, 234, 0.3)',
                    textColor: '#4a5568'
                },
                timeScale: {
                    borderColor: 'rgba(102, 126, 234, 0.3)',
                    timeVisible: true,
                    secondsVisible: false
                },
                width: chartElement.clientWidth,
                height: 400
            });

            // تبدیل داده‌های ماهانه به فرمت TradingView
            const areaSeries = chart.addAreaSeries({
                topColor: 'rgba(102, 126, 234, 0.56)',
                bottomColor: 'rgba(102, 126, 234, 0.04)',
                lineColor: '#667eea',
                lineWidth: 3,
                crosshairMarkerVisible: true,
                crosshairMarkerRadius: 6,
                priceFormat: {
                    type: 'price',
                    precision: 0,
                    minMove: 1
                }
            });

            // تبدیل داده‌ها به فرمت مناسب
            let tradingViewData = [];
            if (chartData.monthly.labels && chartData.monthly.labels.length > 0) {
                tradingViewData = chartData.monthly.labels.map((label, index) => ({
                    time: new Date(label + '-01').getTime() / 1000, // تبدیل به timestamp
                    value: parseFloat(chartData.monthly.data[index]) || 0
                }));
            }

            areaSeries.setData(tradingViewData);

            // تنظیم محدوده زمانی اگر داده وجود داشته باشد
            if (tradingViewData.length > 0) {
                chart.timeScale().fitContent();
            }

            // responsive کردن نمودار
            const resizeObserver = new ResizeObserver(entries => {
                for (let entry of entries) {
                    const { width, height } = entry.contentRect;
                    chart.applyOptions({ width, height: 400 });
                }
            });
            resizeObserver.observe(chartElement);

            // نمودار وضعیت سفارشات با Chart.js
            const statusCtx = document.getElementById('order-status-chart').getContext('2d');

            // Destroy existing chart if it exists
            if (window.orderStatusChart) {
                window.orderStatusChart.destroy();
            }

            window.orderStatusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: chartData.status.labels,
                    datasets: [{
                        data: chartData.status.data,
                        backgroundColor: [
                            '#48bb78',
                            '#ed8936',
                            '#f56565',
                            '#a0aec0'
                        ],
                        borderColor: [
                            '#38a169',
                            '#dd6b20',
                            '#e53e3e',
                            '#718096'
                        ],
                        borderWidth: 2,
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Vazirmatn',
                                    size: 14,
                                    weight: '500'
                                },
                                color: '#2d3748',
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(45, 55, 72, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 8,
                            titleFont: {
                                family: 'Vazirmatn',
                                size: 14,
                                weight: '600'
                            },
                            bodyFont: {
                                family: 'Vazirmatn',
                                size: 13
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
        // Analytics functionality
        let analyticsCharts = {};

        // Load analytics data
        function loadAnalyticsData() {
            const period = $('#analytics-period').val();
            const startDate = $('#analytics-start-date').val();
            const endDate = $('#analytics-end-date').val();

            console.log('Loading analytics data with period:', period, 'start:', startDate, 'end:', endDate);

            setSectionLoading($('#analytics-page'), true);

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_analytics_data',
                    nonce: custom_dashboard.nonce,
                    period: period,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function (response) {
                    console.log('Analytics AJAX response:', response);
                    removeSectionLoading($('#analytics-page'));
                    if (response.success) {
                        console.log('Analytics data received:', response.data);
                        updateAnalyticsUI(response.data);
                    } else {
                        console.error('Analytics error:', response.data);
                        showDashboardMessage('خطا در بارگذاری داده‌های تحلیل: ' + response.data, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Analytics AJAX error:', status, error, xhr.responseText);
                    removeSectionLoading($('#analytics-page'));
                    showDashboardMessage('خطا در اتصال به سرور برای بارگذاری تحلیل‌ها.', 'error');
                }
            });
        }

        // Update analytics UI
        function updateAnalyticsUI(data) {
            // Update stats
            $('#customers-total').text(data.customer_stats.total || 0);
            $('#customers-new').text(data.customer_stats.new || 0);
            $('#customers-loyal').text(data.customer_stats.loyal || 0);
            $('#customers-avg-order').text((data.customer_stats.avg_order || 0) + ' تومان');

            // Update top products
            updateTopProducts(data.top_products);

            // Update province sales
            updateProvinceSales(data.province_sales);

            // Update performance metrics
            updatePerformanceMetrics(data.performance);

            // Create charts
            createAnalyticsCharts(data.charts);
        }

        // Update top products list
        function updateTopProducts(products) {
            const container = $('#top-products-list');
            container.empty();

            if (products && products.length > 0) {
                products.forEach((product, index) => {
                    const item = `
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-sm font-medium text-blue-600 ml-3">
                                    ${index + 1}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">${product.name}</div>
                                    <div class="text-sm text-gray-500">${product.sales} فروش</div>
                                </div>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-900">${product.revenue} تومان</div>
                            </div>
                        </div>
                    `;
                    container.append(item);
                });
            } else {
                container.html('<p class="text-gray-500 text-center py-4">داده‌ای یافت نشد</p>');
            }
        }

        // Update province sales
        function updateProvinceSales(provinces) {
            const container = $('#province-sales-list');
            container.empty();

            if (provinces && provinces.length > 0) {
                provinces.forEach((province, index) => {
                    const item = `
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <span class="font-medium text-gray-900">${province.name}</span>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-900">${province.sales} سفارش</div>
                                <div class="text-sm text-gray-500">${province.revenue} تومان</div>
                            </div>
                        </div>
                    `;
                    container.append(item);
                });
            } else {
                container.html('<p class="text-gray-500 text-center py-4">داده‌ای یافت نشد</p>');
            }
        }

        // Update performance metrics
        function updatePerformanceMetrics(performance) {
            $('#conversion-rate').text((performance.conversion_rate || 0) + '%');
            $('#conversion-rate-bar').css('width', (performance.conversion_rate || 0) + '%');
            $('#avg-processing-time').text((performance.avg_processing_time || 0) + ' روز');
            $('#customer-retention').text((performance.customer_retention || 0) + '%');
            $('#customer-satisfaction').text((performance.customer_satisfaction || 0) + '%');
        }

        // Create analytics charts
        function createAnalyticsCharts(chartData) {
            console.log('Creating analytics charts with data:', chartData);
            // Monthly revenue chart
            createMonthlyRevenueChart(chartData.monthly);

            // Daily revenue chart
            createDailyRevenueChart(chartData.daily);

            // Revenue distribution chart
            createRevenueDistributionChart(chartData.distribution);
        }

        // Monthly revenue chart
        function createMonthlyRevenueChart(data) {
            const chartElement = document.getElementById('monthly-revenue-chart');
            if (!chartElement) {
                console.error('Monthly revenue chart element not found');
                return;
            }

            console.log('Creating monthly revenue chart with data:', data);

            try {
                // Clear existing chart
                if (analyticsCharts.monthly) {
                    analyticsCharts.monthly.remove();
                }

                analyticsCharts.monthly = LightweightCharts.createChart(chartElement, {
                    layout: {
                        background: { color: 'rgba(255, 255, 255, 0.95)' },
                        textColor: '#2d3748',
                        fontSize: 12,
                        fontFamily: 'Vazirmatn, sans-serif'
                    },
                    grid: {
                        vertLines: { color: 'rgba(0, 0, 0, 0.05)' },
                        horzLines: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    width: chartElement.clientWidth,
                    height: 250
                });

                const areaSeries = analyticsCharts.monthly.addAreaSeries({
                    topColor: 'rgba(102, 126, 234, 0.56)',
                    bottomColor: 'rgba(102, 126, 234, 0.04)',
                    lineColor: '#667eea',
                    lineWidth: 2
                });

                if (data && data.labels && data.data && data.labels.length > 0) {
                    const chartData = data.labels.map((label, index) => ({
                        time: new Date(label + '-01').getTime() / 1000,
                        value: parseFloat(data.data[index]) || 0
                    }));
                    areaSeries.setData(chartData);
                    analyticsCharts.monthly.timeScale().fitContent();
                    console.log('Monthly chart created successfully');
                } else {
                    console.warn('No data for monthly chart');
                }
            } catch (error) {
                console.error('Error creating monthly revenue chart:', error);
            }
        }

        // Daily revenue chart
        function createDailyRevenueChart(data) {
            const chartElement = document.getElementById('daily-revenue-chart');
            if (!chartElement) {
                console.error('Daily revenue chart element not found');
                return;
            }

            console.log('Creating daily revenue chart with data:', data);

            try {
                if (analyticsCharts.daily) {
                    analyticsCharts.daily.remove();
                }

                analyticsCharts.daily = LightweightCharts.createChart(chartElement, {
                    layout: {
                        background: { color: 'rgba(255, 255, 255, 0.95)' },
                        textColor: '#2d3748',
                        fontSize: 12,
                        fontFamily: 'Vazirmatn, sans-serif'
                    },
                    grid: {
                        vertLines: { color: 'rgba(0, 0, 0, 0.05)' },
                        horzLines: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    width: chartElement.clientWidth,
                    height: 250
                });

                const lineSeries = analyticsCharts.daily.addLineSeries({
                    color: '#48bb78',
                    lineWidth: 2
                });

                if (data && data.labels && data.data && data.labels.length > 0) {
                    const chartData = data.labels.map((label, index) => ({
                        time: new Date(label).getTime() / 1000,
                        value: parseFloat(data.data[index]) || 0
                    }));
                    lineSeries.setData(chartData);
                    analyticsCharts.daily.timeScale().fitContent();
                    console.log('Daily chart created successfully');
                } else {
                    console.warn('No data for daily chart');
                }
            } catch (error) {
                console.error('Error creating daily revenue chart:', error);
            }
        }        // Revenue distribution chart
        function createRevenueDistributionChart(data) {
            const ctx = document.getElementById('revenue-distribution-chart');
            if (!ctx) {
                console.error('Revenue distribution chart element not found');
                return;
            }

            console.log('Creating revenue distribution chart with data:', data);

            try {
                if (analyticsCharts.distribution) {
                    analyticsCharts.distribution.destroy();
                }

                analyticsCharts.distribution = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data ? data.labels : [],
                        datasets: [{
                            data: data ? data.data : [],
                            backgroundColor: ['#667eea', '#48bb78', '#ed8936', '#f56565', '#a0aec0'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 1,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: {
                                        family: 'Vazirmatn',
                                        size: 12
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Distribution chart created successfully');
            } catch (error) {
                console.error('Error creating revenue distribution chart:', error);
            }
        }

        // Analytics event handlers
        $('#refresh-analytics-btn').on('click', function () {
            const btn = $(this);
            setLoading(btn, 'در حال بروزرسانی...');
            loadAnalyticsData();
            setTimeout(() => removeLoading(btn), 1000);
        });

        $('#analytics-period').on('change', function () {
            if ($(this).val() === 'custom') {
                $('#analytics-start-date, #analytics-end-date').prop('disabled', false);
            } else {
                $('#analytics-start-date, #analytics-end-date').prop('disabled', true);
                loadAnalyticsData();
            }
        });

        // Export handlers
        $('#export-sales-report').on('click', function () {
            exportReport('sales');
        });

        $('#export-customers-report').on('click', function () {
            exportReport('customers');
        });

        $('#export-products-report').on('click', function () {
            exportReport('products');
        });

        function exportReport(type) {
            const btn = $(`#export-${type}-report`);
            setLoading(btn, 'در حال تولید...');

            const period = $('#analytics-period').val();
            const startDate = $('#analytics-start-date').val();
            const endDate = $('#analytics-end-date').val();

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: `export_${type}_report`,
                    nonce: custom_dashboard.nonce,
                    period: period,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function (response) {
                    removeLoading(btn);
                    if (response.success) {
                        // Create download link
                        const link = document.createElement('a');
                        link.href = response.data.file_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function () {
                    removeLoading(btn);
                    alert('خطا در تولید گزارش');
                }
            });
        }

        function updateStats(stats) {
            $('#total-orders').text(stats.total_orders);
            $('#completed-orders').text(stats.completed_orders);
            $('#total-revenue').text(stats.total_revenue + ' تومان');
            $('#avg-order').text(stats.avg_order + ' تومان');
        }

        // Customers functionality
        let customersTable;

        // Initialize customers DataTable
        function initializeCustomersTable() {
            if (typeof $.fn.DataTable !== 'function') {
                console.error('DataTables is not loaded yet');
                return;
            }

            if (customersTable) {
                customersTable.destroy();
            }

            customersTable = $('#customers-table').DataTable({
                serverSide: true,
                ajax: {
                    url: custom_dashboard.ajax_url,
                    type: 'POST',
                    data: function (d) {
                        d.action = 'get_datatable_customers';
                        d.nonce = custom_dashboard.nonce;
                        d.search = $('#customers-search').val();
                        d.sort = $('#customers-sort').val();
                        d.date_filter = $('#customers-date-filter').val();
                    }
                },
                columns: [
                    { data: 0, orderable: true, searchable: true }, // مشتری
                    { data: 1, orderable: false, searchable: true }, // اطلاعات تماس
                    { data: 2, orderable: true, searchable: false }, // آمار سفارشات
                    { data: 3, orderable: true, searchable: false }, // مجموع خرید
                    { data: 4, orderable: true, searchable: false }, // آخرین سفارش
                    { data: 5, orderable: false, searchable: false }  // عملیات
                ],
                columnDefs: [
                    { width: '20%', targets: 0 }, // مشتری
                    { width: '25%', targets: 1 }, // اطلاعات تماس
                    { width: '10%', targets: 2 }, // آمار سفارشات
                    { width: '15%', targets: 3 }, // مجموع خرید
                    { width: '15%', targets: 4 }, // آخرین سفارش
                    { width: '15%', targets: 5 }  // عملیات
                ],
                order: [[0, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fa.json'
                },
                initComplete: function () {
                    $('.dataTables_wrapper').addClass('rtl');
                    $('.dataTables_filter input').addClass('text-right').attr('placeholder', 'جستجو...');
                }
            });
        }

        // Load customers stats
        function loadCustomersStats() {
            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_customers_stats',
                    nonce: custom_dashboard.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('#customers-total').text(response.data.total);
                        $('#customers-new').text(response.data.new);
                        $('#customers-loyal').text(response.data.loyal);
                        $('#customers-avg-order').text(response.data.avg_order + ' تومان');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Customers stats error:', status, error);
                }
            });
        }

        // Refresh customers table
        function refreshCustomersTable() {
            if (customersTable) {
                customersTable.ajax.reload();
            }
            loadCustomersStats();
        }

        // Show customer details modal
        function showCustomerDetailsModal(customerId) {
            const modal = $('#customer-details-modal');
            const content = $('#customer-details-content');

            setSectionLoading(modal, true);

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_customer_details',
                    nonce: custom_dashboard.nonce,
                    customer_id: customerId
                },
                success: function (response) {
                    removeSectionLoading(modal);
                    if (response.success) {
                        const data = response.data;
                        let html = `
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-6 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات مشتری</h3>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">نام:</label>
                                            <span class="text-sm text-gray-900">${data.name || 'نامشخص'}</span>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">ایمیل:</label>
                                            <span class="text-sm text-gray-900">${data.email || 'ندارد'}</span>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">تلفن:</label>
                                            <span class="text-sm text-gray-900">${data.phone || 'ندارد'}</span>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">تاریخ عضویت:</label>
                                            <span class="text-sm text-gray-900">${data.registered_date || 'نامشخص'}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-6 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">آمار خرید</h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-blue-600">${data.total_orders || 0}</div>
                                            <div class="text-sm text-gray-600">کل سفارشات</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-green-600">${data.total_spent || 0} تومان</div>
                                            <div class="text-sm text-gray-600">مجموع خرید</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-purple-600">${data.avg_order_value || 0} تومان</div>
                                            <div class="text-sm text-gray-600">میانگین سفارش</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-orange-600">${data.last_order_date || 'ندارد'}</div>
                                            <div class="text-sm text-gray-600">آخرین سفارش</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-6 rounded-lg lg:col-span-2">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">آخرین سفارشات</h3>
                                    <div class="space-y-3">
                                        ${data.recent_orders && data.recent_orders.length > 0 ?
                                data.recent_orders.map(order => `
                                                <div class="bg-white p-4 rounded border border-gray-200">
                                                    <div class="flex justify-between items-center">
                                                        <div>
                                                            <span class="font-medium text-gray-900">سفارش #${order.id}</span>
                                                            <span class="text-sm text-gray-500 mr-4">${order.date}</span>
                                                        </div>
                                                        <div class="text-left">
                                                            <span class="text-sm font-medium text-gray-900">${order.total} تومان</span>
                                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ml-2 ${order.status === 'completed' ? 'bg-green-100 text-green-800' :
                                        order.status === 'processing' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-gray-100 text-gray-800'
                                    }">${order.status_name}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            `).join('') :
                                '<p class="text-sm text-gray-500 text-center py-4">هیچ سفارشی یافت نشد</p>'
                            }
                                    </div>
                                </div>
                            </div>
                        `;
                        content.html(html);
                        modal.removeClass('hidden');
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function () {
                    removeSectionLoading(modal);
                    alert('خطا در بارگذاری جزئیات مشتری.');
                }
            });
        }

        // بستن مدال جزئیات مشتری
        $(document).on('click', '#customer-details-modal .close', function () {
            $('#customer-details-modal').addClass('hidden');
        });

        $(window).on('click', function (event) {
            if ($(event.target).is('#customer-details-modal')) {
                $('#customer-details-modal').addClass('hidden');
            }
        });

        // Customers page event handlers
        $('#refresh-customers-btn').on('click', function () {
            const btn = $(this);
            setLoading(btn, 'در حال بروزرسانی...');
            refreshCustomersTable();
            setTimeout(() => removeLoading(btn), 1000);
        });

        $('#customers-search').on('keyup', function () {
            if (customersTable) {
                customersTable.search($(this).val()).draw();
            }
        });

        $('#customers-sort').on('change', function () {
            refreshCustomersTable();
        });

        $('#customers-date-filter').on('change', function () {
            refreshCustomersTable();
        });

        // Customer details modal handlers
        $(document).on('click', '.view-customer-details-btn', function () {
            const customerId = $(this).data('customer-id');
            showCustomerDetailsModal(customerId);
        });

        // Products functionality
        let productsTable;

        function initializeProductsTable() {
            if (typeof $.fn.DataTable !== 'function') return;

            if (productsTable) {
                productsTable.destroy();
            }

            productsTable = $('#products-table').DataTable({
                serverSide: true,
                ajax: {
                    url: custom_dashboard.ajax_url,
                    type: 'POST',
                    data: function (d) {
                        d.action = 'get_datatable_products';
                        d.nonce = custom_dashboard.nonce;
                    },
                    dataSrc: function (json) {
                        console.log('DataTables Success Response:', json);
                        return json.data;
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTables Error:', error);
                        console.error('DataTables Thrown:', thrown);
                        console.log('Response Text:', xhr.responseText);
                        alert('خطا در دریافت اطلاعات جدول. لطفا کنسول مرورگر را بررسی کنید.');
                    }
                },
                columns: [
                    // ID
                    { data: 0, orderable: true, searchable: false, defaultContent: "" }, 
                    // Image
                    { data: 1, orderable: false, searchable: false, defaultContent: "" },
                    // Name
                    { data: 2, orderable: true, searchable: true, defaultContent: "" }, 
                    // SKU
                    { data: 3, orderable: true, searchable: true, defaultContent: "" }, 
                    // Price
                    { data: 4, orderable: true, searchable: false, defaultContent: "" }, 
                    // Stock
                    { data: 5, orderable: true, searchable: false, defaultContent: "" }, 
                    // Actions
                    { data: 6, orderable: false, searchable: false, defaultContent: "" }  
                ],
                columnDefs: [
                    { width: '5%', targets: 0 },
                    { width: '10%', targets: 1 },
                    { width: '30%', targets: 2 },
                    { width: '15%', targets: 3 },
                    { width: '15%', targets: 4 },
                    { width: '15%', targets: 5 },
                    { width: '10%', targets: 6 }
                ],
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50], [10, 25, 50]],
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fa.json'
                },
                initComplete: function () {
                    const wrapper = $(this.api().table().container());
                    wrapper.addClass('rtl');
                    wrapper.find('.dataTables_filter input').addClass('text-right').attr('placeholder', 'جستجو...');
                }
            });
        }

        // Initialize products table when tab is shown
        $('.nav-item[data-page="products"]').on('click', function () {
            setTimeout(function () {
                if (!productsTable) {
                    initializeProductsTable();
                } else {
                    productsTable.columns.adjust().responsive.recalc();
                }
            }, 100);
        });

        // Refresh products button
        $('#refresh-products-btn').on('click', function() {
            if (productsTable) {
                productsTable.ajax.reload();
            }
        });

        // Edit Product Modal
        $(document).on('click', '.edit-product-btn', function() {
            const btn = $(this);
            const id = btn.data('id');
            const name = btn.data('name');
            const regularPrice = btn.data('regular-price');
            const salePrice = btn.data('sale-price');
            const stock = btn.data('stock');

            $('#edit-product-id').val(id);
            $('#edit-product-name').val(name);
            $('#edit-product-regular-price').val(regularPrice);
            $('#edit-product-sale-price').val(salePrice);
            $('#edit-product-stock').val(stock);

            $('#edit-product-modal').removeClass('hidden');
        });

        // Close modal
        $('.close-modal').on('click', function() {
            $('#edit-product-modal').addClass('hidden');
        });

        // Submit edit form
        $('#edit-product-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            setLoading(btn, 'در حال ذخیره...');

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_product_simple',
                    nonce: custom_dashboard.nonce,
                    product_id: $('#edit-product-id').val(),
                    regular_price: $('#edit-product-regular-price').val(),
                    sale_price: $('#edit-product-sale-price').val(),
                    stock_quantity: $('#edit-product-stock').val()
                },
                success: function(response) {
                    removeLoading(btn);
                    if (response.success) {
                        $('#edit-product-modal').addClass('hidden');
                        showDashboardMessage(response.data, 'success');
                        if (productsTable) {
                            productsTable.ajax.reload(null, false); // Reload without resetting paging
                        }
                    } else {
                        alert('خطا: ' + response.data);
                    }
                },
                error: function() {
                    removeLoading(btn);
                    alert('خطا در ارتباط با سرور');
                }
            });
        });

        // Import/Export Tabs
        $('.ie-tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            
            // Update buttons
            $('.ie-tab-btn').removeClass('active text-blue-600 border-b-2 border-blue-600').addClass('text-gray-500');
            $(this).addClass('active text-blue-600 border-b-2 border-blue-600').removeClass('text-gray-500');
            
            // Update content
            $('.ie-tab-content').addClass('hidden');
            $('#' + tab + '-tab-content').removeClass('hidden');

            // Load categories if export tab is selected and not loaded yet
            if (tab === 'export' && $('#export-category option').length <= 1) {
                loadProductCategories();
            }
        });

        // Load Product Categories
        function loadProductCategories() {
            const select = $('#export-category');
            
            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_product_categories',
                    nonce: custom_dashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        response.data.forEach(function(cat) {
                            select.append(`<option value="${cat.id}">${cat.name} (${cat.count})</option>`);
                        });
                    }
                }
            });
        }

        // Export Products Form
        $('#export-products-form').on('submit', function(e) {
            e.preventDefault();
            
            const btn = $(this).find('button[type="submit"]');
            setLoading(btn, 'در حال تولید فایل...');
            $('#export-products-result').html('<p class="text-gray-600">در حال آماده‌سازی فایل اکسل...</p>');

            const formData = {
                action: 'export_products_excel',
                nonce: custom_dashboard.nonce,
                category: $('#export-category').val(),
                stock_status: $('#export-stock-status').val(),
                search: $('#export-search').val()
            };

            $.ajax({
                url: custom_dashboard.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    removeLoading(btn);
                    if (response.success) {
                        $('#export-products-result').html(`
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-600 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-green-800">فایل با موفقیت آماده شد.</span>
                                </div>
                                <a href="${response.data.file_url}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    دانلود فایل
                                </a>
                            </div>
                        `);
                        
                        // Auto download
                        const link = document.createElement('a');
                        link.href = response.data.file_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        $('#export-products-result').html(`<p class="text-red-600">خطا: ${response.data}</p>`);
                    }
                },
                error: function() {
                    removeLoading(btn);
                    $('#export-products-result').html('<p class="text-red-600">خطا در ارتباط با سرور.</p>');
                }
            });
        });

        jalaliDatepicker.startWatch();
    });

})(jQuery);
