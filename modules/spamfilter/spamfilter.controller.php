<?php

    require_once(_KARYBU_PATH_ . 'libs/Akismet.class.php');

    /**
     * @class  spamfilterController
     * @author Arnia (dev@karybu.org)
     * @brief The controller class for the spamfilter module
     **/

    class spamfilterController extends spamfilter {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Call this function in case you need to stop the spam filter's usage during the batch work
         **/
        function setAvoidLog() {
            $_SESSION['avoid_log'] = true;
        }

        /**
         * @brief The routine process to check the time it takes to store a document, when writing it, and to ban IP/word
         **/
        function triggerInsertDocument(&$obj) {
            if(!empty($_SESSION['avoid_log'])) {
                return new Object();
            }
            // Check the login status, login information, and permission
            $is_logged = Context::get('is_logged');
            $logged_info = Context::get('logged_info');
            $grant = Context::get('grant');
            // In case logged in, check if it is an administrator
            if ($is_logged) {
                if($logged_info->is_admin == 'Y') return new Object();
                if($grant->manager) return new Object();
            }

            $oFilterModel = getModel('spamfilter');
            // Check if the IP is prohibited
            $output = $oFilterModel->isDeniedIP();
            if(!$output->toBool()) return $output;
            // Check words blacklist
            $config = $this->getSpamConfig();
            if (in_array('articles', (array) $config->usage)) {
                //replace words
                if ($config->action_bad_words == 'replace') {
                    $obj->title = $oFilterModel->censorText($obj->title);
                    $obj->content = $oFilterModel->censorText($obj->content);
                }
                //or block publishing
                else {
                    $output = $oFilterModel->isDeniedWord( $obj->title . $obj->content );
                    if (!$output->toBool()) {
                        return $output;
                    }
                }
            }

            // Check the specified time beside the modificaiton time
            if($obj->document_srl == 0){
                $output = $oFilterModel->checkLimited();
                if(!$output->toBool()) return $output;
            }

            // Save a log
            $this->insertLog();

            return new Object();
        }

        /**
         * @brief The routine process to check the time it takes to store a comment, and to ban IP/word
         **/
        function triggerInsertComment(&$obj) {
            if(!empty($_SESSION['avoid_log'])) {
                return new Object();
            }
            // Check the login status, login information, and permission
            $is_logged = Context::get('is_logged');
            $logged_info = Context::get('logged_info');
            $grant = Context::get('grant');
            // In case logged in, check if it is an administrator
            if (false && $is_logged) {
                if($logged_info->is_admin == 'Y') {
                    return new Object();
                }
                if ($grant->manager) {
                    return new Object();
                }
            }

            $oFilterModel = getModel('spamfilter');

            // Check if the IP is prohibited
            $output = $oFilterModel->isDeniedIP();
            if (!$output->toBool()) {
                return $output;
            }

            // Check words blacklist
            $config = $this->getSpamConfig();
            if (in_array('comments', (array) $config->usage)) {
                //replace words
                if ($config->action_bad_words == 'replace') {
                    $obj->content = $oFilterModel->censorText($obj->content);
                }
                //or block publishing
                else {
                    $output = $oFilterModel->isDeniedWord($obj->content);
                    if (!$output->toBool()) {
                        return $output;
                    }
                }
            }

            // If the specified time check is not modified
            if(!$obj->__isupdate) {
                $output = $oFilterModel->checkLimited();
                if(!$output->toBool()) return $output;
            }
            unset($obj->__isupdate);

            // Save a log
            $this->insertLog();

            return new Object();
        }

        /**
         * @brief Use Akismet API to check comments
         */
        function triggerAkismetCheckComment(&$obj)
        {
            $logged_info = Context::get('logged_info');
            if ($logged_info->is_admin == 'Y') return new Object();
            $oModuleModel = getModel('module');
            $spamConfig = $oModuleModel->getModuleConfig('spamfilter');
            if ($key = $spamConfig->akismet_api_key) //if spam checking enabled
            {
                $akismet = new Akismet(getSiteUrl(), $key);
                $akismet->setCommentAuthorEmail($obj->email_address);
                $akismet->setCommentAuthorURL($obj->homepage);
                $akismet->setCommentContent($obj->content);
                $url = getSiteUrl().$obj->document_srl;
                $akismet->setPermalink($url);
                if ($akismet->isCommentSpam()) {
                    $obj->status = 2;
                }
            }
            return new Object();
        }

        /**
         * @brief Inspect the trackback creation time and IP
         **/
        function triggerInsertTrackback(&$obj) {
            if($_SESSION['avoid_log']) return new Object();

            $oFilterModel = getModel('spamfilter');
            // Confirm if the trackbacks have been added more than once to your document
            $output = $oFilterModel->isInsertedTrackback($obj->document_srl);
            if(!$output->toBool()) return $output;
            
            // Check if the IP is prohibited
            $output = $oFilterModel->isDeniedIP();
            if(!$output->toBool()) return $output;
            // Check if there is a ban on the word
            $text = $obj->blog_name.$obj->title.$obj->excerpt.$obj->url;
            $output = $oFilterModel->isDeniedWord($text);
            if(!$output->toBool()) return $output;
            // Start Filtering
            $oTrackbackModel = getModel('trackback');
            $oTrackbackController = getController('trackback');

            list($ipA,$ipB,$ipC,$ipD) = explode('.',$_SERVER['REMOTE_ADDR']);
            $ipaddress = $ipA.'.'.$ipB.'.'.$ipC;
            // In case the title and the blog name are indentical, investigate the IP address of the last 6 hours, delete and ban it.
            if($obj->title == $obj->excerpt) {
                $oTrackbackController->deleteTrackbackSender(60*60*6, $ipaddress, $obj->url, $obj->blog_name, $obj->title, $obj->excerpt);
                $this->insertIP($ipaddress.'.*', 'AUTO-DENIED : trackback.insertTrackback');
                return new Object(-1,'msg_alert_trackback_denied');
            }
            // If trackbacks have been registered by one C-class IP address more than once for the last 30 minutes, ban the IP address and delete all the posts
            /* Given hosting environment so that one does not work, comment at this part
            $count = $oTrackbackModel->getRegistedTrackback(30*60, $ipaddress, $obj->url, $obj->blog_name, $obj->title, $obj->excerpt);
            if($count > 1) {
                $oTrackbackController->deleteTrackbackSender(3*60, $ipaddress, $obj->url, $obj->blog_name, $obj->title, $obj->excerpt);
                $this->insertIP($ipaddress.'.*');
                return new Object(-1,'msg_alert_trackback_denied');
            }
            */

            return new Object();
        }

        /**
         * @brief IP registration
         * The registered IP address is considered as a spammer
         **/
        function insertIP($ipaddress_list, $description = null) {
			$ipaddress_list = str_replace("\r","",$ipaddress_list);
			$ipaddress_list = explode("\n",$ipaddress_list);
            $args = new stdClass();
            $matches = array();
			foreach($ipaddress_list as $ipaddressValue) {
                //IPv6 check
                preg_match('/^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/', $ipaddressValue, $matches);
                if (isset($matches[1])){
                    if($ipaddress = trim($matches[1])) {
                        $args->ipaddress = $ipaddress;
                        $args->description = $description;
                    }
                }
                else {
                    //IPv4 check
                    preg_match("/(\d{1,3}(?:.(\d{1,3}|\*)){3})\s*(\/\/(.*)\s*)?/",$ipaddressValue,$matches);
                    if (isset($matches[1])){
                        if($ipaddress=trim($matches[1])) {
                            $args->ipaddress = $ipaddress;
                            if(!$description && !empty($matches[4])) {
                                $args->description = $matches[4];
                            }
                            else {
                                $args->description = $description;
                            }
                        }
                    }
                }

				$output = executeQuery('spamfilter.insertDeniedIP', $args);

				if(!$output->toBool()) return $output;
			}
			return $output;

        }

        /**
         * @brief Log registration
         * Register the newly accessed IP address in the log. In case the log interval is withing a certain time,
         * register it as a spammer
         **/
        function insertLog() {
            $output = executeQuery('spamfilter.insertLog');
            return $output;
        }

        /**
         * @return array
         */
        public function getSpamConfig()
        {
            $oModuleModel = getModel('module');
            $spamConfig = $oModuleModel->getModuleConfig('spamfilter');
            return $spamConfig;
        }
    }
?>
