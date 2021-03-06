<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class AmpacheShoutHome {
    public $name           = 'Shout Home';
    public $categories     = 'home';
    public $description    = 'Shoutbox on homepage';
    public $url            = '';
    public $version        = '000001';
    public $min_ampache    = '370021';
    public $max_ampache    = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $maxitems;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct() {
        return true;
    }

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install() {
        // Check and see if it's already installed
        if (Preference::exists('shouthome_max_items')) { return false; }

        Preference::insert('shouthome_max_items','Shoutbox on homepage max items','5','25','integer','plugins');

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall() {
        Preference::delete('shouthome_max_items');

        return true;
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade() {
        return true;
    }

    /**
     * display_home
     * This display the module in home page
     */
    public function display_home() {
        if (AmpConfig::get('sociable')) {
            echo "<div id='shout_objects'>\n";
            $shouts = Shoutbox::get_top($this->maxitems);
            if (count($shouts)) {
                require_once AmpConfig::get('prefix') . '/templates/show_shoutbox.inc.php';
            }
            echo "</div>\n";
        }
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     */
    public function load($user) {
        $user->set_preferences();
        $data = $user->prefs;

        $this->maxitems = intval($data['shouthome_max_items']);
        if ($this->maxitems < 1) {
            $this->maxitems = 5;
        }

        return true;
    }
}
