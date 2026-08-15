<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= get_bloginfo('description') ?>">
    <meta name="keywords" content="LCDS, La Clinique Du Sourire, cabinet dentaire, studio, clinique dentaire, cabinet dentiste, dentiste">
    <meta name="author" content="Rapkalin">
    <link rel="shortcut icon" href="<?php echo get_stylesheet_directory_uri(); ?>/favicon.ico" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="header-main">
    <div id="header-container" class="header-container">
        <!-- Desktop menu container -->
        <div class="desktop-menus">
            <div class="reduced-navigation">
                <div class="header-logo">
                    <a href="<?= home_url('/'); ?>">
                        <span class="dynamic-logo<?= $args['color-logo'] ?? '' ?>"><?php get_template_part("components/logo-white"); ?></span>
                    </a>
                </div>

                <nav id="navigation">
                    <?php $menuItems = get_header_menu(); ?>
                    <div class="menu-content">
                        <?php foreach ($menuItems as $index => $menuItem): ?>
                            <div class="menu-item<?= $menuItem['children'] ? ' has-children' : '' ?>" data-menu-index="<?= $index ?>">
                                <a class="menu-item-link" href="<?= $menuItem['url'] ?>"><?= $menuItem['title'] ?></a>
                            </div>
                        <?php endforeach; ?>
                        <div id="weglot_here"></div>
                    </div>
                </nav>
            </div>
            <nav class="expanded-navigation">
                <div class="expanded-menu-container">
                    <?php foreach ($menuItems as $index => $menuItem): ?>
                        <div class="expanded-menu-section-container" data-menu-index="<?= $index ?>">
                            <div class="side">
                                <span><?php get_template_part('components/svg-bullet') ?></span>
                                <div class="side-title"><?= $menuItem['title'] ?></div>
                            </div>
                            <?php if (!empty($menuItem['children'])): ?>
                                <div class="expanded-menu-section">
                                    <?php foreach ($menuItem['children'] as $label => $child): ?>
                                        <?php $url = $menuItem['type'] === 'pages' ? $child['url'] : ($menuItem['type'] === 'anchors' ? "{$menuItem['url']}#$child" : '#') ?>
                                        <a class="subtitle" href="<?= $url ?>"><?= $label ?></a>
                                        <?php if ($menuItem['type'] === 'tags' && is_array($child) && !empty($child)): ?>
                                            <div class="tags-container">
                                                <?php foreach ($child as $tag): ?>
                                                    <a
                                                        class="child-item"
                                                        href="<?= $menuItem['url'] . '?filter=' . $tag->slug . '#filters-container';?>"
                                                        data-term-id="<?= $tag->term_id ?>"
                                                        data-term-slug="<?= $tag->slug ?>"
                                                    >
                                                        <?= $tag->name ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if ($menuItem['is_contact']): ?>
                                        <?php get_template_part('components/block_addresses', args: [
                                            'extraClasses' => ['section__addresses__white'],
                                        ]) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>

        <!-- Mobile menu container -->
        <div class="mobile-menus">
            <div class="header-menu-container">
                <!-- Bouton Burger pour Mobile -->
                <div class="header-logo">
                    <a href="<?= home_url('/'); ?>">
                        <span class="dynamic-logo<?= $args['color-logo'] ?? '' ?>"><?php get_template_part("components/logo-white"); ?></span>
                    </a>
                </div>

                <button id="mobile-menu-button" class="mobile-menu-button">
                    <span class="burger-icon active"></span>
                    <span class="close-icon">Fermer</span>
                </button>
            </div>
            <nav id="mobile-navigation" class="mobile-navigation"
                 data-menu-items='<?= htmlspecialchars(json_encode($menuItems), ENT_QUOTES, 'UTF-8') ?>'
            >
                <div class="mobile-menu-content">
                    <!-- Handle first menu level -->
                    <div class="mobile-menu-level" data-level="0">
                        <?php foreach ($menuItems as $index => $menuItem): ?>
                            <div
                                class="mobile-menu-item<?= $menuItem['children'] ? ' has-children' : '' ?>"
                                data-menu-index="<?= $index ?>"
                                data-level="0"
                                <?php if ($menuItem['is_contact']): ?>data-addresses="<?= htmlspecialchars(json_encode(get_addresses()), ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                            >
                                <?php if ($menuItem['children']): ?>
                                    <div class="mobile-menu-item-title">
                                        <?= $menuItem['title'] ?>
                                        <span class="arrow"><?php get_template_part("components/svg-arrow-right"); ?></span>
                                    </div>
                                <?php else: ?>
                                    <a class="mobile-menu-item-link" href="<?= $menuItem['url'] ?>">
                                        <?= $menuItem['title'] ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div id="weglot_here"></div>
                    </div>

                    <!-- Dynamically handled in behavior_menu_mobile -->
                    <div
                        class="mobile-menu-level"
                        data-level="1" style="display: none;"
                    ></div>
                </div>
            </nav>
        </div>
    </div>
</div>
<?php
    $templateName  = basename(get_page_template());
$maxWidth = $templateName !== 'page-projects.php' && get_post_type() !== 'projects' && !is_front_page();
?>
<div class="container <?= $maxWidth ? 'max-width-container' : '' ?>">





