<?php

class Dev7EsgsSettings {

	private $text_domain;

	public function __construct( $text_domain ) {
		$this->text_domain = $text_domain;

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 101 );
		add_action( 'save_post', array( $this, 'meta_box_save' ) );
	}

	public function add_meta_boxes() {
		add_meta_box( 'dev7_esgs_box', __( 'Software GitHub Sync', $this->text_domain ), array(
			$this,
			'render_meta_box',
		), 'download', 'normal', 'core' );
	}

	public function render_meta_box() {
		global $post;

		echo '<input type="hidden" name="dev7_esgs_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';
		echo '<table class="form-table">';

		$edd_file = get_post_meta( $post->ID, '_edd_sl_upgrade_file_key', true );

		$enabled = get_post_meta( $post->ID, '_dev7_esgs_enabled', true ) ? true : false;

		echo '<tr>';
		echo '<td class="edd_field_type_text" colspan="2">';
		echo '<input type="checkbox" name="dev7_esgs_sync_enabled" id="dev7_esgs_sync_enabled" value="1" ' . checked( true, $enabled, false ) . '/>&nbsp;';
		echo '<label for="dev7_esgs_sync_enabled">' . __( 'Check to enable sync with GitHub releases', $this->text_domain ) . '</label>';
		$files = get_post_meta( $post->ID, 'edd_download_files', true );
		$name  = isset( $files[ $edd_file ]['name'] ) ? $files[ $edd_file ]['name'] : '';
		echo '<p><br>' . __( '<strong>Setup:</strong>', $this->text_domain ) . '</p>';
		echo '<ol>';
		echo '<li>' . __( 'On your GitHub repository navigate to: <strong>Settings &gt; Webhooks &amp; services &gt; Add webhook</strong>', $this->text_domain ) . '</li>';
		echo '<li>' . __( 'Enter this <strong>Payload URL:</strong>', $this->text_domain ) . ' <code>' . esc_url( home_url( 'dev7-esgs-webhook/' . $post->ID ) ) . '</code></li>';
		echo '<li>' . __( 'Select <strong>Let me select individual events</strong> and untick "Push" and tick "Release"', $this->text_domain ) . '</li>';
		echo '<li>' . __( 'Add the webhook and check the ping is ok', $this->text_domain ) . '</li>';
		echo '</ol>';
		echo '<p>' . sprintf( __( 'With this setting enabled the <strong>%s</strong> file will automatically be replaced with the ZIP file that is attached to a release when a release is created on GitHub. Note that this is the manually "Attached binaries" and <em>not</em> the generated Zipball. The current version number will also be updated with the "Tag version" from the release.', $this->text_domain ), $name ) . '</p>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}

	function meta_box_save( $post_id ) {
		if ( ! isset( $_POST['dev7_esgs_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['dev7_esgs_meta_box_nonce'], basename( __FILE__ ) ) ) {
			return $post_id;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return $post_id;
		}
		if ( isset( $_POST['post_type'] ) && 'download' != $_POST['post_type'] ) {
			return $post_id;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( isset( $_POST['dev7_esgs_sync_enabled'] ) ) {
			update_post_meta( $post_id, '_dev7_esgs_enabled', true );
		} else {
			delete_post_meta( $post_id, '_dev7_esgs_enabled' );
		}
	}

}