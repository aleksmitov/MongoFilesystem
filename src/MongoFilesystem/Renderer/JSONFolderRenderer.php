<?php
namespace MongoFilesystem\Renderer;
use MongoFilesystem\MongoFolder;
use MongoFilesystem\MongoFile;
use MongoFilesystem\Renderer\FolderRenderer;
use MongoFilesystem\Renderer\JSONFileRenderer;
class JSONFolderRenderer extends FolderRenderer
{
    public function __construct(MongoFolder $folder, $pathToViews)
    {
        parent::__construct($folder, $pathToViews, 'json');
    }

    protected function getFileRenderer(MongoFile $file) {
        return new JSONFileRenderer($file, $this->pathToViews);
    }
    protected function getFolderRenderer(MongoFolder $folder) {
        return new JSONFolderRenderer($folder, $this->pathToViews);
    }
}
