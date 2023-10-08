<?php
/**
 * Online Module Management Platform
 * 
 * Main file for store module
 * Contains the required function to allow the module to work
 * 
 * @author  The OMMP Team
 * @version 1.0
 */

/**
 * Check a configuration value
 * 
 * @param string $name
 *      The configuration name (without the module name)
 * @param string $value
 *      The configuration value
 * @param Lang $lang
 *         The Lang object for the current module
 * 
 * @return boolean|string
 *      TRUE is the value is correct for the given name
 *      else a string explaination of the error
 */
function store_check_config($name, $value, $lang) {
    return TRUE;
}

/**
 * Handle user deletion calls
 * This function will be called by the plateform when a user is deleted,
 * it must delete all the data relative to the user
 * 
 * @param int $id
 *         The id of the user that will be deleted
 */
function store_delete_user($id) {
    // This module does not interracts with the user
}

/**
 * Handle an API call
 * 
 * @param string $action
 *      The name of the action to process
 * @param array $data
 *      The data given with the action
 * 
 * @return array|boolean
 *      An array containing the data to respond
 *      FALSE if the action does not exists
 */
function store_process_api($action, $data) {
    global $user, $sql, $db_prefix;

    if ($action == "get-module-versions") {

        // Check the parameters
		if (!check_keys($data, ["id"])) {
			return ["error" => $user->module_lang->get("missing_parameter")];
		}

        $versions = [];
        $request = $sql->query("SELECT version, required, timestamp FROM {$db_prefix}store_versions, {$db_prefix}store_modules WHERE name = " . $sql->quote($data['id']) . " AND id = store_id AND NOT hidden AND NOT blocked ORDER BY version DESC");
        while ($version = $request->fetch()) {
            $versions[] = [
                "version" => $version['version'],
                "required" => $version['required'],
                "timestamp" => $version['timestamp'],
                "formatted_timestamp" => date($user->module_lang->get("date_format"), $version['timestamp']),
            ];
        }
        $request->closeCursor();

        return [
            "ok" => TRUE,
            "versions" => $versions
        ];

    }

    return FALSE;
}

/**
 * Handle page loading for the module
 * 
 * @param string $page
 *      The page requested in the module
 * @param string $pages_path
 *      The absolute path where the pages are stored for this module
 * 
 * @return array|boolean
 *      An array containing multiple informations about the page as described below
 *      [
 *          "content" => The content of the page,
 *          "title" => The title of the page,
 *          "og_image" => The Open Graph image (optional),
 *          "description" => A description of the web page
 *      ]
 *      FALSE to generate a 404 error
 */
function store_process_page($page, $pages_path) {
    global $user, $db_prefix, $sql;
    $data_path = module_get_data_path("store");

    // Check if we must save a version and return page
    if (isset($_GET['version'])) {
        set_ommp_cookie("store.required_version", intval($_GET['version']), FALSE);
    }
    if (isset($_GET['version'])) {
        set_ommp_cookie("store.return", $_GET['return'], FALSE);
    }

    // Check if we must display index
    if ($page == "") {
        return [
            "content" => page_read_module($pages_path . "index.html", []),
            "title" => $user->module_lang->get("@module_name")
        ];
    }

    // Explode the path
    $pages = array_filter(explode("/", $page));
    $module_id = $pages[0];

    // Get informations about the module
    $module_infos = dbGetFirstLineSimple("{$db_prefix}store_modules", "not hidden and not blocked and name = " . $sql->quote($module_id));

    // Check if module exists
    if ($module_infos === FALSE) {
        return FALSE;
    }

    // Get latest version of module
    $latest_version = dbGetFirstLine("SELECT * FROM {$db_prefix}store_versions WHERE store_id = " . $sql->quote($module_infos['id']) . " ORDER BY version DESC");

    // Check if any version has been uploaded yet
    if ($latest_version === FALSE) {
        return FALSE;
    }

    // Check if we must display the module's page
    if (count($pages) == 1) {

        // Reads metadate
        $metadata = @json_decode(@file_get_contents("{$data_path}{$module_id}/meta.json"), TRUE);

        // Get language from the module
        // Try to load the current user's language, if not available in module then load the default module's language
        $lang_code = file_exists("{$data_path}{$module_id}/languages/" . $user->lang->current_language() . ".json") ? $user->lang->current_language() : $metadata->default_language;
        $module_lang = @json_decode(@file_get_contents("{$data_path}{$module_id}/languages/{$lang_code}.json"), TRUE);

        // Get informations about the author
        $author = new User(intval($module_infos['author']));

        // Get available languages
        $languages_list = [];
        foreach (scandir("{$data_path}{$module_id}/languages/") as $file) {
            if (str_ends_with($file, ".json")) {
                $lang = @json_decode(@file_get_contents("{$data_path}{$module_id}/languages/{$file}"), TRUE);
                if ($lang) {
                    $languages_list[] = $lang['@name'];
                }
            }
        }
        $languages = implode(", ", $languages_list);

        // Get screenshots
        $screenshots = 0;
        while (file_exists("{$data_path}{$module_id}/screenshot_{$screenshots}.jpg")) {
            $screenshots++;
        }

        return [
            "content" => page_read_module($pages_path . "module_page.html", [
                "module_name" => htmlvarescape($module_lang['@module_name']),
                "module_description" => htmlvarescape($module_lang['@module_description']),
                "module_id" => htmlvarescape($module_id),
                "module_version" => htmlvarescape($metadata['version']),
                "author_name" => htmlvarescape($author->longname == "" ? $author->username : $author->longname),
                "author_username" => htmlvarescape($author->username),
                "author_certified_image" => $author->certification_html(),
                "ommp_version" => $latest_version['required'],
                "module_languages" => $languages,
                "module_size" => human_file_size(@filesize("{$data_path}{$module_id}/{$module_id}-v{$metadata['version']}.zip"), TRUE, 0),
                "module_website" => htmlvarescape($metadata['website']),
                "module_website_hr" => htmlvarescape(substr($metadata['website'], stripos($metadata['website'], '://') + 3)),
                "module_contact" => htmlvarescape($metadata['contact']),
                "module_screenshots" => $screenshots
            ]),
            "title" => $user->module_lang->get("@module_name") . " - " . $module_lang['@module_name']
        ];
    }

    // Check if we must display an icon
    if (count($pages) == 2 && $pages[1] == "icon.png") {
        $icon_path = "{$data_path}{$module_id}/icon.png";
        // Check if we must resize the image
        $result = get_image_thumbnail($icon_path, 512);
        if (!$result) {
            // If not, display the full image
            if (file_exists($icon_path)) {
                header('Content-Type: image/png');
                readfile($icon_path);
                exit;
            }
            // Else, 404
            return FALSE;
        }
    }

    // Check if we must display a screenshot
    if (count($pages) == 2 && str_starts_with($pages[1], "screenshot_") && str_ends_with($pages[1], ".jpg") && ctype_digit(substr($pages[1], 11, -4))) {
        $ss_path = "{$data_path}{$module_id}/$pages[1]";
        // Check if we must resize the image
        $result = FALSE;
        if (isset($_GET['thumb']) && ctype_digit($_GET['thumb'])) {
            $result = get_image_thumbnail($ss_path, intval($_GET['thumb']));
        }
        if (!$result) {
            // If not, display the full image
            if (file_exists($ss_path)) {
                header('Content-Type: image/jpg');
                readfile($ss_path);
                exit;
            }
            // Else, 404
            return FALSE;
        }
    }

    return FALSE;

}

/**
 * Handle the special URL pages
 * 
 * @param string $url
 *      The url to check for a special page
 * 
 * @return boolean
 *      TRUE if this module can process this url (in this case this function will manage the whole page display)
 *      FALSE else (in this case, we will check the url with the remaining modules, order is defined by module's priority value)
 */
function store_url_handler($url) {
    // This module does not have special URL
    return FALSE;
}
