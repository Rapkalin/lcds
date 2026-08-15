    </div>

    <div id="footer-container" class="main-wrapper">
        <div id="footer-logo">
            <a href="<?= home_url('/'); ?>">
                <?php get_template_part('components/logo-lcds') ?>
            </a>
        </div>

        <?php get_template_part('components/block_addresses') ?>

        <div class="footer-navigation">
            <nav id="footer-main-navigation">
                <ul class="menu-content">
                    <?php wp_nav_menu([
                        'theme_location' => 'footer-menu',
                        'menu_id' => 'footer-menu',
                        'items_wrap' => '%3$s',
                        'container' => false,
                    ]); ?>
                </ul>
            </nav>
            <nav id="footer-social-media-navigation">
                <ul class="menu-content">
                    <?php wp_nav_menu([
                        'theme_location' => 'social-menu',
                        'menu_id' => 'social-menu',
                        'items_wrap' => '%3$s',
                        'container' => false,
                    ]); ?>
                </ul>
            </nav>
        </div>

        <div class="footer-legal">
            <div class="no-numbers-animation">© <?= date('Y') ?> LCDS</div>
            <nav id="footer-legal-navigation">
                <ul class="menu-content">
                    <?php wp_nav_menu([
                        'theme_location' => 'legal-menu',
                        'menu_id' => 'legal-menu',
                        'items_wrap' => '%3$s',
                        'container' => false,
                    ]); ?>
                </ul>
            </nav>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>