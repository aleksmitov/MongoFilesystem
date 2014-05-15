<?php
namespace MongoFilesystem\Renderer;
use MongoFilesystem\MongoFolder;
use MongoFilesystem\MongoFile;
use MongoFilesystem\Renderer\FolderRenderer;
use MongoFilesystem\Renderer\JSONFileRenderer;
class JSONFolderRenderer extends FolderRenderer
{
    public function __construct(MongoFolder $folder)
    {
        /**
         * Specifing the path the to views folder
         * @var string
         */
        $pathToViews = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'views';
        parent::__construct($folder, $pathToViews, 'json');
    }

    protected function getFileRenderer(MongoFile $file) {
        return new JSONFileRenderer($file);
    }
    protected function getFolderRenderer(MongoFolder $folder) {
        return new JSONFolderRenderer($folder);
    }
}
