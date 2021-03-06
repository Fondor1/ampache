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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    exit;
}

$results = array();
switch ($_REQUEST['action']) {
    case 'set_instance':
        // Make sure they they are allowed to do this
        if (!Access::check('localplay','5')) {
            debug_event('DENIED','Error attempted to set instance without required level','1');
            exit;
        }

        $type = $_REQUEST['instance'] ? 'localplay' : 'stream';

        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->set_active_instance($_REQUEST['instance']);
        Preference::update('play_type',$GLOBALS['user']->id,$type);

        // We should also refesh the sidebar
        ob_start();
        require_once AmpConfig::get('prefix') . '/templates/sidebar.inc.php';
        $results['sidebar-content'] = ob_get_contents();
        ob_end_clean();
    break;
    case 'command':
        // Make sure they are allowed to do this
        if (!Access::check('localplay','50')) {
            debug_event('DENIED','Attempted to control Localplay without sufficient access','1');
            exit;
        }

        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();

        // Switch on valid commands
        switch ($_REQUEST['command']) {
            case 'prev':
            case 'next':
            case 'stop':
            case 'play':
            case 'pause':
                $command = scrub_in($_REQUEST['command']);
                $localplay->$command();
            break;
            case 'volume_up':
            case 'volume_down':
            case 'volume_mute':
                $command = scrub_in($_REQUEST['command']);
                $localplay->$command();

                // We actually want to refresh something here
                ob_start();
                $objects = $localplay->get();
                require_once AmpConfig::get('prefix') . '/templates/show_localplay_status.inc.php';
                $results['localplay_status'] = ob_get_contents();
                ob_end_clean();
            break;
            case 'delete_all':
                $localplay->delete_all();
                ob_start();
                $browse = new Browse();
                $browse->set_type('playlist_localplay');
                $browse->set_static_content(true);
                $browse->save_objects(array());
                $browse->show_objects(array());
                $browse->store();
                $results[$browse->get_content_div()] = ob_get_contents();
                ob_end_clean();
            break;
            case 'skip':
                $localplay->skip(intval($_REQUEST['id']));
                $objects = $localplay->get();
                ob_start();
                $browse = new Browse();
                $browse->set_type('playlist_localplay');
                $browse->set_static_content(true);
                $browse->save_objects($objects);
                $browse->show_objects($objects);
                $browse->store();
                $results[$browse->get_content_div()] = ob_get_contents();
                ob_end_clean();
            break;
            default:
                // Nothing
            break;
        } // end whitelist

    break;
    case 'delete_track':
        // Load Connect... yada yada
        if (!Access::check('localplay','50')) {
            debug_event('DENIED','Attempted to delete track without access','1');
            exit;
        }
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();

        // Scrub in the delete request
        $id = intval($_REQUEST['id']);

        $localplay->delete_track($id);

        // Wait in case we just deleted what we were playing
        sleep(3);
        $objects = $localplay->get();
        $status = $localplay->status();

        ob_start();
        $browse = new Browse();
        $browse->set_type('playlist_localplay');
        $browse->set_static_content(true);
        $browse->save_objects($objects);
        $browse->show_objects($objects);
        $browse->store();
        $results[$browse->get_content_div()] = ob_get_contents();
        ob_end_clean();

    break;
    case 'delete_instance':
        // Make sure that you have access to do this...
        if (!Access::check('localplay','75')) {
            debug_event('DENIED','Attempted to delete instance without access','1');
            exit;
        }

        // Scrub it in
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->delete_instance($_REQUEST['instance']);

        $key = 'localplay_instance_' . $_REQUEST['instance'];
        $results[$key] = '';
    break;
    case 'repeat':
        // Make sure that they have access to do this again no clue
        if (!Access::check('localplay','50')) {
            debug_event('DENIED','Attempted to set repeat without access','1');
            exit;
        }

        // Scrub her in
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();
        $localplay->repeat(make_bool($_REQUEST['value']));

        ob_start();
        $objects = $localplay->get();
        require_once AmpConfig::get('prefix') . '/templates/show_localplay_status.inc.php';
        $results['localplay_status'] = ob_get_contents();
        ob_end_clean();

    break;
    case 'random':
        // Make sure that they have access to do this
        if (!Access::check('localplay','50')) {
            debug_event('DENIED','Attempted to set random without access','1');
            exit;
        }

        // Scrub her in
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();
        $localplay->random(make_bool($_REQUEST['value']));

        ob_start();
        $objects = $localplay->get();
        require_once AmpConfig::get('prefix') . '/templates/show_localplay_status.inc.php';
        $results['localplay_status'] = ob_get_contents();
        ob_end_clean();

    break;
    default:
        $results['rfc3514'] = '0x1';
    break;
} // switch on action;

// We always do this
echo xoutput_from_array($results);
