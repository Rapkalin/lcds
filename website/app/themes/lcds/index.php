<?php
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<?php if (!$_COOKIE["intro"]): ?>
    <div class="intro-container" id="intro-start">Cookie accepté</div>
<?php endif; ?>

<?php
if (have_rows('content_blocks')) : ?>
    <?php
        $templateName  = basename(get_page_template());
    $maxWidth = $templateName === 'page-x.php' || get_post_type() === 'projects' || is_front_page();
    ?>
    <div class="main-content-container <?= $maxWidth ? 'max-width-container' : '' ?>">
        <?php while (have_rows('content_blocks')) : the_row(); ?>
            <section class="content-blocks <?= get_row_layout() ?>">
                <?php switch (get_row_layout()) {
                    case 'block_x':
                        get_template_part("components/block_x");
                        break;
                    case 'block_image_text':
                        get_template_part("components/block_image_text_button");
                        break;
                    case 'block_image_with_text':
                        get_template_part("components/block_image_with_text");
                        break;
                } ?>
            </section>
        <?php endwhile; ?>
    </div>
<?php endif;
get_footer();
