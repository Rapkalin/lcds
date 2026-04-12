<?php
/**
 * Template Name: Page équipe
 *
 * @package WordPress
 */

get_header(args:['color-logo' => '__grey']);
?>

<div class="page-header-container">
    <h1 class="title"><?= get_field('title') ?></h1>
    <div class="description"><?= get_field('description') ?></div>
</div>

<div class="section-people-wrapper main-wrapper">
    <section class="section-block-people">
        <?php foreach (get_field('departments') as $department): ?>
            <div class="accordion-block block-people">
                <h3 class="accordion-title block-people-title">
                    <?= $department['title'] ?>
                    <span class="accordion-icon">-</span>
                </h3>
                <div class="accordion-content">
                    <div class="block-people-container">
                    <?php foreach ($department['team'] as $i => $member): ?>
                        <?php
                            $srcset = wp_get_attachment_image_srcset( $member['image']['ID']);
                            $isPopUpActive = isset($member['popup_description']) && $member['popup_description'];
                        ?>
                        <div
                            class="people-details <?= $isPopUpActive ? 'popup-active' : '' ?>"
                        >
                            <div class="people-image-button">
                                <?php if ($isPopUpActive): ?>
                                    <button class="people-button classic-button">En savoir plus</button>
                                <?php endif; ?>
                                <img
                                    class="people-image"
                                    src="<?= esc_url($member['image']['url']) ?>"
                                    srcset="<?php echo esc_attr( $srcset ); ?>"
                                    alt="<?= esc_attr($member['image']['title']) ?>"
                                    width="<?= $member['image']['width'] ?>"
                                    height="<?= $member['image']['height'] ?>"
                                >
                            </div>
                            <div class="people-name"><?= esc_attr($member['title']) ?></div>
                            <div class="people-description"><?= esc_attr($member['description']) ?></div>
                        </div>

                        <?php if ($isPopUpActive): ?>
                            <div class="people-popup" id="people-popup-<?= $i ?>" hidden>
                                <div class="popup-content">
                                    <button class="popup-close">&times;</button>
                                    <img src="<?= esc_url($member['image']['url']) ?>" alt="" class="popup-image">
                                    <h4 class="popup-name"><?= esc_attr($member['title']) ?></h4>
                                    <p class="popup-description"><?= $member['popup_description'] ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<?php
get_footer();
?>
