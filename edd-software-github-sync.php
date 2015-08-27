<?php
/**
 * Plugin Name: Easy Digital Downloads - Software GitHub Sync
 * Plugin URI: http://dev7studios.com
 * Description: Sync GitHub releases with EDD downloads that use Software Licensing
 * Version: 0.1.0
 * Author: Dev7studios
 * Author URI: http://dev7studios.com
 * Text Domain: dev7-esgs
 * Domain Path: lang
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dev7EDDSoftwareGitHubSync {

	private $text_domain = 'dev7-esgs';
	private $plugin_path;
	private $plugin_url;

	public function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );

		load_plugin_textdomain( $this->text_domain, false, $this->plugin_path . 'lang' );
		$this->loadIncludes();
		new Dev7EsgsWebhooks( $this->text_domain );

		add_action( 'init', array( $this, 'init' ) );
	}

	protected function loadIncludes() {
		require_once $this->plugin_path . 'includes/Dev7EsgsWebhooks.php';
		require_once $this->plugin_path . 'includes/Dev7EsgsSettings.php';
	}

	public function init() {
		new Dev7EsgsSettings( $this->text_domain );
	}

}

new Dev7EDDSoftwareGitHubSync();