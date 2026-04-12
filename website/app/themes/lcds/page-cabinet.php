<?php
/**
 * Template Name: Page agence
 *
 * @package WordPress
 */

get_header(args:['color-logo' => '__grey']);

?>
<div class="page-header-container">
    <h1 class="title"><?= get_field('title') ?></h1>
    <div class="description"><?= get_field('description') ?></div>
</div>

<?php if (have_rows('content_blocks')) :
while (have_rows('content_blocks')) : the_row(); ?>
    <?php
        $blockId = get_sub_field('block_id');
        $hasId = $blockId['label'] && $blockId['identifier'];
    ?>
<section
    class="content-blocks <?= get_row_layout() ?>"
    <?php if ($hasId) : ?>
        id="<?= $blockId['identifier'] ?>"
    <?php endif; ?>
>
    <?php switch(get_row_layout()) {
        case 'block_image_full':
            get_template_part("components/block_image_full");
            break;
        case 'block_details':
            get_template_part("components/block_details");
            break;
        case 'block_numbers':
            get_template_part("components/block_numbers");
            break;
        case 'block_image_with_text':
            get_template_part("components/block_image_with_text");
            break;
        case 'block_infos':
            get_template_part("components/block_infos");
            break;
        case 'block_rewards':
            get_template_part("components/block_rewards");
            break;
        case 'block_publications':
            get_template_part("components/block_publications");
            break;
    } ?>
</section>
<?php endwhile;
endif;

get_footer();
?>