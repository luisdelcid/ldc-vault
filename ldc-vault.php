<?php
/**
 * Author: Luis del Cid
 * Author URI: https://luisdelcid.com
 * Description: A collection of useful functions for your WordPress theme's functions.php.
 * Domain Path:
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network:
 * Plugin Name: LDC Vault
 * Plugin URI: https://luisdelcid.com
 * Text Domain: ldc-vault
 * Version: 2019.11.26.3
 *
 */ // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	defined('ABSPATH') or die('No script kiddies please!');

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    require_once(plugin_dir_path(__FILE__) . 'includes/plugin-update-checker-4.8.1/plugin-update-checker.php');
    Puc_v4_Factory::buildUpdateChecker('https://github.com/luisdelcid/ldc-vault', __FILE__, 'ldc-vault');

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	add_action('plugins_loaded', function(){
        if(defined('LDC_Vault') or defined('LDC_Vault_Version')){
            add_action('admin_notices', function(){
				printf('<div class="notice notice-error"><p>LDC Vault already exists.</p></div>');
			});
			deactivate_plugins(plugin_basename(__FILE__));
		} else {
            define('LDC_Vault', __FILE__);
			define('LDC_Vault_Version', '2019.11.26.3');
			require_once(plugin_dir_path(LDC_Vault) . 'class-ldc-vault.php');
            require_once(plugin_dir_path(LDC_Vault) . 'functions.php');
        }
	});
