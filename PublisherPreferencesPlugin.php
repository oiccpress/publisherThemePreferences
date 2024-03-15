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
use APP\template\TemplateManager;
use Illuminate\Support\Facades\DB;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class PublisherPreferencesPlugin extends GenericPlugin {

    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            Hook::add( 'Templates::Admin::Index::AdminFunctions', [$this, 'updateJournalSettings'] );
            Hook::add( 'Form::config::after', [$this, 'contextSettings'] );
        }

        return $success;
    }

    public function contextSettings( $hookName, &$args )
    {
        $config = &$args[0];
        if($config['id'] == 'theme') {
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

        if($update > -1) {

            // Ensure the theme plugin is enabled in the journal
            $allThemes = PluginRegistry::loadCategory('themes', true);
            foreach(array_keys($allThemes) as $themeName) {

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

            $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
            $templateMgr->clearTemplateCache();
            $templateMgr->clearCssCache();
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
        return 'This plugin ensures that the publsihers theme is used for their journals, ensuring a consistent user experience.';
    }

}
