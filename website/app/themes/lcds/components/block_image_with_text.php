<?php $withText = get_sub_field('text') ?>
<section class="section-block-image-with-text">
    <div
        class="block-image <?= $withText ? 'with-text' : 'no-text' ?>"
        style="background-image: url(<?= get_sub_field('image')['url']; ?>);"
    >
        <?php if($withText): ?>
            <h2 class="block-text">
                <?php get_template_part('components/svg-quote') ?>
                <?= nl2br($withText) ?>
            </h2>
        <?php endif; ?>
    </div>
</section>