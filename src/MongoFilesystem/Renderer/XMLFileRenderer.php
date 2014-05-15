<?php
namespace MongoFilesystem\Renderer;
use MongoFilesystem\Renderer\FileRenderer;
use MongoFilesystem\MongoFile;
class XMLFileRenderer extends FileRenderer
{
    /**
     * The costructor
     * @param MongoFile $file A MongoFile intstance of the file for rendering
     * @param string $pathToViews - The directory of the template to render
     */
    public function __construct(MongoFile $file)
    {
        /**
         * Specifing the path the to views folder
         * @var string
         */
        $pathToViews = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'views';
        parent::__construct($file, $pathToViews, 'xml');
    }
}