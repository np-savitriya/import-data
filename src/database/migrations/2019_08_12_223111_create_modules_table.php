<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	if (!Schema::hasTable('modules')) {
	    Schema::create('modules', function (Blueprint $table) {
		$table->increments('id');
		$table->string('name');
		$table->enum('import_enabled',['yes','no'])->default('no');  
		$table->timestamps();
	    	$table->softDeletes();
	   });
	}else{
	    Schema::table('modules', function (Blueprint $table) {
		$table->enum('import_enabled',['yes','no'])->default('no');
	   });
	}

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modules');
    }
}

