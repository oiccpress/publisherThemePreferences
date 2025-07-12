<?php

namespace APP\plugins\generic\publisherPreferences\controllers\grid;

use PKP\controllers\grid\GridHandler;
use PKP\controllers\grid\plugins\PluginGridCellProvider;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class PreferredPluginGridCellProvider extends PluginGridCellProvider {

    protected $plugin;

    public function __construct($plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;
    }

    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $plugin = & $row->getData();
        $columnId = $column->getId();
        assert(is_a($plugin, 'Plugin') && !empty($columnId));

        switch ($columnId) {
            case 'enabled':
                $plugins = $this->plugin->getPreferredPlugins();
                $isEnabled = in_array($plugin->getName(), $plugins);
                return [
                    'selected' => $isEnabled,
                    'disabled' => false,
                ];
            default:
                break;
        }

        return parent::getTemplateVarsFromRowColumn($row, $column);
    }

    /**
     * @copydoc GridCellProvider::getCellActions()
     */
    public function getCellActions($request, $row, $column, $position = GridHandler::GRID_ACTION_POSITION_DEFAULT)
    {
        switch ($column->getId()) {
            case 'enabled':
                $plugins = $this->plugin->getPreferredPlugins();
                $plugin = $row->getData(); /** @var Plugin $plugin */
                $requestArgs = array_merge(
                    ['plugin' => $plugin->getName()],
                    $row->getRequestArgs()
                );
                switch (true) {
                    case in_array($plugin->getName(), $plugins):
                        // Create an action to disable the plugin
                        return [new LinkAction(
                            'disable',
                            new RemoteActionConfirmationModal(
                                $request->getSession(),
                                __('grid.plugin.disable'),
                                __('common.disable'),
                                $request->url(null, null, 'disable', null, $requestArgs)
                            ),
                            __('manager.plugins.disable'),
                            null
                        )];
                    default:
                        // Create an action to enable the plugin
                        return [new LinkAction(
                            'enable',
                            new AjaxAction(
                                $request->url(null, null, 'enable', null, array_merge(
                                    ['csrfToken' => $request->getSession()->token()],
                                    $requestArgs
                                ))
                            ),
                            __('manager.plugins.enable'),
                            null
                        )];
                }
        }
        return parent::getCellActions($request, $row, $column, $position);
    }

}