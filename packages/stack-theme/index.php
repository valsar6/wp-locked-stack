<?php declare(strict_types=1); ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main data-theme="stack-theme">
    <h1><?php bloginfo('name'); ?></h1>
    <p><?php echo esc_html(stack_theme_access_notice(get_current_user_id())); ?></p>
</main>
<?php wp_footer(); ?>
</body>
</html>
