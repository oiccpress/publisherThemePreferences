<?php

namespace APP\plugins\generic\publisherPreferences;

use APP\core\Application;
use APP\handler\Handler;
use PKP\navigationMenu\NavigationMenuItemDAO;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\db\DAORegistry;
use PKP\navigationMenu\NavigationMenuItem;
use PKP\security\Role;

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

        $currentUser = $request->getUser();
        $currentContext = $request->getContext();

        $isAdmin = $currentUser->hasRole([Role::ROLE_ID_MANAGER], $currentContext->getId()) || $currentUser->hasRole([Role::ROLE_ID_SITE_ADMIN], \PKP\core\PKPApplication::SITE_CONTEXT_ID);
        if(!$isAdmin) {
            return new JSONMessage(false, 'not admin');
        }

        $copyFromContext = $_POST['journal'];

        echo '<div style="display:flex;height:100vh;justify-content:center;align-items:center;flex-direction:column"><h1>Copy Journal Items</h1>';
        
        // Copy here
        if(class_exists('APP\plugins\generic\blockPages\classes\BlockPage')) {
            // copy block pages from other journal
            $blockPagesDao = DAORegistry::getDAO('BlockPagesDAO');
            $pages = $blockPagesDao->getByContextId($copyFromContext);
            foreach($pages->toAssociativeArray() as $page) {
                $page->setContextId($currentContext->getId());
                $blockPagesDao->insertObject($page);
                echo '<p>copied page ' . htmlentities($page->getData('title')) . ' across</p>';

                $navMenuItem = new NavigationMenuItem();
                $navMenuItem->setContextId( $currentContext->getId() );
                $navMenuItem->setTitle( $page->getData('title'), "en" );
                $navMenuItem->setType( NavigationMenuItem::NMI_TYPE_REMOTE_URL );
                $navMenuItem->setPath("");
                $navMenuItem->setContent("", "en");
                $navMenuItem->setRemoteUrl( $request->getDispatcher()->url($request, Application::ROUTE_PAGE, $currentContext?->getPath() ?? '', $page->getData('path') ), "en" );
    
                $navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO'); /** @var NavigationMenuItemDAO $navigationMenuItemDao */
                $navigationMenuItemDao->insertObject($navMenuItem);
                echo '<p>created navigation menu item</p>';
            }
            echo '<p>copied pages</p>';
        }
        
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