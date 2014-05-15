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
namespace MongoFilesystem\Exceptions;
use \ErrorException;
use \MongoId;

class MongoFolderNotFoundException extends ErrorException
{
    /**
     * @param string $name The foldername
     * @param MongoId $ID
     */
    public function __construct(MongoId $ID, $name = "")
    {
        $message = "A MongoFolder with ";
        if($name != "")
        {
            $message .= "name: '" . $name . "' and ";
        }
        $message .= "MongoId: " . $ID . " does NOT exist.";
        parent::__construct($message);
    }
}
