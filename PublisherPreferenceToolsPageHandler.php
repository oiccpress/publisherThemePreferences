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
use APP\journal\Journal;
use APP\core\Request;
use PKP\navigationMenu\NavigationMenuItemAssignment;
use PKP\navigationMenu\NavigationMenuItemAssignmentDAO;
use PKP\navigationMenu\NavigationMenuDAO;
use APP\plugins\generic\blockPages\classes\BlockPage;

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

    public function copyTemplate($args, $request)
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

        $copyFromContext = $_POST['template'];
        $template = file_get_contents( dirname(__FILE__) . '/journalTemplates/' . $copyFromContext );
        $template = json_decode($template, true);
        if(!$template) {
            return new JSONMessage(false, 'Not valid template');
        }

        echo '<div style="display:flex;height:100vh;justify-content:center;align-items:center;flex-direction:column"><h1>Copy Journal from Template</h1>';
        
        foreach($template as $where => $items) {

            $navigationMenuDao = DAORegistry::getDAO('NavigationMenuDAO'); /** @var NavigationMenuDAO $navigationMenuDao */
            $navigationMenusWithArea = $navigationMenuDao->getByArea($currentContext->getId(), $where)->toArray();
            $menuId = $navigationMenusWithArea[0]->getId();
            if(!$menuId) {
                return new JSONMessage(false, 'Navigation menu does not exist on this current journal: ' . $where);
            }
            echo '<p>Copying to menu id ' . $menuId . '</p>';

            $navigationMenuItemAssignmentDao = DAORegistry::getDAO('NavigationMenuItemAssignmentDAO'); /** @var NavigationMenuItemAssignmentDAO $navigationMenuItemAssignmentDao */
            $navigationMenuItemAssignmentDao->deleteByMenuId($menuId);

            foreach($items as $item) {
                $this->copyItem( $item, $currentContext, $request, $menuId );
            }
        }
        echo '<p>Done</p>';
        
        echo '</div>';

    }

    /**
     * @param string $input
     * @param Journal $currentContext
     * @param Request $request
     */
    public function replaceVars($input, $currentContext, $request) {
        $contextUrl = $request->getRouter()->url($request, $currentContext->getPath());
        $input = str_replace(
            [ '{journalName}', '{abbreviationUpper}', '{contextURL}' ],
            [ $currentContext->getName('en'), strtoupper($currentContext->getAcronym('en')), $contextUrl ],
            $input
        );
        return $input;
    }

    public $sequence = 0;

    public function copyItem($item, $currentContext, $request, $menuId, $parentId = null) {
        $navMenuItem = new NavigationMenuItem();
        $navMenuItem->setContextId( $currentContext->getId() );
        $navMenuItem->setTitle( $this->replaceVars( $item['label'], $currentContext, $request ), "en" );

        switch($item['type']) {
            case 'url':
                $navMenuItem->setType( NavigationMenuItem::NMI_TYPE_REMOTE_URL );
                $navMenuItem->setPath("");
                $navMenuItem->setContent("", "en");
                $navMenuItem->setRemoteUrl( $this->replaceVars( $item['url'], $currentContext, $request ), "en" );
                break;

            case 'submissions':
                $navMenuItem->setType( NavigationMenuItem::NMI_TYPE_SUBMISSIONS );
                $navMenuItem->setPath("");
                $navMenuItem->setContent("", "en");
                break;
            case 'archives':
                $navMenuItem->setType( 'NMI_TYPE_ARCHIVES' );
                $navMenuItem->setPath("");
                $navMenuItem->setContent("", "en");
                break;
            case 'currentIssue':
                $navMenuItem->setType( 'NMI_TYPE_CURRENT' );
                $navMenuItem->setPath("");
                $navMenuItem->setContent("", "en");
                break;

            case 'blockPage':
                if(!class_exists('APP\plugins\generic\blockPages\classes\BlockPage')) {
                    echo '<p>CANNOT CREATE ITEM: blockPages not enabled!!!</p>';
                    return;
                }
                // Create the block page
                $blockPagesDao = DAORegistry::getDAO('BlockPagesDAO');
                $blockPage = $blockPagesDao->newDataObject();
                $blockPage->setContextId( $currentContext->getId() );
                $blockPage->setPath( $item['path'] );
                $blockPage->setTitle( $this->replaceVars( $item['title'] ?? $item['label'], $currentContext, $request ), null );
                $blockPage->setContent( $this->replaceVars( json_encode( $item['content']), $currentContext, $request ), null );
                $blockPagesDao->insertObject( $blockPage );

                $navMenuItem->setType( NavigationMenuItem::NMI_TYPE_REMOTE_URL );
                $navMenuItem->setPath("");
                $navMenuItem->setContent("", "en");

                $navMenuItem->setRemoteUrl( $request->getDispatcher()->url($request, Application::ROUTE_PAGE, $currentContext?->getPath() ?? '', $item['path'] ), "en" );
    
                break;
        }

        // Save nav menu
        $navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO'); /** @var NavigationMenuItemDAO $navigationMenuItemDao */
        $navMenuItemId = $navigationMenuItemDao->insertObject($navMenuItem);

        // Now assign to menu
        $navigationMenuItemAssignmentDao = DAORegistry::getDAO('NavigationMenuItemAssignmentDAO'); /** @var NavigationMenuItemAssignmentDAO $navigationMenuItemAssignmentDao */

        $assignment = new NavigationMenuItemAssignment();
        $assignment->setMenuId($menuId);
        $assignment->setMenuItemId($navMenuItemId);
        $assignment->setSequence( $this->sequence );
        $this->sequence += 1;
        if ($parentId) {
            $assignment->setParentId($parentId);
        }
        $navigationMenuItemAssignmentDao->insertObject($assignment);

        // Now run through any child items
        if(isset($item['children'])) {
            foreach($item['children'] as $child) {
                $this->copyItem( $child, $currentContext, $request, $menuId, $navMenuItemId );
            }
        }

    }

    public static function filename($input) {
        $o = explode("/", $input);
        return array_pop($o);
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
            'templates' => array_map( [ static::class, 'filename' ], glob( dirname(__FILE__) . '/journalTemplates/*.json' ) ),
        ]);

        return $templateMgr->fetchJson(
            $this->plugin->getTemplateResource(
                'publisherPreferenceTools.tpl'
            )
        );
    }

}