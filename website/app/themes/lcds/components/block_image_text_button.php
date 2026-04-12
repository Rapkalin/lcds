<section
        class="section-block-image-text"
        style="background-image: url(<?= get_sub_field('image')['url']; ?>)"
>
    <div class="container">
        <h2 class="title"><?= get_sub_field('title') ?></h2>
        <div class="description"><?= get_sub_field('description') ?></div>
        <?php get_template_part('components/button') ?>
    </div>
</section>