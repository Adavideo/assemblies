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

	// add enclosure to rss item
	elgg_extend_view('extensions/item', 'assemblies/enclosure');

	// extend group main page
	elgg_extend_view('groups/tool_latest', 'assemblies/group_module');

	// Register a page handler, so we can have nice URLs
	elgg_register_page_handler('assemblies', 'assemblies_page_handler');

	// Add a new assemblies widget
	elgg_register_widget_type('assemblies', elgg_echo("assemblies"), elgg_echo("assemblies:widget:description"));

	// Register URL handlers for assemblies
	elgg_register_entity_url_handler('object', 'assemblies', 'assemblies_url_override');
	elgg_register_plugin_hook_handler('entity:icon:url', 'object', 'assemblies_icon_url_override');

	// Register granular notification for this object type
	register_notification_object('object', 'assemblies', elgg_echo('assemblies:newupload'));

	// Listen to notification events and supply a more useful message
	elgg_register_plugin_hook_handler('notify:entity:message', 'object', 'assemblies_notify_message');

	// add the group assemblies tool option
	add_group_tool_option('assemblies', elgg_echo('groups:enableassemblies'), true);

	// Register entity type for search
	elgg_register_entity_type('object', 'assemblies');

	// add a assemblies link to owner blocks
	elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'assemblies_owner_block_menu');

	// Register actions
	$action_path = elgg_get_plugins_path() . 'assemblies/actions/assemblies';
	elgg_register_action("assemblies/upload", "$action_path/upload.php");
	elgg_register_action("assemblies/delete", "$action_path/delete.php");
	// temporary - see #2010
	elgg_register_action("assemblies/download", "$action_path/download.php");

	// embed support
	$item = ElggMenuItem::factory(array(
		'name' => 'assemblies',
		'text' => elgg_echo('assemblies'),
		'priority' => 10,
		'data' => array(
			'options' => array(
				'type' => 'object',
				'subtype' => 'assemblies',
			),
		),
	));
	elgg_register_menu_item('embed', $item);

	$item = ElggMenuItem::factory(array(
		'name' => 'assemblies_upload',
		'text' => elgg_echo('assemblies:upload'),
		'priority' => 100,
		'data' => array(
			'view' => 'embed/assemblies_upload/content',
		),
	));

	elgg_register_menu_item('embed', $item);
}

/**
 * Dispatches assemblies pages.
 * URLs take the form of
 *  All assemblies:       assemblies/all
 *  User's assemblies:    assemblies/owner/<username>
 *  Friends' assemblies:  assemblies/friends/<username>
 *  View assemblies:       assemblies/view/<guid>/<title>
 *  New assemblies:        assemblies/add/<guid>
 *  Edit assemblies:       assemblies/edit/<guid>
 *  Group assemblies:     assemblies/group/<guid>/all
 *  Download:        assemblies/download/<guid>
 *
 * Title is ignored
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
			assemblies_register_toggle();
			include "$assemblies_dir/owner.php";
			break;
		case 'friends':
			assemblies_register_toggle();
			include "$assemblies_dir/friends.php";
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
		case 'search':
			assemblies_register_toggle();
			include "$assemblies_dir/search.php";
			break;
		case 'group':
			assemblies_register_toggle();
			include "$assemblies_dir/owner.php";
			break;
		case 'all':
			assemblies_register_toggle();
			include "$assemblies_dir/world.php";
			break;
		case 'download':
			set_input('guid', $page[1]);
			include "$assemblies_dir/download.php";
			break;
		default:
			return false;
	}
	return true;
}

/**
 * Adds a toggle to extra menu for switching between list and gallery views
 */
function assemblies_register_toggle() {
	$url = elgg_http_remove_url_query_element(current_page_url(), 'list_type');

	if (get_input('list_type', 'list') == 'list') {
		$list_type = "gallery";
		$icon = elgg_view_icon('grid');
	} else {
		$list_type = "list";
		$icon = elgg_view_icon('list');
	}

	if (substr_count($url, '?')) {
		$url .= "&list_type=" . $list_type;
	} else {
		$url .= "?list_type=" . $list_type;
	}


	elgg_register_menu_item('extras', array(
		'name' => 'assemblies_list',
		'text' => $icon,
		'href' => $url,
		'title' => elgg_echo("assemblies:list:$list_type"),
		'priority' => 1000,
	));
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
	if (($entity instanceof ElggEntity) && ($entity->getSubtype() == 'assemblies')) {
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
	if (elgg_instanceof($params['entity'], 'user')) {
		$url = "assemblies/owner/{$params['entity']->username}";
		$item = new ElggMenuItem('assemblies', elgg_echo('assemblies'), $url);
		$return[] = $item;
	} else {
		if ($params['entity']->assemblies_enable != "no") {
			$url = "assemblies/group/{$params['entity']->guid}/all";
			$item = new ElggMenuItem('assemblies', elgg_echo('assemblies:group'), $url);
			$return[] = $item;
		}
	}

	return $return;
}

/**
 * Returns an overall assemblies type from the mimetype
 *
 * @param string $mimetype The MIME type
 * @return string The overall type
 */
function assemblies_get_simple_type($mimetype) {

	switch ($mimetype) {
		case "application/msword":
			return "document";
			break;
		case "application/pdf":
			return "document";
			break;
	}

	if (substr_count($mimetype, 'text/')) {
		return "document";
	}

	if (substr_count($mimetype, 'audio/')) {
		return "audio";
	}

	if (substr_count($mimetype, 'image/')) {
		return "image";
	}

	if (substr_count($mimetype, 'video/')) {
		return "video";
	}

	if (substr_count($mimetype, 'opendocument')) {
		return "document";
	}

	return "general";
}

// deprecated and will be removed
function get_general_assemblies_type($mimetype) {
	elgg_deprecated_notice('Use assemblies_get_simple_type() instead of get_general_assemblies_type()', 1.8);
	return assemblies_get_simple_type($mimetype);
}

/**
 * Returns a list of assembliestypes
 *
 * @param int       $container_guid The GUID of the container of the assemblies
 * @param bool      $friends        Whether we're looking at the container or the container's friends
 * @return string The typecloud
 */
function assemblies_get_type_cloud($container_guid = "", $friends = false) {

	$container_guids = $container_guid;

	if ($friends) {
		// tags interface does not support pulling tags on friends' content so
		// we need to grab all friends
		$friend_entities = get_user_friends($container_guid, "", 999999, 0);
		if ($friend_entities) {
			$friend_guids = array();
			foreach ($friend_entities as $friend) {
				$friend_guids[] = $friend->getGUID();
			}
		}
		$container_guids = $friend_guids;
	}

	elgg_register_tag_metadata_name('simpletype');
	$options = array(
		'type' => 'object',
		'subtype' => 'assemblies',
		'container_guids' => $container_guids,
		'threshold' => 0,
		'limit' => 10,
		'tag_names' => array('simpletype')
	);
	$types = elgg_get_tags($options);

	$params = array(
		'friends' => $friends,
		'types' => $types,
	);

	return elgg_view('assemblies/typecloud', $params);
}

function get_assembliestype_cloud($owner_guid = "", $friends = false) {
	elgg_deprecated_notice('Use assemblies_get_type_cloud instead of get_assembliestype_cloud', 1.8);
	return assemblies_get_type_cloud($owner_guid, $friends);
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
	$assemblies = $params['entity'];
	$size = $params['size'];
	if (elgg_instanceof($assemblies, 'object', 'assemblies')) {

		// thumbnails get first priority
		if ($assemblies->thumbnail) {
			return "mod/assemblies/thumbnail.php?assemblies_guid=$assemblies->guid&size=$size";
		}

		$mapping = array(
			'application/excel' => 'excel',
			'application/msword' => 'word',
			'application/pdf' => 'pdf',
			'application/powerpoint' => 'ppt',
			'application/vnd.ms-excel' => 'excel',
			'application/vnd.ms-powerpoint' => 'ppt',
			'application/vnd.oasis.opendocument.text' => 'openoffice',
			'application/x-gzip' => 'archive',
			'application/x-rar-compressed' => 'archive',
			'application/x-stuffit' => 'archive',
			'application/zip' => 'archive',

			'text/directory' => 'vcard',
			'text/v-card' => 'vcard',

			'application' => 'application',
			'audio' => 'music',
			'text' => 'text',
			'video' => 'video',
		);

		$mime = $assemblies->mimetype;
		if ($mime) {
			$base_type = substr($mime, 0, strpos($mime, '/'));
		} else {
			$mime = 'none';
			$base_type = 'none';
		}

		if (isset($mapping[$mime])) {
			$type = $mapping[$mime];
		} elseif (isset($mapping[$base_type])) {
			$type = $mapping[$base_type];
		} else {
			$type = 'general';
		}

		if ($size == 'large') {
			$ext = '_lrg';
		} else {
			$ext = '';
		}
		
		$url = "mod/assemblies/graphics/icons/{$type}{$ext}.gif";
		$url = elgg_trigger_plugin_hook('assemblies:icon:url', 'override', $params, $url);
		return $url;
	}
}