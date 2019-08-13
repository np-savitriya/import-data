<?php
/**
 * Created by PhpStorm.
 * User: ravin
 * Date: 4/3/19
 * Time: 12:06 PM
 */

namespace Import\ImportData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Module extends Model
{
    use SoftDeletes;
    protected $table = 'modules';

    protected $fillable = ['name', 'import_enabled','created_at', 'updated_at','deleted_at'];
}