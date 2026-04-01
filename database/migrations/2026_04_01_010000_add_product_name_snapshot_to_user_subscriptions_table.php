<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddProductNameSnapshotToUserSubscriptionsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('user_subscriptions')) {
            return;
        }

        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_subscriptions', 'product_name_snapshot')) {
                $table->string('product_name_snapshot')->nullable()->after('product_id');
            }
        });

        if (!Schema::hasTable('products')) {
            return;
        }

        $productNameById = DB::table('products')->pluck('name', 'id');
        if ($productNameById->isEmpty()) {
            return;
        }

        DB::table('user_subscriptions')
            ->select(['id', 'product_id'])
            ->whereNull('product_name_snapshot')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($productNameById) {
                foreach ($rows as $row) {
                    $name = $productNameById->get((int) $row->product_id);
                    if (empty($name)) {
                        continue;
                    }

                    DB::table('user_subscriptions')
                        ->where('id', $row->id)
                        ->update(['product_name_snapshot' => $name]);
                }
            });
    }

    public function down()
    {
        if (!Schema::hasTable('user_subscriptions')) {
            return;
        }

        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('user_subscriptions', 'product_name_snapshot')) {
                $table->dropColumn('product_name_snapshot');
            }
        });
    }
}
