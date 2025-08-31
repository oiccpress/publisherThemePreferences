<?php
namespace APP\plugins\generic\publisherPreferences\forms;

use PKP\core\PKPRequest;
use PKP\file\TemporaryFileManager;
use PKP\form\Form;

class NewJournalCoverForm extends Form {

    /**
     * @copydoc \PKP\form\Form::getLocaleFieldNames()
     */
    public function getLocaleFieldNames(): array
    {
        return ['imageAltText'];
    }

        /**
     * Upload a temporary file.
     */
    public function uploadFile(PKPRequest $request): int|bool
    {
        $user = $request->getUser();

        $temporaryFileManager = new TemporaryFileManager();
        $temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());

        if ($temporaryFile) {
            return $temporaryFile->getId();
        }

        return false;
    }

}