<?php
/**
 * SocialPilot AI - Configuration
 * Copy this file to config.php and update values
 */

define('APP_NAME', 'SocialPilot AI');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');
define('APP_ENV', 'production'); // development | production
define('APP_DEBUG', false);
define('APP_TIMEZONE', 'Europe/Istanbul');
define('APP_LOCALE', 'tr_TR');

// Database
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'socialpilot_ai');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security
define('SECRET_KEY', 'CHANGE_THIS_TO_RANDOM_64_CHAR_STRING');
define('CSRF_TOKEN_NAME', '_csrf_token');
define('SESSION_LIFETIME', 7200); // 2 hours
define('PASSWORD_MIN_LENGTH', 8);

// Upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/quicktime']);
define('ALLOWED_DOC_TYPES', ['application/pdf']);

// OpenAI
define('OPENAI_API_KEY', '');
define('OPENAI_MODEL', 'gpt-4o-mini');

// Facebook / Instagram Graph API
define('FACEBOOK_APP_ID', '');
define('FACEBOOK_APP_SECRET', '');
define('FACEBOOK_REDIRECT_URI', APP_URL . '/api/oauth/facebook.php');

// LinkedIn API
define('LINKEDIN_CLIENT_ID', '');
define('LINKEDIN_CLIENT_SECRET', '');
define('LINKEDIN_REDIRECT_URI', APP_URL . '/api/oauth/linkedin.php');

// WordPress
define('WORDPRESS_REDIRECT_URI', APP_URL . '/api/oauth/wordpress.php');

// Email (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', 'noreply@socialpilot.ai');
define('SMTP_FROM_NAME', 'SocialPilot AI');

// Telegram
define('TELEGRAM_BOT_TOKEN', '');

// WhatsApp API
define('WHATSAPP_API_URL', '');
define('WHATSAPP_API_TOKEN', '');

// Cron
define('CRON_SECRET_KEY', 'CHANGE_THIS_CRON_SECRET');

// Auto Mode Defaults
define('AUTO_MORNING_TIME', '09:00');
define('AUTO_EVENING_TIME', '18:00');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', __DIR__);
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('ADMIN_PATH', ROOT_PATH . '/admin');
