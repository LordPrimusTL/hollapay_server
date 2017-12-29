<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillsTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bills_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('organization_id');
            $table->string('tid');
            $table->string('bills_type')->comment="DSTV,GOTV e.t.c";
            $table->float('amount', 12,2);
            $table->string('status');
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
        Schema::dropIfExists('bills_transactions');
    }
}
