<?php
/**
 * GeneratePress Child Theme - V-ism +knasy
 * 
 * Podcast platform with Firebase Storage integration
 */

// Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

// Security settings
require_once __DIR__ . '/inc/security.php';

// Firebase Storage integration
require_once __DIR__ . '/inc/firebase.php';

// Admin: Audio upload UI
require_once __DIR__ . '/inc/admin-audio-upload.php';

// Admin: Post ID column
require_once __DIR__ . '/inc/admin-post-columns.php';

// Frontend UI components
require_once __DIR__ . '/inc/frontend-ui.php';






