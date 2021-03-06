<?php
/**
 * @class  editorModel
 * @author Arnia (dev@karybu.org)
 * @brief model class of the editor odule
 **/

class editorModel extends editor
{

    var $loaded_component_list = array();

    /**
     * @brief Return the editor
     *
     * Editor internally generates editor_sequence from 1 to 30 for temporary use.
     * That means there is a limitation that more than 30 editors cannot be displayed on a single page.
     *
     * However, editor_sequence can be value from getNextSequence() in case of the modified or the auto-saved for file upload
     *
     **/

    /**
     * @brief Return editor setting for each module
     **/
    function getEditorConfig($module_srl = null)
    {
        if (!isset($GLOBALS['__editor_module_config__'][$module_srl]) && $module_srl) {
            // Get trackback settings of the selected module
            $oModuleModel = & getModel('module');
            $GLOBALS['__editor_module_config__'][$module_srl] = $oModuleModel->getModulePartConfig(
                'editor',
                $module_srl
            );
        }
        $editor_config = isset($GLOBALS['__editor_module_config__'][$module_srl]) ? $GLOBALS['__editor_module_config__'][$module_srl] : null;

        $oModuleModel = & getModel('module');
        $editor_default_config = $oModuleModel->getModuleConfig('editor');

        if (!is_object($editor_config)) {
            $editor_config = new stdClass();
        }

        if (isset($editor_config->enable_html_grant)) {
            if (!is_array(
                $editor_config->enable_html_grant
            )
            ) {
                $editor_config->enable_html_grant = array();
            }
        }
        if (isset($editor_config->enable_comment_html_grant)) {
            if (!is_array(
                $editor_config->enable_comment_html_grant
            )
            ) {
                $editor_config->enable_comment_html_grant = array();
            }
        }
        if (isset($editor_config->upload_file_grant)) {
            if (!is_array(
                $editor_config->upload_file_grant
            )
            ) {
                $editor_config->upload_file_grant = array();
            }
        }
        if (isset($editor_config->comment_upload_file_grant)) {
            if (!is_array(
                $editor_config->comment_upload_file_grant
            )
            ) {
                $editor_config->comment_upload_file_grant = array();
            }
        }
        if (isset($editor_config->enable_default_component_grant)) {
            if (!is_array(
                $editor_config->enable_default_component_grant
            )
            ) {
                $editor_config->enable_default_component_grant = array();
            }
        }
        if (isset($editor_config->enable_comment_default_component_grant)) {
            if (!is_array(
                $editor_config->enable_comment_default_component_grant
            )
            ) {
                $editor_config->enable_comment_default_component_grant = array();
            }
        }
        if (isset($editor_config->enable_component_grant)) {
            if (!is_array(
                $editor_config->enable_component_grant
            )
            ) {
                $editor_config->enable_component_grant = array();
            }
        }
        if (isset($editor_config->enable_comment_component_grant)) {
            if (!is_array(
                $editor_config->enable_comment_component_grant
            )
            ) {
                $editor_config->enable_comment_component_grant = array();
            }
        }

        if (isset($editor_config->enable_autosave)) {
            if ($editor_config->enable_autosave != 'N') {
                $editor_config->enable_autosave = "Y";
            }
        }

        if (!isset($editor_config->editor_height)) {
            if (!empty($editor_default_config->editor_height) ? $editor_config->editor_height = $editor_default_config->editor_height : $editor_config->editor_height = 400) {
                ;
            }
        }
        if (!isset($editor_config->comment_editor_height)) {
            if (!empty($editor_default_config->comment_editor_height) ? $editor_config->comment_editor_height = $editor_default_config->comment_editor_height : $editor_config->comment_editor_height = 100) {
                ;
            }
        }
        if (!isset($editor_config->editor_skin)) {
            if (!empty($editor_default_config->editor_skin) ? $editor_config->editor_skin = $editor_default_config->editor_skin : $editor_config->editor_skin = 'ckeditor') {
                ;
            }
        }
        if (!isset($editor_config->comment_editor_skin)) {
            if (!empty($editor_default_config->comment_editor_skin) ? $editor_config->comment_editor_skin = $editor_default_config->comment_editor_skin : $editor_config->comment_editor_skin = 'ckeditor') {
                ;
            }
        }
        if (!isset($editor_config->content_style)) {
            if (!empty($editor_default_config->content_style) ? $editor_config->content_style = $editor_default_config->content_style : $editor_config->content_style = 'default') {
                ;
            }
        }

        if (!isset($editor_config->content_font) && isset($editor_default_config->content_font)) {
            $editor_config->content_font = $editor_default_config->content_font;
        }
        if (!isset($editor_config->content_font_size) && isset($editor_default_config->content_font_size)) {
            $editor_config->content_font_size = $editor_default_config->content_font_size;
        }

        if (!isset($editor_config->sel_editor_colorset) && isset($editor_default_config->sel_editor_colorset)) {
            $editor_config->sel_editor_colorset = $editor_default_config->sel_editor_colorset;
        }
        if (!isset($editor_config->sel_comment_editor_colorset) && isset($editor_default_config->sel_comment_editor_colorset)) {
            $editor_config->sel_comment_editor_colorset = $editor_default_config->sel_comment_editor_colorset;
        }
        if (!isset($editor_config->comment_content_style) && isset($editor_default_config->comment_content_style)) {
            $editor_config->comment_content_style = $editor_default_config->comment_content_style;
        }
        return $editor_config;
    }

    function loadDrComponents()
    {
        $drComponentPath = './modules/editor/skins/dreditor/drcomponents/';
        $drComponentList = FileHandler::readDir($drComponentPath);

        $oTemplate = & TemplateHandler::getInstance();

        $drComponentInfo = array();
        if ($drComponentList) {
            foreach ($drComponentList as $i => $drComponent) {
                unset($obj);
                $obj = $this->getDrComponentXmlInfo($drComponent);
                Context::loadLang(sprintf('%s%s/lang/', $drComponentPath, $drComponent));
                $path = sprintf('%s%s/tpl/', $drComponentPath, $drComponent);
                $obj->html = $oTemplate->compile($path, $drComponent);
                $drComponentInfo[$drComponent] = $obj;
            }
        }
        Context::set('drComponentList', $drComponentInfo);
    }

    function getDrComponentXmlInfo($drComponentName)
    {
        $lang_type = Context::getLangType();
        // Get the xml file path of requested component
        $component_path = sprintf('%s/skins/dreditor/drcomponents/%s/', $this->module_path, $drComponentName);

        $xml_file = sprintf('%sinfo.xml', $component_path);
        $cache_file = sprintf('./files/cache/editor/dr_%s.%s.php', $drComponentName, $lang_type);
        // Return information after including it after cached xml file exists
        if (file_exists($cache_file) && file_exists($xml_file) && filemtime($cache_file) > filemtime($xml_file)) {
            include($cache_file);
            return $xml_info;
        }
        // Return after parsing and caching if the cached file does not exist
        $oParser = new XmlParser();
        $xml_doc = $oParser->loadXmlFile($xml_file);

        $component_info->component_name = $drComponentName;
        $component_info->title = $xml_doc->component->title->body;
        $component_info->description = str_replace('\n', "\n", $xml_doc->component->description->body);
        $component_info->version = $xml_doc->component->version->body;
        $component_info->date = $xml_doc->component->date->body;
        $component_info->homepage = $xml_doc->component->link->body;
        $component_info->license = $xml_doc->component->license->body;
        $component_info->license_link = $xml_doc->component->license->attrs->link;

        $buff = '<?php if(!defined("__KARYBU__")) exit(); ';
        $buff .= sprintf('$xml_info->component_name = "%s";', $component_info->component_name);
        $buff .= sprintf('$xml_info->title = "%s";', $component_info->title);
        $buff .= sprintf('$xml_info->description = "%s";', $component_info->description);
        $buff .= sprintf('$xml_info->version = "%s";', $component_info->version);
        $buff .= sprintf('$xml_info->date = "%s";', $component_info->date);
        $buff .= sprintf('$xml_info->homepage = "%s";', $component_info->homepage);
        $buff .= sprintf('$xml_info->license = "%s";', $component_info->license);
        $buff .= sprintf('$xml_info->license_link = "%s";', $component_info->license_link);

        // Author information
        if (!is_array($xml_doc->component->author)) {
            $author_list[] = $xml_doc->component->author;
        }
        else {
            $author_list = $xml_doc->component->author;
        }

        for ($i = 0; $i < count($author_list); $i++) {
            $buff .= sprintf('$xml_info->author[' . $i . ']->name = "%s";', $author_list[$i]->name->body);
            $buff .= sprintf(
                '$xml_info->author[' . $i . ']->email_address = "%s";',
                $author_list[$i]->attrs->email_address
            );
            $buff .= sprintf('$xml_info->author[' . $i . ']->homepage = "%s";', $author_list[$i]->attrs->link);
        }

        // history
        if ($xml_doc->component->history) {
            if (!is_array($xml_doc->component->history)) {
                $history_list[] = $xml_doc->component->history;
            }
            else {
                $history_list = $xml_doc->component->history;
            }

            for ($i = 0; $i < count($history_list); $i++) {
                unset($obj);
                sscanf($history_list[$i]->attrs->date, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
                $date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                $buff .= sprintf(
                    '$xml_info->history[' . $i . ']->description = "%s";',
                    $history_list[$i]->description->body
                );
                $buff .= sprintf('$xml_info->history[' . $i . ']->version = "%s";', $history_list[$i]->attrs->version);
                $buff .= sprintf('$xml_info->history[' . $i . ']->date = "%s";', $date);

                if ($history_list[$i]->author) {
                    (!is_array(
                        $history_list[$i]->author
                    )) ? $obj->author_list[] = $history_list[$i]->author : $obj->author_list = $history_list[$i]->author;

                    for ($j = 0; $j < count($obj->author_list); $j++) {
                        $buff .= sprintf(
                            '$xml_info->history[' . $i . ']->author[' . $j . ']->name = "%s";',
                            $obj->author_list[$j]->name->body
                        );
                        $buff .= sprintf(
                            '$xml_info->history[' . $i . ']->author[' . $j . ']->email_address = "%s";',
                            $obj->author_list[$j]->attrs->email_address
                        );
                        $buff .= sprintf(
                            '$xml_info->history[' . $i . ']->author[' . $j . ']->homepage = "%s";',
                            $obj->author_list[$j]->attrs->link
                        );
                    }
                }

                if ($history_list[$i]->log) {
                    (!is_array(
                        $history_list[$i]->log
                    )) ? $obj->log_list[] = $history_list[$i]->log : $obj->log_list = $history_list[$i]->log;

                    for ($j = 0; $j < count($obj->log_list); $j++) {
                        $buff .= sprintf(
                            '$xml_info->history[' . $i . ']->logs[' . $j . ']->text = "%s";',
                            $obj->log_list[$j]->body
                        );
                        $buff .= sprintf(
                            '$xml_info->history[' . $i . ']->logs[' . $j . ']->link = "%s";',
                            $obj->log_list[$j]->attrs->link
                        );
                    }
                }
            }
        }
        // List extra variables (text type only in the editor component)
        $extra_vars = $xml_doc->component->extra_vars->var;
        if ($extra_vars) {
            if (!is_array($extra_vars)) {
                $extra_vars = array($extra_vars);
            }
            foreach ($extra_vars as $key => $val) {
                unset($obj);
                $key = $val->attrs->name;
                $title = $val->title->body;
                $description = $val->description->body;
                $xml_info->extra_vars->{$key}->title = $title;
                $xml_info->extra_vars->{$key}->description = $description;

                $buff .= sprintf('$xml_info->extra_vars->%s->%s = "%s";', $key, 'title', $title);
                $buff .= sprintf('$xml_info->extra_vars->%s->%s = "%s";', $key, 'description', $description);
            }
        }

        $buff .= ' ?>';

        FileHandler::writeFile($cache_file, $buff, "w");

        unset($xml_info);
        include($cache_file);
        return $xml_info;
    }

    /**
     * @brief Return the editor template
     * You can call upload_target_srl when modifying content
     * The upload_target_srl is used for a routine to check if an attachment exists
     **/
    function getEditor($upload_target_srl = 0, $option = null)
    {
        /**
         * Editor default option
         */
        $oModuleModel = & getModel('module');
        $editor_config = $editor_config = $oModuleModel->getModuleConfig('editor');
        if (!is_object($editor_config)) {
            $editor_config = new stdClass();
        }
        if (!is_object($option)) {
            $option = new stdClass();
        }
        if (empty($editor_config->editor_height)) {
            $editor_config->editor_height = 400;
        }
        if (empty($editor_config->comment_editor_height)) {
            $editor_config->comment_editor_height = 100;
        }
        if (empty($editor_config->editor_skin)) {
            $editor_config->editor_skin = 'ckeditor';
        }
        if (empty($editor_config->comment_editor_skin)) {
            $editor_config->comment_editor_skin = 'ckeditor';
        }
        if (empty($editor_config->sel_editor_colorset)) {
            $editor_config->sel_editor_colorset = 'default';
        }
        if (empty($editor_config->sel_comment_editor_colorset)) {
            $editor_config->sel_comment_editor_colorset = 'default';
        }


        if ($upload_target_srl) {
            $option->editor_sequence = $upload_target_srl;
        }
        if (empty($option->skin)) {
            $option->skin = $editor_config->editor_skin;
        }
        if (empty($option->colorset)) {
            $option->colorset = $editor_config->sel_editor_colorset;
        }
        if (empty($option->height)) {
            if(empty($option->editor_height)){
                $option->height = $editor_config->editor_height;
            }else{
                $option->height = $option->editor_height;
            }
        }
        if (empty($option->comment_skin)) {
            $option->comment_skin = $editor_config->comment_editor_skin;
        }
        if (empty($option->comment_colorset)) {
            $option->comment_colorset = $editor_config->sel_comment_editor_colorset;
        }
        if (empty($option->comment_height)) {
            $option->comment_height = $editor_config->comment_editor_height;
        }
        if (empty($option->content_style)) {
            $option->content_style = !empty($editor_config->content_style) ? $editor_config->content_style : null;
        }

        if (empty($option->allow_fileupload)) {
            $allow_fileupload = false;
        } else {
            $allow_fileupload = true;
        }

        Context::set('content_style', !empty($option->content_style) ? $option->content_style : null);
        Context::set('content_font', !empty($option->content_font) ? $option->content_font : null);
        Context::set('content_font_size', !empty($option->content_font_size) ? $option->content_font_size : null);

        // Option setting to allow auto-save
        if (empty($option->enable_autosave)) {
            $enable_autosave = false;
        } elseif (Context::get(isset($option->primary_key_name) ? $option->primary_key_name : null)) {
            $enable_autosave = false;
        } else {
            $enable_autosave = true;
        }
        // Option setting to allow the default editor component
        if (empty($option->enable_default_component)) {
            $enable_default_component = false;
        } else {
            $enable_default_component = true;
        }
        // Option setting to allow other extended components
        if (empty($option->enable_component)) {
            $enable_component = false;
        } else {
            $enable_component = true;
        }
        // Setting for html-mode
        if (!empty($option->disable_html)) {
            $html_mode = false;
        } else {
            $html_mode = true;
        }
        // Set Height
        $editor_height = !empty($option->height) ? $option->height : null;
        // Skin Setting
        $skin = !empty ($option->skin) ? $option->skin : null;
        Context::set('colorset', $option->colorset);
        Context::set('skin', $option->skin);

        if ($skin == 'dreditor') {
            $this->loadDrComponents();
        }

        /**
         * Check the automatic backup feature (do not use if the post is edited)
         **/
        if ($enable_autosave) {
            // Extract auto-saved data
            $saved_doc = $this->getSavedDoc($upload_target_srl);
            // Context setting auto-saved data
            Context::set('saved_doc', $saved_doc);
        }
        Context::set('enable_autosave', $enable_autosave);
        $option->use_simple_options = $option->use_simple_options == 'Y' ? true : false;
        Context::set('use_simple_options',$option->use_simple_options);

        /**
         * Extract editor's unique number (in order to display multiple editors on a single page)
         **/
        if (!empty($option->editor_sequence)) {
            $editor_sequence = $option->editor_sequence;
        }
        else {
            if (empty($_SESSION['_editor_sequence_'])) {
                $_SESSION['_editor_sequence_'] = 1;
            }
            $editor_sequence = $_SESSION['_editor_sequence_']++;
        }

        /**
         * Upload setting by using configuration of the file module internally
         **/
        $files_count = 0;
        if ($allow_fileupload) {
            $oFileModel = & getModel('file');
            // Get upload configuration to set on SWFUploader
            $file_config = $oFileModel->getUploadConfig();
            $file_config->allowed_attach_size = $file_config->allowed_attach_size * 1024 * 1024;
            $file_config->allowed_filesize = $file_config->allowed_filesize * 1024 * 1024;

            Context::set('file_config', $file_config);
            // Configure upload status such as file size
            $upload_status = $oFileModel->getUploadStatus();
            Context::set('upload_status', $upload_status);
            // Upload enabled (internally caching)
            $oFileController = & getController('file');
            $oFileController->setUploadInfo($editor_sequence, $upload_target_srl);
            // Check if the file already exists
            if ($upload_target_srl) {
                $files_count = $oFileModel->getFilesCount($upload_target_srl);
            }
        }
        Context::set('files_count', (int)$files_count);

        Context::set('allow_fileupload', $allow_fileupload);
        // Set editor_sequence value
        Context::set('editor_sequence', $editor_sequence);
        // Set the document number to upload_target_srl for file attachments
        // If a new document, upload_target_srl = 0. The value becomes changed when file attachment is requested
        Context::set('upload_target_srl', $upload_target_srl);
        // Set the primary key valueof the document or comments
        Context::set('editor_primary_key_name', $option->primary_key_name);
        // Set content column name to sync contents
        Context::set('editor_content_key_name', $option->content_key_name);


        /**
         * Check editor component
         **/
        $site_module_info = Context::get('site_module_info');
        $site_srl = (int)$site_module_info->site_srl;
        if ($enable_component) {
            if (!Context::get('component_list')) {
                $component_list = $this->getComponentList(true, $site_srl);
                Context::set('component_list', $component_list);
            }
        }
        Context::set('enable_component', $enable_component);
        Context::set('enable_default_component', $enable_default_component);

        /**
         * Variable setting if html_mode is available
         **/
        Context::set('html_mode', $html_mode);

        /**
         * Set a height of editor
         **/
        Context::set('editor_height', $editor_height);
        // Check an option whether to start the editor manually
        Context::set('editor_manual_start', !empty($option->manual_start) ? $option->manual_start : null);

        /**
         * Set a skin path to pre-compile the template
        ?**/
        $tpl_path = sprintf('%sskins/%s/', $this->module_path, $skin);
        $tpl_file = 'editor.html';

        if (!file_exists($tpl_path . $tpl_file)) {
            $skin = 'ckeditor';
            $tpl_path = sprintf('%sskins/%s/', $this->module_path, $skin);
        }
        Context::set('editor_path', $tpl_path);

        // load editor skin lang
        Context::loadLang($tpl_path . 'lang');
        // Return the compiled result from tpl file
        $oTemplate = new TemplateHandler();
        return $oTemplate->compile($tpl_path, $tpl_file);
    }

    /**
     * @brief Return editor template which contains settings of each module
     * Result of getModuleEditor() is as same as getEditor(). But getModuleEditor()uses additional settings of each module to generate an editor
     *
     * 2 types of editors supported; document and comment.
     * 2 types of editors can be used on a single module. For instance each for original post and reply port.
     **/
    function getModuleEditor($type = 'document', $module_srl, $upload_target_srl, $primary_key_name, $content_key_name)
    {
        // Get editor settings of the module
        $editor_config = $this->getEditorConfig($module_srl);
        // Configurations listed according to a type
        $config = new stdClass();
        if ($type == 'document') {
            $config->editor_skin = isset($editor_config->editor_skin) ? $editor_config->editor_skin : null;
            $config->content_style = isset($editor_config->content_style) ? $editor_config->content_style : null;
            $config->content_font = isset($editor_config->content_font) ? $editor_config->content_font : null;
            $config->content_font_size = isset($editor_config->content_font_size) ? $editor_config->content_font_size : null;
            $config->sel_editor_colorset = isset($editor_config->sel_editor_colorset) ? $editor_config->sel_editor_colorset : null;
            $config->upload_file_grant = isset($editor_config->upload_file_grant) ? $editor_config->upload_file_grant : null;
            $config->enable_default_component_grant = isset($editor_config->enable_default_component_grant) ? $editor_config->enable_default_component_grant : null;
            $config->enable_component_grant = isset($editor_config->enable_component_grant) ? $editor_config->enable_component_grant : null;
            $config->enable_html_grant = isset($editor_config->enable_html_grant) ? $editor_config->enable_html_grant : null;
            $config->editor_height = isset($editor_config->editor_height) ? $editor_config->editor_height : null;
            $config->enable_autosave = isset($editor_config->enable_autosave) ? $editor_config->enable_autosave : null;
            $config->use_simple_options = isset($editor_config->use_simple_options) ? $editor_config->use_simple_options : null;
        } else {
            $config->editor_skin = isset($editor_config->comment_editor_skin) ? $editor_config->comment_editor_skin : null;
            $config->content_style = isset($editor_config->comment_content_style) ? $editor_config->comment_content_style : null;
            $config->content_font = isset($editor_config->content_font) ? $editor_config->content_font : null;
            $config->content_font_size = isset($editor_config->content_font_size) ? $editor_config->content_font_size : null;
            $config->sel_editor_colorset = isset($editor_config->sel_comment_editor_colorset) ? $editor_config->sel_comment_editor_colorset : null;
            $config->upload_file_grant = isset($editor_config->comment_upload_file_grant) ? $editor_config->comment_upload_file_grant : null;
            $config->enable_default_component_grant = isset($editor_config->enable_comment_default_component_grant) ? $editor_config->enable_comment_default_component_grant : null;
            $config->enable_component_grant = isset($editor_config->enable_comment_component_grant) ? $editor_config->enable_comment_component_grant : null;
            $config->enable_html_grant = isset($editor_config->enable_comment_html_grant) ? $editor_config->enable_comment_html_grant : null;
            $config->editor_height = isset($editor_config->comment_editor_height) ? $editor_config->comment_editor_height : null;
            $config->use_simple_options = isset($editor_config->comment_use_simple_options) ? $editor_config->comment_use_simple_options : null;
            $config->enable_autosave = 'N';
        }
        // Check a group_list of the currently logged-in user for permission check
        if (Context::get('is_logged')) {
            $logged_info = Context::get('logged_info');
            $group_list = $logged_info->group_list;
        } else {
            $group_list = array();
        }
        // Pre-set option variables of editor
        $option = new stdClass();
        $option->skin = isset($config->editor_skin) ? $config->editor_skin : null;
        $option->content_style = isset($config->content_style) ? $config->content_style : null;
        $option->content_font = isset($config->content_font) ? $config->content_font : null;
        $option->content_font_size = isset($config->content_font_size) ? $config->content_font_size : null;
        $option->colorset = isset($config->sel_editor_colorset) ? $config->sel_editor_colorset : null;
        // Permission check for file upload
        $option->allow_fileupload = false;
        if (count($config->upload_file_grant)) {
            foreach ($group_list as $group_srl => $group_info) {
                if (in_array($group_srl, $config->upload_file_grant)) {
                    $option->allow_fileupload = true;
                    break;
                }
            }
        }
        // Permission check for using default components
        $option->enable_default_component = false;
        if (count($config->enable_default_component_grant)) {
            foreach ($group_list as $group_srl => $group_info) {
                if (in_array($group_srl, $config->enable_default_component_grant)) {
                    $option->enable_default_component = true;
                    break;
                }
            }
        } else {
            $option->enable_default_component = true;
        }
        // Permisshion check for using extended components
        $option->enable_component = false;
        if (count($config->enable_component_grant)) {
            foreach ($group_list as $group_srl => $group_info) {
                if (in_array($group_srl, $config->enable_component_grant)) {
                    $option->enable_component = true;
                    break;
                }
            }
        } else {
            $option->enable_component = true;
        }
        // HTML editing privileges
        $enable_html = false;
        if (count($config->enable_html_grant)) {
            foreach ($group_list as $group_srl => $group_info) {
                if (in_array($group_srl, $config->enable_html_grant)) {
                    $enable_html = true;
                    break;
                }
            }
        } else {
            $enable_html = true;
        }

        if ($enable_html) {
            $option->disable_html = false;
        } else {
            $option->disable_html = true;
        }
        // Set Height
        $option->height = $config->editor_height;
        // Set an option for Auto-save
        $option->enable_autosave = $config->enable_autosave == 'Y' ? true : false;
        $option->use_simple_options = $config->use_simple_options == 'Y' ? true : false;
        // Other settings
        $option->primary_key_name = $primary_key_name;
        $option->content_key_name = $content_key_name;

        return $this->getEditor($upload_target_srl, $option);
    }

    /**
     * @brief Get information which has been auto-saved
     **/
    function getSavedDoc($upload_target_srl)
    {
        // Find a document by using member_srl for logged-in user and ipaddress for non-logged user
        if (Context::get('is_logged')) {
            $logged_info = Context::get('logged_info');
            $auto_save_args->member_srl = $logged_info->member_srl;
        } else {
            $auto_save_args->ipaddress = $_SERVER['REMOTE_ADDR'];
        }
        $auto_save_args->module_srl = Context::get('module_srl');
        // Get the current module if module_srl doesn't exist
        if (!$auto_save_args->module_srl) {
            $current_module_info = Context::get('current_module_info');
            $auto_save_args->module_srl = $current_module_info->module_srl;
        }
        // Extract auto-saved data from the DB
        $output = executeQuery('editor.getSavedDocument', $auto_save_args);
        $saved_doc = $output->data;
        // Return null if no result is auto-saved
        if (!$saved_doc) {
            return;
        }
        // Check if the auto-saved document already exists
        $oDocumentModel = & getModel('document');
        $oSaved = $oDocumentModel->getDocument($saved_doc->document_srl);
        if ($oSaved->isExists()) {
            return;
        }
        // Move all the files if the auto-saved data contains document_srl and file
        // Then set document_srl to editor_sequence
        if ($saved_doc->document_srl && $upload_target_srl && !Context::get('document_srl')) {
            $saved_doc->module_srl = $auto_save_args->module_srl;
            $oFileController = & getController('file');
            $oFileController->moveFile($saved_doc->document_srl, $saved_doc->module_srl, $upload_target_srl);
        } else {
            if ($upload_target_srl) {
                $saved_doc->document_srl = $upload_target_srl;
            }
        }
        // Change auto-saved data
        $oEditorController = & getController('editor');
        $oEditorController->deleteSavedDoc(false);
        $oEditorController->doSaveDoc($saved_doc);

        return $saved_doc;
    }

    /**
     * @brief create objects of the component
     **/
    function getComponentObject($component, $editor_sequence = 0, $site_srl = 0)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $component) || !preg_match(
                '/^[0-9]+$/',
                $editor_sequence . $site_srl
            )
        ) {
            return;
        }

        if (!isset($this->loaded_component_list[$component][$editor_sequence])) {
            // Create an object of the component and execute
            $class_path = sprintf('%scomponents/%s/', $this->module_path, $component);
            $class_file = sprintf('%s%s.class.php', $class_path, $component);
            if (!file_exists($class_file)) {
                return new Object(-1, sprintf(
                    Context::getLang('msg_component_is_not_founded'),
                    $component
                ));
            }
            // Create an object after loading the class file
            require_once($class_file);
            $tmp_fn = create_function('$seq,$path', "return new {$component}(\$seq,\$path);");
            $oComponent = $tmp_fn($editor_sequence, $class_path);
            if (!$oComponent) {
                return new Object(-1, sprintf(
                    Context::getLang('msg_component_is_not_founded'),
                    $component
                ));
            }
            // Add configuration information
            $component_info = $this->getComponent($component, $site_srl);
            $oComponent->setInfo($component_info);
            $this->loaded_component_list[$component][$editor_sequence] = $oComponent;
        }

        return $this->loaded_component_list[$component][$editor_sequence];
    }

    /**
     * @brief Return a list of the editor skin
     **/
    function getEditorSkinList()
    {
        return FileHandler::readDir('./modules/editor/skins');
    }

    /**
     * @brief Return the cache file name of editor component list
     **/
    function getCacheFile($filter_enabled = true, $site_srl = 0)
    {
        $lang = Context::getLangType();
        $cache_path = _KARYBU_PATH_ . 'files/cache/editor/cache/';
        if (!is_dir($cache_path)) {
            FileHandler::makeDir($cache_path);
        }
        $cache_file = $cache_path . 'component_list.' . $lang . '.';
        if ($filter_enabled) {
            $cache_file .= 'filter.';
        }
        if ($site_srl) {
            $cache_file .= $site_srl . '.';
        }
        $cache_file .= 'php';
        return $cache_file;
    }

    /**
     * @brief Return a component list (DB Information included)
     **/
    function getComponentList($filter_enabled = true, $site_srl = 0, $from_db = false)
    {
        $cache_file = $this->getCacheFile(false, $site_srl);
        if ($from_db || !file_exists($cache_file)) {
            $oEditorController = & getController('editor');
            $oEditorController->makeCache(false, $site_srl);
        }

        if (!file_exists($cache_file)) {
            return;
        }
        @include($cache_file);
        $logged_info = Context::get('logged_info');
        if ($logged_info && is_array($logged_info->group_list)) {
            $group_list = array_keys($logged_info->group_list);
        } else {
            $group_list = array();
        }

        if (count($component_list)) {
            foreach ($component_list as $key => $val) {
                if (!trim($key)) {
                    continue;
                }
                if (!is_dir(_KARYBU_PATH_ . 'modules/editor/components/' . $key)) {
                    FileHandler::removeFile($cache_file);
                    return $this->getComponentList($filter_enabled, $site_srl);
                }
                if (!$filter_enabled) {
                    continue;
                }
                if ($val->enabled == "N") {
                    unset($component_list->{$key});
                    continue;
                }
                if ($logged_info->is_admin == "Y" || $logged_info->is_site_admin == "Y") {
                    continue;
                }
                if ($val->target_group) {
                    if (!$logged_info) {
                        $val->enabled = "N";
                    } else {
                        $is_granted = false;
                        foreach ($group_list as $group_srl) {
                            if (in_array($group_srl, $val->target_group)) {
                                $is_granted = true;
                            }
                        }
                        if (!$is_granted) {
                            $val->enabled = "N";
                        }
                    }
                }
                if ($val->enabled != "N" && $val->mid_list) {
                    $mid = Context::get('mid');
                    if (!in_array($mid, $val->mid_list)) {
                        $val->enabled = "N";
                    }
                }
                if ($val->enabled == "N") {
                    unset($component_list->{$key});
                    continue;
                }
            }

        }
        return $component_list;
    }

    /**
     * @brief Get xml and db information of the component
     **/
    function getComponent($component_name, $site_srl = 0)
    {
        $args = new stdClass();
        $args->component_name = $component_name;

        if ($site_srl) {
            $args->site_srl = $site_srl;
            $output = executeQuery('editor.getSiteComponent', $args);
        } else {
            $output = executeQuery('editor.getComponent', $args);
        }
        $component = isset($output->data) ? $output->data : null;

        $component_name = isset($component->component_name) ? $component->component_name : null;

        $xml_info = new stdClass();
        $xml_info = $this->getComponentXmlInfo($component_name);
        $xml_info->enabled = isset($component->enabled) ? $component->enabled : null;

        $xml_info->target_group = array();

        $xml_info->mid_list = array();

        if (!empty($component->extra_vars)) {
            $extra_vars = @unserialize($component->extra_vars);

            if ($extra_vars->target_group) {
                $xml_info->target_group = $extra_vars->target_group;
                unset($extra_vars->target_group);
            }

            if ($extra_vars->mid_list) {
                $xml_info->mid_list = $extra_vars->mid_list;
                unset($extra_vars->mid_list);
            }


            if ($xml_info->extra_vars) {
                foreach ($xml_info->extra_vars as $key => $val) {
                    $xml_info->extra_vars->{$key}->value = $extra_vars->{$key};
                }
            }
        }

        return $xml_info;
    }

    /**
     * @brief Read xml information of the component
     **/
    function getComponentXmlInfo($component)
    {
        $lang_type = Context::getLangType();
        // Get xml file path of the requested components
        $component_path = sprintf('%s/components/%s/', $this->module_path, $component);

        $xml_file = sprintf('%sinfo.xml', $component_path);
        $cache_file = sprintf('./files/cache/editor/%s.%s.php', $component, $lang_type);
        // Include and return xml file information if cached file exists
        if (file_exists($cache_file) && file_exists($xml_file) && filemtime($cache_file) > filemtime($xml_file)) {
            include($cache_file);
            return $xml_info;
        }
        // Parse, cache and then return if the cached file doesn't exist
        $oParser = new XmlParser();
        $xml_doc = $oParser->loadXmlFile($xml_file);
        // Component information listed
        if (!empty($xml_doc->component->version) && $xml_doc->component->attrs->version == '0.2') {
            $component_info = new stdClass();
            $component_info->component_name = $component;
            $component_info->title = !empty($xml_doc->component->title->body) ? $xml_doc->component->title->body : null;
            $component_info->description = !empty($xml_doc->component->description->body) ? str_replace('\n', "\n", $xml_doc->component->description->body) : null;
            $component_info->version = !empty($xml_doc->component->version->body) ? $xml_doc->component->version->body : null;
            $component_info->date = !empty($xml_doc->component->date->body) ? $xml_doc->component->date->body : null;
            $component_info->homepage = !empty($xml_doc->component->link->body) ? $xml_doc->component->link->body : null;
            $component_info->license = !empty($xml_doc->component->license->body) ? $xml_doc->component->license->body : null;
            $component_info->license_link = !empty($xml_doc->component->license->attrs->link) ? $xml_doc->component->license->attrs->link : null;

            $buff = '<?php if(!defined("__KARYBU__")) exit(); ';
            $buff .= sprintf('$xml_info = new stdClass;');
            $buff .= sprintf('$xml_info->component_name = "%s";', $component_info->component_name);
            $buff .= sprintf('$xml_info->title = "%s";', $component_info->title);
            $buff .= sprintf('$xml_info->description = "%s";', $component_info->description);
            $buff .= sprintf('$xml_info->version = "%s";', $component_info->version);
            $buff .= sprintf('$xml_info->date = "%s";', $component_info->date);
            $buff .= sprintf('$xml_info->homepage = "%s";', $component_info->homepage);
            $buff .= sprintf('$xml_info->license = "%s";', $component_info->license);
            $buff .= sprintf('$xml_info->license_link = "%s";', $component_info->license_link);
            // Author information
            if (!is_array($xml_doc->component->author)) {
                $author_list[] = $xml_doc->component->author;
            }
            else {
                $author_list = $xml_doc->component->author;
            }
            if (count($author_list) > 0) {
                $buff .= sprintf('$xml_info->author = array();');
            }
            for ($i = 0; $i < count($author_list); $i++) {
                $buff .= sprintf('$xml_info->author[' . $i . '] = new stdClass;');
                $buff .= sprintf('$xml_info->author[' . $i . ']->name = "%s";', $author_list[$i]->name->body);
                $buff .= sprintf(
                    '$xml_info->author[' . $i . ']->email_address = "%s";',
                    $author_list[$i]->attrs->email_address
                );
                $buff .= sprintf('$xml_info->author[' . $i . ']->homepage = "%s";', $author_list[$i]->attrs->link);
            }

            // history
            if ($xml_doc->component->history) {
                if (!is_array($xml_doc->component->history)) {
                    $history_list[] = $xml_doc->component->history;
                }
                else {
                    $history_list = $xml_doc->component->history;
                }

                for ($i = 0; $i < count($history_list); $i++) {
                    unset($obj);
                    sscanf($history_list[$i]->attrs->date, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
                    $date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                    $buff .= sprintf(
                        '$xml_info->history[' . $i . ']->description = "%s";',
                        $history_list[$i]->description->body
                    );
                    $buff .= sprintf(
                        '$xml_info->history[' . $i . ']->version = "%s";',
                        $history_list[$i]->attrs->version
                    );
                    $buff .= sprintf('$xml_info->history[' . $i . ']->date = "%s";', $date);

                    if ($history_list[$i]->author) {
                        (!is_array(
                            $history_list[$i]->author
                        )) ? $obj->author_list[] = $history_list[$i]->author : $obj->author_list = $history_list[$i]->author;

                        for ($j = 0; $j < count($obj->author_list); $j++) {
                            $buff .= sprintf(
                                '$xml_info->history[' . $i . ']->author[' . $j . ']->name = "%s";',
                                $obj->author_list[$j]->name->body
                            );
                            $buff .= sprintf(
                                '$xml_info->history[' . $i . ']->author[' . $j . ']->email_address = "%s";',
                                $obj->author_list[$j]->attrs->email_address
                            );
                            $buff .= sprintf(
                                '$xml_info->history[' . $i . ']->author[' . $j . ']->homepage = "%s";',
                                $obj->author_list[$j]->attrs->link
                            );
                        }
                    }

                    if ($history_list[$i]->log) {
                        (!is_array(
                            $history_list[$i]->log
                        )) ? $obj->log_list[] = $history_list[$i]->log : $obj->log_list = $history_list[$i]->log;

                        for ($j = 0; $j < count($obj->log_list); $j++) {
                            $buff .= sprintf(
                                '$xml_info->history[' . $i . ']->logs[' . $j . ']->text = "%s";',
                                $obj->log_list[$j]->body
                            );
                            $buff .= sprintf(
                                '$xml_info->history[' . $i . ']->logs[' . $j . ']->link = "%s";',
                                $obj->log_list[$j]->attrs->link
                            );
                        }
                    }
                }
            }


        } else {
            if (!empty($xml_doc->component->author->attrs->date)) {
                list ($y, $m, $d) = sscanf($xml_doc->component->author->attrs->date, '%d. %d. %d');
                $date = sprintf('%04d%02d%02d', $y, $m, $d);
            }
            else {
                $date = null;
            }
            $xml_info = new stdClass();
            $xml_info->component_name = $component;
            $xml_info->title = isset($xml_doc->component->title->body) ? $xml_doc->component->title->body : null;
            $xml_info->description = isset($xml_doc->component->author->description->body) ? str_replace('\n', "\n", $xml_doc->component->author->description->body) : null;
            $xml_info->version = isset($xml_doc->component->attrs->version) ? $xml_doc->component->attrs->version : null;
            $xml_info->date = $date;
            $xml_info->author = new stdClass();
            $xml_info->author->name = isset($xml_doc->component->author->name->body) ? $xml_doc->component->author->name->body : null;
            $xml_info->author->email_address = isset($xml_doc->component->author->attrs->email_address) ? $xml_doc->component->author->attrs->email_address : null;
            $xml_info->author->homepage = isset($xml_doc->component->author->attrs->link) ? $xml_doc->component->author->attrs->link : null;

            $buff = '<?php if(!defined("__KARYBU__")) exit(); ';
            $buff .= sprintf('$xml_info = new stdClass;');
            $buff .= sprintf('$xml_info->component_name = "%s";', isset($xml_info->component_name) ? $xml_info->component_name : '');
            $buff .= sprintf('$xml_info->title = "%s";', isset($xml_info->title) ? $xml_info->title : '');
            $buff .= sprintf('$xml_info->description = "%s";', isset($xml_info->description) ? $xml_info->description : '');
            $buff .= sprintf('$xml_info->version = "%s";', isset($xml_info->version) ? $xml_info->version : '');
            $buff .= sprintf('$xml_info->date = "%s";', isset($xml_info->date) ? $xml_info->date : '');
            $buff .= sprintf('$xml_info->author = array();');
            $buff .= sprintf('$xml_info->author[0] = new stdClass;');
            $buff .= sprintf('$xml_info->author[0]->name = "%s";', isset($xml_info->author->name) ? $xml_info->author->name : '');
            $buff .= sprintf('$xml_info->author[0]->email_address = "%s";', isset($xml_info->author->email_address) ? $xml_info->author->email_address : '');
            $buff .= sprintf('$xml_info->author[0]->homepage = "%s";', isset($xml_info->author->homepage) ? $xml_info->author->homepage : '');
        }
        // List extra variables (text type only for editor component)
        if (isset($xml_doc->component->extra_vars->var)) {
            $extra_vars = $xml_doc->component->extra_vars->var;
        }
        else {
            $extra_vars = null;
        }
        if ($extra_vars) {
            if (!is_array($extra_vars)) {
                $extra_vars = array($extra_vars);
            }
            $xml_info->extra_vars = new stdClass();
            foreach ($extra_vars as $key => $val) {
                unset($obj);
                $key = isset($val->attrs->name) ? $val->attrs->name : null;
                if ($key) {
                    $title = isset($val->title->body) ? $val->title->body : null;
                    $description = isset($val->description->body) ? $val->description->body : null;
                    $xml_info->extra_vars->{$key} = new stdClass();
                    $xml_info->extra_vars->{$key}->title = $title;
                    $xml_info->extra_vars->{$key}->description = $description;

                    $buff .= sprintf('$xml_info->extra_vars->%s->%s = "%s";', $key, 'title', $title);
                    $buff .= sprintf('$xml_info->extra_vars->%s->%s = "%s";', $key, 'description', $description);
                }
            }
        }

        $buff .= ' ?>';

        FileHandler::writeFile($cache_file, $buff, "w");

        unset($xml_info);
        include($cache_file);
        return $xml_info;
    }
}
?>
