<?php
get_header();
?>

<div class="login-required-container">
    <h1>دسترسی به داشبورد</h1>
    <p>برای دسترسی به داشبورد سفارشی، لطفاً وارد حساب کاربری خود شوید.</p>

    <?php echo do_shortcode('[smsir_login]'); ?>

    <p>اگر حساب کاربری ندارید، می‌توانید از طریق مودال بالا ثبت نام کنید.</p>
</div>

<?php
get_footer();
?>