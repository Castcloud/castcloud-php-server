<?php
namespace Swagger\Contexts;

/**
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 *             Copyright [2013] [Robert Allen]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * @category   Swagger
 * @package    Swagger
 */

use Swagger\Annotations\Resource;

/**
 * MethodContext
 */
class MethodContext extends Context
{
    /**
     * @var string
     */
    private $method;

    /**
     * @param string         $method     method
     * @param string         $docComment docComment
     */
    public function __construct($method, $docComment)
    {
        parent::__construct($docComment);
        $this->method     = $method;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
}
