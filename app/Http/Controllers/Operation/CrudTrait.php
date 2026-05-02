<?php

namespace App\Http\Controllers\Operation;

use Backpack\CRUD\app\Models\Traits\HasEnumFields;
use Backpack\CRUD\app\Models\Traits\HasFakeFields;
use Backpack\CRUD\app\Models\Traits\HasIdentifiableAttribute;
use Backpack\CRUD\app\Models\Traits\HasRelationshipFields;
use Backpack\CRUD\app\Models\Traits\HasTranslatableFields;
use Backpack\CRUD\app\Models\Traits\HasUploadFields;


trait CrudTrait
{
    use HasIdentifiableAttribute;
    use HasEnumFields;
    use HasRelationshipFields;
    // use HasUploadFields;
    use HasFakeFields;
    use HasTranslatableFields;

    public static function hasCrudTrait()
    {
        return true;
    }
}
