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
use \MongoId;
use \ErrorException;
class MongoFileNotFoundException extends ErrorException
{
    /**
     * @param string $name
     * @param string $extension
     * @param MongoId $ID
     */
    public function __construct(MongoId $ID, $name = NULL, $extension = NULL) {
        
        $message = "A MongoFile ";
        if($name !== NULL)
        {
            $message .= $name;
            if($extension != NULL && $extension != "")
            {
                $message .= "." . $extension;
            }
        }
        $message .= " with MongoId: " . $ID . " does NOT exist.";
        parent::__construct($message);
    }
}

