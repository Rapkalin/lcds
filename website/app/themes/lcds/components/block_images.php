<?php
$images = get_sub_field('images');
$align = get_sub_field('align');
$size = 'full'; // (thumbnail, medium, large, full or custom size)
if( $images ): ?>
    <section class="section-block-gallery-images main-wrapper <?= count($images) > 1 ? 'gallery-images' : 'gallery-image' ?>">
        <?php foreach( $images as $i => $image ):
            $srcset = wp_get_attachment_image_srcset($image['ID']);

            if (count($images) > 1) {
                $align = $i === 0 ? $align : ( $align === 'right' ? 'left' : 'right' );
            } else {
                $align = 'default';
            }
        ?>
            <img
                src="<?php header_image() ?>"
                srcset="<?= esc_attr( $srcset ) ?>"
                alt="<?= $image['alt'] ?? $image['title'] ?>"
                width="<?= $image['width'] ?>"
                height="<?= $image['height'] ?>"
                class="align-<?= $align ?>"
            >
        <?php endforeach; ?>
    </section>
<?php endif; ?>
