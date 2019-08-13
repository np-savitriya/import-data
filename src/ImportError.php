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

class ImportError extends Model
{
    use SoftDeletes;
    protected $table = 'import_data_errors';

    protected $fillable = ['module_id', 'error_reason', 'duplicate_flag', 'fields', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at'];
}