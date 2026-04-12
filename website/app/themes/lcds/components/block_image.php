<?php
$image = get_sub_field('image');
if( $image ): ?>
    <?php
        $srcset = wp_get_attachment_image_srcset( $image['ID']);
        $align = get_sub_field('image_alignment');
        $size = get_sub_field('image_size') ? 'big' : 'small';
    ?>
    <section class="section-block-article-image img-align-<?= $align ?> img-size-<?= $size ?>">
        <img
            src="<?php header_image(); ?>"
            srcset="<?php echo esc_attr( $srcset ); ?>"
            alt="<?= $image['alt'] ?? $image['title'] ?>"
            width="<?= $image['width'] ?>"
            height="<?= $image['height'] ?>"
        >
        <span class="img-credit"><?= get_sub_field('credit') ?></span>
    </section>
<?php endif; ?>
