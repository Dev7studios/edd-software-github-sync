<?php

class Dev7EsgsWebhooks {

	private $text_domain;
	private $rewrite_rule = 'dev7-esgs-webhook/([0-9]+?)/?$';

	public function __construct( $text_domain ) {
		$this->text_domain = $text_domain;

		add_action( 'wp_loaded', array( $this, 'flush_rules' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'insert_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'insert_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'listen' ) );
	}

	public function flush_rules() {
		$rules = get_option( 'rewrite_rules' );

		if ( ! isset( $rules[ $this->rewrite_rule ] ) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}

	public function insert_rewrite_rules( $rules ) {
		$newrules                        = array();
		$newrules[ $this->rewrite_rule ] = 'index.php?dev7_esgs_webhook=$matches[1]';

		return $newrules + $rules;
	}

	public function insert_query_vars( $vars ) {
		array_push( $vars, 'dev7_esgs_webhook' );

		return $vars;
	}

	public function listen() {
		$post_id = get_query_var( 'dev7_esgs_webhook' );
		if ( ! $post_id || ! is_numeric( $post_id ) ) {
			return;
		}

		$enabled = get_post_meta( $post_id, '_dev7_esgs_enabled', true ) ? true : false;
		if ( ! $enabled ) {
			echo __( 'Error: Software GitHub Sync disabled', $this->text_domain ) . "\n";
			exit;
		}

		$data = json_decode( file_get_contents( 'php://input' ) );
		if ( ! isset( $data->release ) ) {
			echo __( 'Error: Invalid data', $this->text_domain ) . "\n";
			exit;
		}

		$version = isset( $data->release->tag_name ) ? $data->release->tag_name : '';
		$zip_url = isset( $data->release->assets[0]->browser_download_url ) ? $data->release->assets[0]->browser_download_url : '';

		if ( $zip_url ) {
			// Check for ZIP
			$file_parts = pathinfo( basename( $zip_url ) );
			if ( $file_parts['extension'] != 'zip' ) {
				echo __( 'Error: Release attachment is not a ZIP', $this->text_domain ) . "\n";
				exit;
			}

			// Download file
			echo sprintf( __( 'Downloading %s...', $this->text_domain ), $zip_url ) . "\n";
			$response = wp_remote_get( $zip_url );
			if ( is_wp_error( $response ) ) {
				echo $response->get_error_message();
				exit;
			}

			$wp_upload_dir = wp_upload_dir();
			$upload_path   = edd_get_upload_dir();
			$upload_path .= $wp_upload_dir['subdir'] . '/';
			wp_mkdir_p( $upload_path );
			$filename = basename( $zip_url );
			$filename = wp_unique_filename( $upload_path, $filename );

			if ( @file_put_contents( $upload_path . $filename, $response['body'] ) === false ) {
				echo sprintf( __( 'Error downloading %s', $this->text_domain ), $zip_url ) . "\n";
				exit;
			}

			// Update EDD attachment
			$edd_file = get_post_meta( $post_id, '_edd_sl_upgrade_file_key', true );
			$files    = edd_get_download_files( $post_id );
			if ( isset( $files[ $edd_file ] ) ) {
				$base_url = $wp_upload_dir['baseurl'] . str_replace( $wp_upload_dir['basedir'], '', $upload_path );

				// Add attachment
				$filetype      = wp_check_filetype( basename( $filename ), null );
				$attachment    = array(
					'guid'           => $base_url . $filename,
					'post_mime_type' => $filetype['type'],
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);
				$attach_id     = wp_insert_attachment( $attachment, $filename, $post_id );
				$attached_file = ltrim( str_replace( $wp_upload_dir['basedir'], '', $upload_path ), '/' ) . $filename;
				update_post_meta( $attach_id, '_wp_attached_file', $attached_file );
				echo sprintf( __( 'Attachment %s added', $this->text_domain ), $attached_file ) . "\n";

				$files[ $edd_file ]['attachment_id'] = $attach_id;
				$files[ $edd_file ]['name']          = $filename;
				$files[ $edd_file ]['file']          = $base_url . $filename;
			}

			update_post_meta( $post_id, 'edd_download_files', $files );
			echo __( 'File updated', $this->text_domain ) . "\n";

			// Update EDD SL version
			update_post_meta( $post_id, '_edd_sl_version', $version );
			echo __( 'Version updated', $this->text_domain ) . "\n";
		}

		echo __( 'Finished', $this->text_domain ) . "\n";
		exit;
	}

}