<?php

namespace APP\plugins\generic\publisherPreferences\controllers\grid;

use PKP\controllers\grid\plugins\PluginGridCellProvider;

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

}