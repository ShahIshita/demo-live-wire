<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryFieldsToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('stripe_payment_status');
            $table->string('delivery_tracking_number', 100)->nullable()->after('delivery_date');
            $table->string('delivery_carrier', 50)->nullable()->after('delivery_tracking_number');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_date', 'delivery_tracking_number', 'delivery_carrier']);
        });
    }
}
