<?php
namespace MongoFilesystem\Renderer;
use MongoFilesystem\MongoFolder;
use MongoFilesystem\MongoFile;
use MongoFilesystem\Renderer\FolderRenderer;
use MongoFilesystem\Renderer\HTMLFileRenderer;
class HTMLFolderRenderer extends FolderRenderer
{
    public function __construct(MongoFolder $folder, $pathToViews)
    {
        parent::__construct($folder, $pathToViews, 'html');
    }

    protected function getFileRenderer(MongoFile $file) {
        return new HTMLFileRenderer($file, $this->pathToViews);
    }
    protected function getFolderRenderer(MongoFolder $folder) {
        return new HTMLFolderRenderer($folder, $this->pathToViews);
    }
}
