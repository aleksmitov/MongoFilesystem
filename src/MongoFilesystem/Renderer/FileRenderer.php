<?php
/*
* Copyright (c) 2014 Alexander Mitov
* 
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
* 
*  http://www.apache.org/licenses/LICENSE-2.0
* 
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/
namespace MongoFilesystem\Renderer;
use MongoFilesystem\MongoFile;
use \Twig_Loader_Filesystem;
use \Twig_Environment;
abstract class FileRenderer
{
    /**
     * Holds the MongoFile instance
     * @var MongoFolder
     */
    protected $file;
    protected $twig;
    protected $twigLoader;
    /**
     * @var string The path to the views to be rendered
     */
    protected $pathToViews;
    /**
     * The type of the template to render
     * @var string
     */
    protected $typePrefix;
    /**
     * The costructor
     * @param MongoFile $file A MongoFile intstance of the file for rendering
     * @param string $pathToViews
     * @param string $typePrefix The type of the template to render
     */
    public function __construct(MongoFile $file, $pathToViews, $typePrefix)
    {
        $this->file = $file;
        $this->twigLoader = new Twig_Loader_Filesystem($pathToViews);
        $this->twig = new Twig_Environment($this->twigLoader);
        $this->pathToViews = $pathToViews;
        $this->typePrefix = $typePrefix;
    }
    public function render()
    {
         $context = array();
         $context["name"] = $this->file->getFilename();
         $context["extension"] = $this->file->getExtension();
         $context["permissions"] = $this->file->getPermissions();
         $context["owner"] = $this->file->getOwner();
         $context["group"] = $this->file->getGroup();
         /**
          * @var DateTime
          */
         $context["lastModified"] = $this->file->getLastModifiedDate();
         $context["size"] = $this->file->getSize();
         $context["ID"] = (string)$this->file->getID();
         $result = $this->twig->render($this->typePrefix . "_file.phtml", $context);
         return $result;
    }
}