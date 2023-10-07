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

    // Check if we must display index
    if ($page == "") {
        return [
            "content" => page_read_module($pages_path . "index.html", []),
            "title" => $user->module_lang->get("@module_name")
        ];
    }

    // Explode the path
    $pages = array_filter(explode("/", $page));
    $module_name = $pages[0];

    // Get informations about the module
    $module_infos = dbGetFirstLineSimple("{$db_prefix}store_modules", "not hidden and not blocked and name = " . $sql->quote($module_name));

    // Check if module exists
    if ($module_infos === FALSE) {
        return FALSE;
    }

    // Get latest version of module
    $latest_version = dbGetFirstLine("SELECT * FROM {$db_prefix}store_versions WHERE store_id = " . $sql->quote($module_infos['id']) . " ORDER BY version DESC");

    // Check if any version has been updated yet
    if ($latest_version === FALSE) {
        return FALSE;
    }

    // Check if we must display the module's page
    if (count($pages) == 1) {

        // Reads metadate
        $metadata = @json_decode(@file_get_contents("{$data_path}{$module_name}/meta.json"));

        // Get language from the module
        // Try to load the current user's language, if not available in module then load the default module's language
        $lang_code = file_exists("{$data_path}{$module_name}/languages/" . $user->lang->current_language() . ".json") ? $user->lang->current_language() : $metadata->default_language;
        $module_lang = @json_decode(@file_get_contents("{$data_path}{$module_name}/languages/{$lang_code}.json"));

        return [
            "content" => print_r($latest_version, TRUE) . "<br />" . print_r($metadata, TRUE) . "<br />" . print_r($module_lang, TRUE),
            "title" => $user->module_lang->get("@module_name")
        ];
    }

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