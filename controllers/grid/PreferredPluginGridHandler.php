<?php

namespace APP\plugins\generic\publisherPreferences\controllers\grid;

use APP\notification\NotificationManager;
use PKP\controllers\grid\admin\plugins\AdminPluginGridHandler;
use PKP\controllers\grid\CategoryGridHandler;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\plugins\PluginGridRow;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\notification\PKPNotification;

class PreferredPluginGridHandler extends AdminPluginGridHandler {

    protected $plugin;

    public function __construct($plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;
    }

    public function getRowInstance()
    {
        return new PluginGridRow([]);
    }

    public function initialize($request, $args = null)
    {

        CategoryGridHandler::initialize($request, $args);

        // Basic grid configuration
        $this->setTitle('common.plugins');

        // Set the no items row text
        $this->setEmptyRowText('grid.noItems');

        // Columns
        // This is a copy from OJS apart from this line to use the other provider
        $pluginCellProvider = new PreferredPluginGridCellProvider($this->plugin);
        $this->addColumn(
            new GridColumn(
                'name',
                'common.name',
                null,
                null,
                $pluginCellProvider,
                [
                    'showTotalItemsNumber' => true,
                    'collapseAllColumnsInCategories' => true
                ]
            )
        );

        $descriptionColumn = new GridColumn(
            'description',
            'common.description',
            null,
            null,
            $pluginCellProvider
        );
        $descriptionColumn->addFlag('html', true);
        $this->addColumn($descriptionColumn);

        $this->addColumn(
            new GridColumn(
                'enabled',
                'common.enabled',
                null,
                'controllers/grid/common/cell/selectStatusCell.tpl',
                $pluginCellProvider
            )
        );

    }

    /**
     * Enable a plugin.
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return JSONMessage JSON object
     */
    public function enable($args, $request)
    {
        $plugin = $this->getAuthorizedContextObject(PKPApplication::ASSOC_TYPE_PLUGIN); /** @var Plugin $plugin */
        if ($request->checkCSRF()) {

            if(in_array($plugin->getName(), $this->plugin->getPreferredPlugins())) {
                return $this->disable($args, $request);
            }
            
            $this->plugin->setPreferredPlugins(array_merge( $this->plugin->getPreferredPlugins(), [ $plugin->getName() ]));

            if (empty($args['disableNotification'])) {
                $user = $request->getUser();
                $notificationManager = new NotificationManager();
                $notificationManager->createTrivialNotification($user->getId(), PKPNotification::NOTIFICATION_TYPE_PLUGIN_ENABLED, ['pluginName' => $plugin->getDisplayName()]);
            }
            return \PKP\db\DAO::getDataChangedEvent($request->getUserVar('plugin'), $request->getUserVar($this->getCategoryRowIdParameterName()));
        }
        return new JSONMessage(false);
    }

    /**
     * Disable a plugin.
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return JSONMessage JSON object
     */
    public function disable($args, $request)
    {
        $plugin = $this->getAuthorizedContextObject(PKPApplication::ASSOC_TYPE_PLUGIN); /** @var Plugin $plugin */
        if ($request->checkCSRF()) {
            
            $plugins = $this->plugin->getPreferredPlugins();
            array_splice($plugins, array_search($plugin->getName(), $plugins), 1);
            $this->plugin->setPreferredPlugins($plugins);

            if (empty($args['disableNotification'])) {
                $user = $request->getUser();
                $notificationManager = new NotificationManager();
                $notificationManager->createTrivialNotification($user->getId(), PKPNotification::NOTIFICATION_TYPE_PLUGIN_DISABLED, ['pluginName' => $plugin->getDisplayName()]);
            }
            return \PKP\db\DAO::getDataChangedEvent($request->getUserVar('plugin'), $request->getUserVar($this->getCategoryRowIdParameterName()));
        }
        return new JSONMessage(false);
    }

}
