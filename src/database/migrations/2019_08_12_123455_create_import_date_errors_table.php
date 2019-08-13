<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImportDataErrorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_data_errors', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('module_id');
            $table->text('error_reason');
            $table->tinyInteger('duplicate_flag')->default(0);
            $table->text('fields');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->datetime('created_at');
            $table->datetime('updated_at');
            $table->softDeletes();


	        $table->index("module_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_data_errors');
    }
}

