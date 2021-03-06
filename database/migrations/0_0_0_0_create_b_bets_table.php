<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBBetsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('b_bets'))Schema::create('b_bets', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id')->unsigned()->nullable()->index('bets_user_id_foreign');
			$table->integer('b_game_id')->unsigned()->nullable()->index('bets_game_id_foreign');
			$table->text('items', 65535);
			$table->integer('itemsCount');
			$table->float('price');
			$table->integer('from');
			$table->integer('to');
			$table->timestamps();
			$table->integer('vip');
			$table->text('msg', 65535);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('b_bets');
	}

}
