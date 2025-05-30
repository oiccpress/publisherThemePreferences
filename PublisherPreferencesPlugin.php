<?php

/**
 * Main class for publisher preferences plugni
 * 
 * @author Joe Simpson
 * 
 * @class PublisherPreferencesPlugin
 *
 * @ingroup plugins_generic_publisherPreferences
 *
 * @brief Publisher Preferences
 */

namespace APP\plugins\generic\publisherPreferences;

use APP\core\Application;
use APP\plugins\generic\publisherPreferences\controllers\grid\PreferredPluginGridHandler;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\DB;
use PKP\core\Registry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class PublisherPreferencesPlugin extends GenericPlugin {

    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            Hook::add( 'Context::add', [ $this, 'updateJournalSettings' ] );
            Hook::add( 'Templates::Admin::Index::AdminFunctions', [$this, 'updateJournalSettings'] );
            Hook::add( 'Form::config::after', [$this, 'contextSettings'] );

            Hook::add( 'Template::Settings::admin', [$this, 'callbackShowWebsiteSettingsTabs']) ;
            Hook::add( 'LoadComponentHandler', [$this, 'setupGridHandler'] );

            Hook::add( 'LoadHandler', [$this, 'setPageHandler'] );
            Hook::add( 'Templates::Management::Settings::tools', [ $this, 'callbackShowToolsTabs' ] );
        }

        return $success;
    }

    public function getPreferredPlugins() {
        return $this->getSetting( \PKP\core\PKPApplication::CONTEXT_ID_NONE, 'preferredPlugins' ) ?: [];
    }

    public function setPreferredPlugins($plugins) {
        $this->updateSetting( \PKP\core\PKPApplication::CONTEXT_ID_NONE, 'preferredPlugins', $plugins );
    }

    /**
     * Permit requests to the grid handler
     *
     * @param string $hookName The name of the hook being invoked
     */
    public function setupGridHandler($hookName, $params)
    {
        $component = & $params[0];
        $componentInstance = & $params[2];
        if ($component == 'plugins.generic.publisherPreferences.controllers.grid.PreferredPluginGridHandler') {
            // Allow the static page grid handler to get the plugin object
            $componentInstance = new PreferredPluginGridHandler($this);
            return true;
        }
        return false;
    }

    /**
     * Extend the website settings tabs to include static pages
     *
     * @param string $hookName The name of the invoked hook
     * @param array $args Hook parameters
     *
     * @return bool Hook handling status
     */
    public function callbackShowWebsiteSettingsTabs($hookName, $args)
    {
        $templateMgr = $args[1];
        $output = & $args[2];
        $request = & Registry::get('request');
        $dispatcher = $request->getDispatcher();

        $output .= $templateMgr->fetch($this->getTemplateResource('publisherPreferencesTab.tpl'));

        // Permit other plugins to continue interacting with this hook
        return false;
    }

    public function callbackShowToolsTabs($hookName, $args)
    {
        $templateMgr = $args[1];
        $output = & $args[2];
        $request = & Registry::get('request');
        $dispatcher = $request->getDispatcher();

        echo $templateMgr->fetch($this->getTemplateResource('toolsTab.tpl'));

        // Permit other plugins to continue interacting with this hook
        return false;
    }

    public function setPageHandler(string $hookName, array $args): bool
    {
        $page =& $args[0];
        $handler =& $args[3];
        if ($this->getEnabled() && $page === 'publisherpreferences') {
            $handler = new PublisherPreferenceToolsPageHandler($this);
            return true;
        }
        return false;
    }

    public function contextSettings( $hookName, &$args )
    {
        $config = &$args[0];
        if($config['id'] == 'theme' && stripos($config['action'], 'site') === false) {
            foreach($config['fields'] as &$field) {
                if($field['name'] == 'themePluginPath') {
                    // Hide the theme selection from the UI as it'll be overriden on a semi-regular basis
                    $field['component'] = 'field-hidden';
                }
            }
        }
    }

    /**
     * Run a function to update all journals to use the same theme on a periodic basis
     */
    public function updateJournalSettings()
    {

        $activeTheme = Application::get()->getRequest()->getSite()->getData('themePluginPath');
        
        $update = DB::affectingStatement( 'UPDATE `journal_settings` SET `setting_value` = ? WHERE `setting_name` = ?', [ $activeTheme, 'themePluginPath' ] );

        if($update > 0) {

            $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
            $templateMgr->clearTemplateCache();
            $templateMgr->clearCssCache();
        }

        // Ensure the theme plugin is enabled in the journal
        $allThemes = PluginRegistry::loadCategory('themes', true, \PKP\core\PKPApplication::CONTEXT_ID_NONE );
        // Also make sure this plugin is loaded so that the journal can't change theme on a temp basis
        $plugins = array_merge( array_keys($allThemes), ['publisherpreferencesplugin', ], $this->getPreferredPlugins() );
        foreach($plugins as $themeName) {

            DB::affectingStatement("
                INSERT INTO `plugin_settings` ( plugin_name, context_id, setting_name, setting_value, setting_type )
                SELECT 
                    ? AS `plugin_name`,
                    `journals`.`journal_id` AS `context_id`,
                    'enabled' as `setting_name`,
                    '1' as `setting_value`,
                    'bool' as `setting_type`
                FROM `journals`
                ON DUPLICATE KEY UPDATE `setting_value` = '1'
            ", [ $themeName ]);

        }

    }

    /**
     * Provide a name for this plugin
     *
     * The name will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDisplayName()
    {
        return 'Publisher Preferences';
    }

    /**
     * Provide a description for this plugin
     *
     * The description will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDescription()
    {
        return 'This plugin ensures that the publishers theme is used for their journals, ensuring a consistent user experience.';
    }

}
