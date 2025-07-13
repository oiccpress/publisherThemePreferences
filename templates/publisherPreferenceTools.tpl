<div class="pkp_page_content pkp_page_importexport_plugins">
	
    <form id="publisherPreferenceCopyJournal" class="pkp_form" action="{url page="publisherpreferences" op="copyJournal"}" method="post">
        {csrf}

        <h2>{translate key="plugins.generic.publisherPreferences.copyJournal"}</h2>

        <select name="journal">
            {foreach from=$journals item=journal}
                <option value="{$journal->getId()}">{$journal->getLocalizedAcronym()} {$journal->getLocalizedName()}</option>
            {/foreach}
        </select>

        {fbvFormButtons submitText="plugins.generic.publisherPreferences.next" hideCancel="true"}

    </form>

    <hr/>

    <form id="publisherPreferenceCopyJournal" class="pkp_form" action="{url page="publisherpreferences" op="copyTemplate"}" method="post">
        {csrf}

        <h2>{translate key="plugins.generic.publisherPreferences.copyTemplate"}</h2>

        <select name="template">
            {foreach from=$templates item=template}
                <option value="{$template}">{$template}</option>
            {/foreach}
        </select>

        {fbvFormButtons submitText="plugins.generic.publisherPreferences.next" hideCancel="true"}

    </form>

    <hr />

    <form id="publisherPreferenceCopyReviewForm" class="pkp_form" action="{url page="publisherpreferences" op="copyReviewForm"}" method="post">
        {csrf}

        <h2>{translate key="plugins.generic.publisherPreferences.copyReviewForm"}</h2>

        <select name="form">
            {foreach from=$forms key=$key item=template}
                <option value="{$key}">{$template}</option>
            {/foreach}
        </select>

        <select name="journal">
            {foreach from=$journals item=journal}
                <option value="{$journal->getId()}">{$journal->getLocalizedAcronym()} {$journal->getLocalizedName()}</option>
            {/foreach}
        </select>

        {fbvFormButtons submitText="plugins.generic.publisherPreferences.next" hideCancel="true"}

    </form>

</div>