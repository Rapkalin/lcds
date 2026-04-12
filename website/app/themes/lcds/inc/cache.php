<?php

// Invalidate cache when a menu is updated
add_action('wp_update_nav_menu', function() {
    delete_transient('custom_menu_items_*'); // Delete all transients starting with custom_menu_items_
});

// Invalidate cache when article/une page is updated
add_action('save_post', function() {
    delete_transient('custom_menu_items_*');
});
