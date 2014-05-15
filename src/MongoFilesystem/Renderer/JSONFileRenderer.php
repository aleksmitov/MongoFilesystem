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
<?php
namespace MongoFilesystem\Renderer;
use MongoFilesystem\Renderer\FileRenderer;
use MongoFilesystem\MongoFile;
class JSONFileRenderer extends FileRenderer
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
        parent::__construct($file, $pathToViews, 'json');
    }
}