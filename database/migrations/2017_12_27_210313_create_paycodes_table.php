<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaycodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paycodes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('organization_id');
            $table->string('pay_id');
            $table->string('code');
            $table->float('amount',10,2);
            $table->string('pin')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->string('frontend_partner_id')->nullable();
            $table->integer('status')->default(0)->comment = "0 - Unused, 1 - Used, 2 - Cancel";
            $table->text('extras')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paycodes');
    }
}
