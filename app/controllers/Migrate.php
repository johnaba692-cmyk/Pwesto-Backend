<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Runs pending migrations from app/migrations with the LavaLust Migration library.
 *
 * Only available on the local development server: set
 * $config['migration_enabled'] = TRUE in app/config/migration.php, open
 * http://127.0.0.1:3000/migrate, then set it back to FALSE.
 */
class Migrate extends Controller
{
    public function index()
    {
        $this->config->load('migration');
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
        if (config_item('environment') !== 'development' || !$isLocal || !config_item('migration_enabled')) {
            show_404();
        }
        header('Content-Type: text/plain; charset=utf-8');
        $this->call->library('migration');
        $this->migration->migrate();
    }
}
