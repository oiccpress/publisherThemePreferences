<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#updateCoverForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#newcoverImageUploader'),
				$preview: $('#newcoverImagePreview'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode page="publisherpreferences" op="uploadCoverImage"},
					baseUrl: {$baseUrl|json_encode},
					filters: {ldelim}
						mime_types : [
							{ldelim} title : "Image files", extensions : "jpg,jpeg,png,svg" {rdelim}
						]
					{rdelim}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<tab id="updateCover" label="{translate key="plugins.generic.publisherPreferences.updateCover"}">
    <form class="pkp_form" id="updateCoverForm" method="post" action="{url page="publisherpreferences" op="uploadCover"}">
        {csrf}

        <p>
            {translate key="plugins.generic.publisherPreferences.updateCoverDescription"}
        </p>

        {fbvFormArea id="coverImage" title="editor.issues.coverPage"}
            {fbvFormSection}
                {include file="controllers/fileUploadContainer.tpl" id="newcoverImageUploader"}
                <input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
            {/fbvFormSection}
            {fbvFormSection id="newcoverImagePreview"}
                {if $coverImage != ''}
                    <div class="pkp_form_file_view pkp_form_image_view">
                        <div class="img">
                            <img src="{$publicFilesDir}/{$coverImageName|escape:"url"}{'?'|uniqid}" {if $imageAltText !== ''} alt="{$imageAltText|escape}"{/if}>
                        </div>

                        <div class="data">
                            <span class="title">
                                {translate key="common.altText"}
                            </span>
                            <span class="value">
                                {fbvElement type="text" id="coverImageAltText" label="common.altTextInstructions" value=$coverImageAltText}
                            </span>

                            <div id="{$deleteCoverImageLinkAction->getId()}" class="actions">
                                {include file="linkAction/linkAction.tpl" action=$deleteCoverImageLinkAction contextId="issueForm"}
                            </div>
                        </div>
                    </div>
                {/if}
            {/fbvFormSection}
        {/fbvFormArea}

		{fbvFormArea id="UpdateCoverFormArea"}

			{fbvFormButtons id="updateCoverFormSubmit" submitText="common.save" hideCancel=true}
		{/fbvFormArea}
	</form>
</tab>