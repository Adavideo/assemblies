<?php
/**
 * Elgg assemblies plugin
 *
 * @package ElggFile
 */

elgg_register_event_handler('init', 'system', 'assemblies_init');

/**
 * File plugin initialization functions.
 */
function assemblies_init() {

	// register a library of helper functions
	elgg_register_library('elgg:assemblies', elgg_get_plugins_path() . 'assemblies/lib/assemblies.php');

	// Site navigation
	$item = new ElggMenuItem('assemblies', elgg_echo('assemblies'), 'assemblies/all');
	elgg_register_menu_item('site', $item);

	// Extend CSS
	elgg_extend_view('css/elgg', 'assemblies/css');

	// extend group main page
	elgg_extend_view('groups/tool_latest', 'assemblies/group_module');

	// Register a page handler, so we can have nice URLs
	elgg_register_page_handler('assemblies', 'assemblies_page_handler');

	// Add a new assemblies widget
	elgg_register_widget_type('assemblies', elgg_echo("assemblies"), elgg_echo("assemblies:widget:description"));

	// Register URL handlers for assemblies
	elgg_register_entity_url_handler('object', 'assembly', 'assemblies_url_override');
	elgg_register_plugin_hook_handler('entity:icon:url', 'object', 'assemblies_icon_url_override');

	// Register granular notification for this object type
	register_notification_object('object', 'assemblies', elgg_echo('assemblies:new'));

	// Listen to notification events and supply a more useful message
	elgg_register_plugin_hook_handler('notify:entity:message', 'object', 'assemblies_notify_message');

	// add the group assemblies tool option
	add_group_tool_option('assemblies', elgg_echo('groups:enableassemblies'), true);

	// Register entity type for search
	elgg_register_entity_type('object', 'assembly');

	// add a assemblies link to owner blocks
	elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'assemblies_owner_block_menu');

	// Register actions
	$action_path = elgg_get_plugins_path() . 'assemblies/actions/assemblies';
	elgg_register_action("assemblies/edit", "$action_path/edit.php");
	elgg_register_action("assemblies/delete", "$action_path/delete.php");

}

/**
 * Dispatches assemblies pages.
 * URLs take the form of
 *  All topics in site:    discussion/all
 *  List topics in forum:  discussion/owner/<guid>
 *  View discussion topic: discussion/view/<guid>
 *  Add discussion topic:  discussion/add/<guid>
 *  Edit discussion topic: discussion/edit/<guid>
 *
 * @param array $page
 * @return bool
 */
function assemblies_page_handler($page) {

	if (!isset($page[0])) {
		$page[0] = 'all';
	}

	$assemblies_dir = elgg_get_plugins_path() . 'assemblies/pages/assemblies';

	$page_type = $page[0];
	switch ($page_type) {
		case 'owner':
			include "$assemblies_dir/owner.php";
			break;
		case 'view':
			set_input('guid', $page[1]);
			include "$assemblies_dir/view.php";
			break;
		case 'add':
			include "$assemblies_dir/upload.php";
			break;
		case 'edit':
			set_input('guid', $page[1]);
			include "$assemblies_dir/edit.php";
			break;
		case 'all':
			include "$assemblies_dir/world.php";
			break;
		default:
			return false;
	}
	return true;
}


/**
 * Creates the notification message body
 *
 * @param string $hook
 * @param string $entity_type
 * @param string $returnvalue
 * @param array  $params
 */
function assemblies_notify_message($hook, $entity_type, $returnvalue, $params) {
	$entity = $params['entity'];
	$to_entity = $params['to_entity'];
	$method = $params['method'];
	if (($entity instanceof ElggEntity) && ($entity->getSubtype() == 'assembly')) {
		$descr = $entity->description;
		$title = $entity->title;
		$url = elgg_get_site_url() . "view/" . $entity->guid;
		$owner = $entity->getOwnerEntity();
		return $owner->name . ' ' . elgg_echo("assemblies:via") . ': ' . $entity->title . "\n\n" . $descr . "\n\n" . $entity->getURL();
	}
	return null;
}

/**
 * Add a menu item to the user ownerblock
 */
function assemblies_owner_block_menu($hook, $type, $return, $params) {
	if (elgg_instanceof($params['entity'], 'group') && ($params['entity']->assemblies_enable != "no")) {
		$url = "assemblies/group/{$params['entity']->guid}/all";
		$item = new ElggMenuItem('assemblies', elgg_echo('assemblies:group'), $url);
		$return[] = $item;
	}
	return $return;
}


/**
 * Populates the ->getUrl() method for assemblies objects
 *
 * @param ElggEntity $entity File entity
 * @return string File URL
 */
function assemblies_url_override($entity) {
	$title = $entity->title;
	$title = elgg_get_friendly_title($title);
	return "assemblies/view/" . $entity->getGUID() . "/" . $title;
}

/**
 * Override the default entity icon for assemblies
 *
 * Plugins can override or extend the icons using the plugin hook: 'assemblies:icon:url', 'override'
 *
 * @return string Relative URL
 */
function assemblies_icon_url_override($hook, $type, $returnvalue, $params) {
    $entity = $params['entity'];
    if (elgg_instanceof($entity, 'object', 'assembly')) {
        switch ($params['size']) {
            case 'small':
                return 'mod/assemblies/images/assemblies.gif';
                break;
            case 'medium':
                return 'mod/assemblies/images/assemblies_lrg.gif';
                break;
        }
    }
}
