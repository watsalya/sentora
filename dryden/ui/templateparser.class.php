<?php

/**
 * @package zpanelx
 * @subpackage dryden -> ui
 * @author Bobby Allen (ballen@zpanelcp.com)
 * @copyright ZPanel Project (http://www.zpanelcp.com/)
 * @link http://www.zpanelcp.com/
 * @license GPL (http://www.gnu.org/licenses/gpl.html)
 */
class ui_templateparser {

    /**
     * Loads in the template content and parses it to compute the place holder content.
     * @param string $template_path The full path to the system template (or user template)
     * @return sting 
     */
    static function Generate($template_path) {
        $template_raw = file_get_contents($template_path . "/master.ztml");
        $template_html = ui_templateparser::Process($template_raw);
        return eval('?>' . $template_html);
    }

    /**
     * Replaces the template place holders with the equivilent dynamic infomation.
     * @param string $raw
     * @return string 
     */
    static function Process($raw) {
        /**
         * @todo Add dynamic place holders which relate to class methods (for easy extending) - All place holders will be placed in an XML file.
         * for now the below is just a test!
         */
        global $controller;
        $tplp = new runtime_dataobject;


        /*
         * Register some 'core' template place holders!
         */
        $tplp->addItemValue('assetfolderpath', 'etc/styles/zpanelx/');
        $tplp->addItemValue('version_apache', sys_versions::ShowApacheVersion());
        $tplp->addItemValue('version_php', sys_versions::ShowPHPVersion());
        $tplp->addItemValue('version_mysql', sys_versions::ShowMySQLVersion());
        $tplp->addItemValue('version_zpanel', ctrl_options::GetOption('dbversion'));
        $tplp->addItemValue('version_platform', sys_versions::ShowOSPlatformVersion());
        $tplp->addItemValue('version_kernal', sys_versions::ShowOSKernalVersion(sys_versions::ShowOSPlatformVersion()));
        $tplp->addItemValue('version_osname', sys_versions::ShowOSName());
        $tplp->addItemValue('client_ipaddress', sys_monitoring::ClientIPAddress());
        $tplp->addItemValue('server_ipaddress', sys_monitoring::ServerIPAddress());
        $tplp->addItemValue('uptime', sys_monitoring::ServerUptime());

        $tplp->addItemValue('module', ui_module::getModule($controller->getCurrentModule()));

        foreach ($tplp->getDataObject() as $placeholder => $replace) {
            $raw = str_replace("<% " . $placeholder . " %>", $replace, $raw);
        }

        /*
         * Load class template holders (if the class exists) - It will execute 'Template()' method from the class.
         */
        preg_match_all("'<#\s(.*?)\s#>'si", $raw, $match);
        if ($match) {
            foreach ($match[1] as $classes) {
                if (class_exists('' . $classes . '')) {
                    $raw = str_replace("<# " . $classes . " #>", call_user_func(array($classes, 'Template')), $raw);
                }
            }
        }

        /*
         * Load module controller output placeholders!
         */
        preg_match_all("'<@\s(.*?)\s@>'si", $raw, $match);
        if ($match) {
            foreach ($match[1] as $classes) {
                #if (class_exists('' . $classes . '')) {
                ob_start();
                eval("echo module_controller::get" . $classes . "();");
                $output = ob_get_contents();
                ob_end_clean();
                $raw = str_replace("<@ " . $classes . " @>", $output, $raw);
                #}
            }
        }
        
        /*
         * PHP Execution protection!
         * @todo Add an error logging trap here!
         */
        $raw = str_replace('<?', 'PHP execution is not permitted! Caught: [', $raw);
        $raw = str_replace('?>', ']', $raw);

        return $raw;
    }

}

?>