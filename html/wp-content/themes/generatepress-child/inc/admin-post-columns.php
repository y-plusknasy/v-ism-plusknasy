<?php
/**
 * Admin Post Columns Customization
 * 
 * 投稿一覧画面のカスタマイズ（ID列の追加）
 */

/**
 * 投稿一覧にID列を追加（Firebase管理用）
 */
function add_post_id_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'title') {
            $new_columns['post_id'] = 'ID';
        }
        $new_columns[$key] = $value;
    }
    return $new_columns;
}
add_filter('manage_posts_columns', 'add_post_id_column');

function show_post_id_column($column, $post_id) {
    if ($column === 'post_id') {
        echo '<strong>' . $post_id . '</strong>';
    }
}
add_action('manage_posts_custom_column', 'show_post_id_column', 10, 2);

function make_post_id_column_sortable($columns) {
    $columns['post_id'] = 'ID';
    return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'make_post_id_column_sortable');
