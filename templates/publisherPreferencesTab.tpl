<tab id="publisherPreferences" label="{translate key="plugins.generic.publisherPreferences.preferences"}">
{capture assign=pubPrefPageGridUrl}{url router=PKP\core\PKPApplication::ROUTE_COMPONENT component="plugins.generic.publisherPreferences.controllers.grid.PreferredPluginGridHandler" op="fetchGrid" escape=false}{/capture}
{load_url_in_div id="publisherPreferencesGrid" url=$pubPrefPageGridUrl}
</tab>
