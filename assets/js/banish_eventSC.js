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

var aad_mmipadfunpage = {
	events: new Array,
	
	init: function($) {
		aad_mmipadfunpage.events = aad_mmipadfunpage.get_events($);
		$('.banish-event .weapon-inventory').change(function() {
			aad_mmipadfunpage.upate_banish_total();
		});
	},
	
	get_events: function($) {
		var events = new Array;
		
		$('.banish-event').each(function() {
			var roamers = aad_mmipadfunpage.get_roamers($, this);
			
			events.push({
				instance: this.getAttribute('data-instance'),
				roamers: roamers,
				banish_list: aad_mmipadfunpage.get_banish_list(roamers)
			});
		});
		
		return events;
	},
	
	/**
	 * Build list of roamers from HTML content
	 * 
	 * @param {object} $ jQuery reference
	 * @param {object} d jQuery object for div to search
	 * @returns {array} Array of Roamers
	 */
	get_roamers: function($, d) {
		var roamers = new Array;
		
		/**
		 * Find roamers based on field used to report banishable count
		 * 
		 * Data includes which weapon is used to banish the roamer and how many are required
		 */
		$(d).find('.banish-roamer-cnt').each(function(index,row) {
			roamers[index] = {
				id: row.getAttribute('id'),
				weapon: row.getAttribute('data-weapon'),
				uses: parseInt(row.getAttribute('data-uses')) || 1
			};
		});
		
		return roamers;
	},
	
	/**
	 * Build list of which roamers to use when calculating total number of roamers that can be banished
	 * 
	 * A weapon may be able to banish several different roamers with differing effieciency.
	 * 
	 * @param {array} roamers List of roamers
	 * @returns {array} List of weapons indicating which roamer to banish
	 */
	get_banish_list: function(roamers) {
		var banish_list = {};
		roamers.forEach(function(roamer) {
			var weapon = roamer.weapon;

			if (banish_list.hasOwnProperty(weapon)){
				if (roamer.uses < banish_list[weapon].uses) {
					banish_list[weapon] = {
						uses: roamer.uses,
						roamer: roamer.id						
					};
				}
			} else {
				banish_list[weapon] = {
					uses: roamer.uses,
					roamer: roamer.id
				};
			}
		});
		
		return banish_list;
	},
	
	upate_banish_total: function() {
		function getInventory(weaponID) {
			inventory = parseInt(jQuery("input[id='" + weaponID + "']").val()) || 0;
			inventory = inventory < 0 ? 0 : inventory;
			return inventory;
		}
				
		aad_mmipadfunpage.events.forEach(function(event){
			/**
			 * Update number of roamers banished by each weapon in inventory
			 */
			event.roamers.forEach(function(roamer) {
				var inventory = getInventory(roamer.weapon);
				var banish = Math.floor(inventory / roamer.uses);
				jQuery("span[id='" + roamer.id + "']").html(banish);
			});
			
			/**
			 * Update max number of roamers that can be banished
			 */
			var total = 0;
			for (var weaponID in event.banish_list) {
				inventory = getInventory(weaponID);
				inventory = inventory < 0 ? 0 : inventory;
				uses = event.banish_list[weaponID].uses;
				total += Math.floor(inventory / uses);
			};
			
			jQuery("span[id='banish-total-" + event.instance + "']").html(total);
		});
	}
};

jQuery(document).ready(function ($) {
	aad_mmipadfunpage.init($);
});