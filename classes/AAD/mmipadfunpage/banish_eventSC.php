<?php

/*
 * Copyright (C) 2017 Kenneth J. Brucker <ken.brucker@action-a-day.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace AAD\mmipadfunpage;

/**
 * Description of banish_eventSC
 *
 * @package mmipadfunpage
 * @author Kenneth J. Brucker <ken.brucker@action-a-day.com>
 */
/*
 *  Protect from direct execution
 */
if ( !defined( 'WP_PLUGIN_DIR' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	die( 'I don\'t think you should be here.' );
}

class banish_eventSC {

	/**
	 * @var string Plugin version
	 */
	private $version;

	/**
	 * @var array hash of asset paths
	 */
	private $urls;

	/**
	 * Instantiate class
	 *
	 * @param string $version Plugin version
	 * @param array hash of asset paths used by the plugin
	 * @return void
	 */
	public function __construct( $version, $urls ) {
		$this->version	 = $version;
		$this->urls		 = $urls;
		return;
	}

	/**
	 * Plug into WP
	 *
	 * @param void
	 * @return void
	 */
	public function run() {
		/**
		 * Register scripts and CSS
		 */
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );

		/**
		 * Add Shortcode to display roamers calculation form
		 */
		add_shortcode( 'banish_event', array( $this, 'sc_banish_event' ) );

		add_action( 'template_redirect', array( $this, 'enqueue_css' ) );
	}

	/**
	 * Register Javascript and CSS
	 */
	public function register_scripts() {
		wp_register_script( 'aad-mmipadfunpage-banish-eventSC', // Handle
					  $this->urls['js'] . 'banish_eventSC.js', // URL to .js file
					  array( 'jquery-core' ), // Dependencies
					  $this->version, // Script version
					  true		  // Place in footer
		);
		wp_register_style( 'aad-mmipadfunpage-banish-event-css', // Handle
					 $this->urls['css'] . 'banish_event.css', // URL to CSS file
					 array(), // No Dependencies
					 $this->version		// Script version
		);
	}

	/**
	 * If the current page/post includes the shortcode, enqueue CSS file.
	 *
	 * @param void
	 * @return void
	 */
	public function enqueue_css() {
		if ( is_singular() ) {
			$post = get_post();

			if ( has_shortcode( $post->post_content, 'banish_event' ) ) {
				wp_enqueue_style( 'aad-mmipadfunpage-banish-event-css' );
			}
		}
	}

	/**
	 * Display roamers form
	 *
	 * @param array $_attrs Shortcode attribute array
	 * @param string $content contents between shortcode tags
	 * @return string HTML content
	 */
	public function sc_banish_event( $_attrs, $content = null ) {
		/**
		 * @var int Short code instance number
		 */
		static $instance = 0;

		/**
		 * Split input, drop blank lines and remove html tags that wordpress may have added
		 */
		$lines = array_filter( explode( "\n", strip_tags( $content ) ) );

		if ( count( $lines ) < 1 ) {
			return $content;
		}

		$output = "<div class='banish-event' data-instance='$instance'>";
		wp_enqueue_script( 'aad-mmipadfunpage-banish-eventSC' );

		/**
		 * Input is in form "Roamer, Weapon, Count"
		 * Roamer is unique, Weapon may duplicate
		 */
		$banish_weapons = array();
		foreach ( $lines as $line ) {
			$fields = array_map( "trim", explode( ',', $line ) );
			if ( count( $fields ) <> 3 ) {
				// illegal line
				continue;
			}

			$weapon_id = sanitize_key( html_entity_decode( $fields[1] ) ) . '_' . $instance;
			if ( !key_exists( $weapon_id, $banish_weapons ) ) {
				$banish_weapons[$weapon_id]			 = array();
				$banish_weapons[$weapon_id]['name']	 = $fields[1];
				$banish_weapons[$weapon_id]['roamers'] = array();
			}
			$banish_weapons[$weapon_id]['roamers'][] = array(
				'name'	 => $fields[0],
				'id'	 => sanitize_html_class( html_entity_decode( $fields[0] ) ) . '_' . $instance,
				'uses'	 => (int) $fields[2]
			);
		}

		/**
		 * Create form to collect user inventory for each weapon
		 */
		$output	 .= '<form class="banish-event-form">';
		$output	 .= '<h1>Enter Your Inventory:</h1>';
		$output	 .= '<fieldset class="banish-event-inventory">';
		foreach ( $banish_weapons as $weapon_id => $weapon ) {
			$output	 .= '<div>';
			$output	 .= '<input id="' . $weapon_id . '" type="number" value="0" min="0" class="weapon-inventory">';
			$output	 .= '<label for="' . $weapon_id . '">' . esc_html( $weapon['name'] ) . '</label>';
			$output	 .= '</div>';
		}
		$output .= '</fieldset></form>';

		/**
		 * A weapon can banish multiple roamers but may require different number of weapons to do so.
		 * To get a maximum of how many could be banished, need to know for each weapon, which roamer
		 * is is banished the best.
		 *
		 * While creating list of weapons further below and how many roamers each can banish also keep track of which
		 * roamers require the least weapons to banish to build the total. The list will be generated in
		 * $banish_total[weapon_id]={roamer_id, uses};
		 */
		$output .= "<h1 class='can-banish'>You can Banish <span id='banish-total-${instance}'>0</span> Roamers</h1>";

		/**
		 * Create list of weapons and how many roamers each weapon can banish
		 */
		$output .= '<dl class="banish-event-roamers">';

		foreach ( $banish_weapons as $weapon_id => $weapon ) {
			$separator	 = false;
			$output		 .= '<dt class="weapon">Using your ' . esc_html( $weapon['name'] ) . ':<dd>';
			foreach ( $weapon['roamers'] as $roamer ) {
				if ( $separator ) {
					$output .= ' <span class="banish-separator">OR</span> ';
				}
				$uses		 = (integer) $roamer['uses'];
				$output		 .= '<span class="banish-roamer">';
				$output		 .= "<span id='${roamer['id']}' data-weapon='$weapon_id' data-uses='$uses' class='banish-roamer-cnt'>0</span> ";
				$output		 .= esc_html( $roamer['name'] ) . " (uses $uses)";
				$output		 .= '</span>';
				$separator	 = true;
			}
		}

		$output .= '</dl></div>';

		$instance++;
		return $output;
	}

}
