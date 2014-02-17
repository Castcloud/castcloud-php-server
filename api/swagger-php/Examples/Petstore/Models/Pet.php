<?php
namespace Petstore\Models;

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
 *
 * @package
 * @category
 * @subpackage
 */
use Swagger\Annotations as SWG;

/**
 * @package
 * @category
 * @subpackage
 *
 * @SWG\Model(id="Pet",required="id, name")
 */
class Pet
{
    /**
     * @SWG\Property(name="id",type="integer",format="int64",description="Unique identifier for the Pet",minimum="0.0",maximum="100.0")
     */
    public $id;

    /**
     * @SWG\Property(name="name",type="string",description="Friendly name of the pet")
     */
    public $name;

    /**
     * @SWG\Property(name="category",type="Category",description="Category the pet is in")
     */
    public $category;

    /**
     * @SWG\Property(name="photoUrls",type="array",@SWG\Items("string"),description="Image URLs")
     */
    public $photos;

    /**
     * @SWG\Property(name="tags",type="array",@SWG\Items("Tag"),description="Tags assigned to this pet")
     */
    public $tags;

    /**
     * @SWG\Property(name="status",type="string",description="pet status in the store",enum="['available','pending','sold']")
     */
    public $status;

}

