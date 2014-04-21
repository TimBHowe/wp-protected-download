<?php
/*
Plugin Name: Protected Download
Plugin URI:
Description: Add shotecode for creating links to pass attachment id's to a form for a user to complete before downloading the attachment.
Version: 0.1
Author: TimBHowe
Author Email: tim@hallme.com
License:

  Copyright 2011 TimBHowe (tim@hallme.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

class ProtectedDownload {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'Protected Download';
	const slug = 'protected_download';

	/**
	 * Constructor
	 */
	function __construct() {
		//register an activation hook for the plugin
		register_activation_hook( __FILE__, array( &$this, 'install_protected_download' ) );

		//Hook up to the init action
		add_action( 'init', array( &$this, 'init_protected_download' ) );
	}

	/**
	 * Runs when the plugin is activated
	 */
	function install_protected_download() {
		// do not generate any output here
	}

	/**
	 * Runs when the plugin is initialized
	 */
	function init_protected_download() {

		// Register the shortcode [protected]
		add_shortcode( 'protected', array( &$this, 'protect_shortcode' ) );
		add_shortcode( 'attachment_url', array( &$this, 'unprotect_shortcode' ) );
		add_shortcode( 'referral_link', array( &$this, 'referral_back_shortcode' ) );

		if ( is_admin() ) {
			//this will run when in the WordPress admin
			add_action( 'print_media_templates', array( $this, 'media_template_updates' ) );
		} else {
			//this will run when on the frontend
		}
	}

	//Add a file id field to the media library to allow users to add it to the short code
	function media_template_updates(){ ?>
		<script type="text/html" id="tmpl-my-custom-attachment-display-setting">
			<label class="setting">
				<span><?php _e('File ID'); ?></span>
				<input type="text" value="{{data.fileid}}" readonly />
			</label>
		</script>

		<script>
		jQuery(document).ready(function(){
			// merge default gallery settings template with yours
			wp.media.view.Settings.AttachmentDisplay = wp.media.view.Settings.AttachmentDisplay.extend({
				//re-render the attachment options with the new id field
				render: function() {
					var attachment = this.options.attachment;
					if ( attachment ) {
						_.extend( this.options, {
							fileid: attachment.get('id'),
							sizes: attachment.get('sizes'),
							type:  attachment.get('type')
						});
					}

					wp.media.view.Settings.prototype.render.call( this );
					return this;
				},

				template: function(view){
					return wp.media.template('attachment-display-settings')(view)
					+ wp.media.template('my-custom-attachment-display-setting')(view);
				}
			});
		});
		</script>
	<?php
	}

	//Add [protected] shortcode
	function protect_shortcode($atts, $content=null ) {
		//Set attributes for shortcode
		extract( shortcode_atts( array(
			'file_id' => '',
			'form_url' => '',
		), $atts ) );

		$file_info =  get_post( $file_id );

		$link_href = add_query_arg( 'file_id', urlencode($file_id), $form_url );

		return '<a class="protected-file" title="Request '.$file_info->post_title.'" href="'.$link_href.'">' . $content . '</a>';
	}

	//Add [attachment_url] shortcode - to link to the file using the attachment ID. Used on the thank you page or user email notification
	function unprotect_shortcode($atts, $content=null ) {
		//Set attributes for shortcode
		extract( shortcode_atts( array(
			'file_id' => $_GET['file_id'],
		), $atts ) );

		$file_name =  get_the_title( $file_id );


		if (!$content && $file_name){
			$content = $file_name;
		}

		return '<a class="unprotected-file" title="Download '.$file_name.'" href="'.wp_get_attachment_url($file_id).'">' . $content . '</a>';
	}

	//Add [referral_link] shortcode - to link to back to the original page that had the protected link on it.
	function referral_back_shortcode($atts, $content=null ) {
		//Set attributes for shortcode
		extract( shortcode_atts( array(
			'ref_url' => $_GET['ref_url'],
		), $atts ) );

		$ref_url = urldecode($ref_url);
		$post_id =  url_to_postid( $ref_url );
		$post_title =  get_the_title( $post_id );


		if (!$content && $post_title){
			$content = $post_title;
		}

		return '<a class="back-link" title="'.$post_title.'" href="'.$ref_url.'">Back to ' . $content . '</a>';
	}

} // end class
new ProtectedDownload();