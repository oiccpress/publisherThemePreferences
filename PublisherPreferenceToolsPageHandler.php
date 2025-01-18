<?php

namespace APP\plugins\generic\publisherPreferences;

use APP\handler\Handler;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\db\DAORegistry;

class PublisherPreferenceToolsPageHandler extends Handler {

    public PublisherPreferencesPlugin $plugin;

    public function __construct(PublisherPreferencesPlugin $plugin)
    {
        parent::__construct();

        $this->plugin = $plugin;
    }

    public function copyJournal($args, $request)
    {

        if (!$request->checkCSRF()) {
            return new JSONMessage(false);
        }

        $currentContext = $request->getContext();
        $copyFromContext = $_POST['journal'];

        echo '<div style="display:flex;height:100vh;justify-content:center;align-items:center;flex-direction:column"><h1>Copy Journal Items</h1>';
        
        // Copy here
        echo '<p>todo: copy journal items</p>';
        
        echo '</div>';

    }

    public function index($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);

        $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */
        $journals = [];
        $currentContext = $request->getContext();
        foreach($journalDao->getAll(true)->toArray() as $journal) {
            if($currentContext->getId() != $journal->getId()) {
                $journals[] = $journal;
            }
        }

        $templateMgr->assign([
            'journals' => $journals,
        ]);

        return $templateMgr->fetchJson(
            $this->plugin->getTemplateResource(
                'publisherPreferenceTools.tpl'
            )
        );
    }

}