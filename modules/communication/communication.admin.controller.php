<?php
    /**
     * @class  communicationAdminController
     * @author Arnia (dev@karybu.org)
     * @brief communication module of the admin controller class
     **/

    class communicationAdminController extends communication {

        /**
         * Initialization
         **/
        function init() {
        }

        /**
         * save configurations of the communication module
		 * @return void|Object (success : void, fail : Object)
         **/
        function procCommunicationAdminInsertConfig() {
            // get the default information
            $args = Context::gets('skin','colorset','editor_skin','editor_colorset', 'mskin', 'layout_srl', 'mlayout_srl');

            if(!$args->skin) $args->skin = 'default';
            if(!$args->colorset) $args->colorset = 'white';
            if(!$args->editor_skin) $args->editor_skin = 'default';
            if(!$args->mskin) $args->mskin = 'default';
            if(!$args->layout_srl) $args->layout_srl = null;

            // create the module module Controller object
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('communication',$args);

			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCommunicationAdminConfig');
			return $this->setRedirectUrl($returnUrl, $output);
        }

    }
?>
