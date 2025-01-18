<div class="pkp_page_content pkp_page_importexport_plugins">
	
    <form id="publisherPreferenceCopyJournal" class="pkp_form" action="{url page="publisherpreferences" op="copyJournal"}" method="post">
        {csrf}

        <h2>{translate key="plugins.generic.publisherPreferences.copyJournal"}</h2>

        <select name="journal">
            {foreach from=$journals item=journal}
                <option value="{$journal->getId()}">{$journal->getLocalizedName()}</option>
            {/foreach}
        </select>

        {fbvFormButtons submitText="plugins.generic.publisherPreferences.next" hideCancel="true"}

    </form>

</div>