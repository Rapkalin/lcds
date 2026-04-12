<?php
$image = get_sub_field('image');
if( $image ): ?>
    <section class="section-block-image-full-width">
        <?php $srcset = wp_get_attachment_image_srcset( $image['ID']); ?>
        <img
            src="<?php header_image(); ?>"
            srcset="<?php echo esc_attr( $srcset ); ?>"
            alt="<?= $image['alt'] ?? $image['title'] ?>"
            width="<?= $image['width'] ?>"
            height="<?= $image['height'] ?>"
        >
    </section>
<?php endif; ?>
