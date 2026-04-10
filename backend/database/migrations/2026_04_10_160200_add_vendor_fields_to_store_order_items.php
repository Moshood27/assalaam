<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->after('product_id');
            $table->decimal('vendor_amount', 15, 2)->nullable()->after('line_cost');
            $table->string('vendor_payout_reference')->nullable()->after('vendor_amount');
            $table->timestamp('vendor_paid_at')->nullable()->after('vendor_payout_reference');
            $table->index('vendor_id');
            $table->index('vendor_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('store_order_items', function (Blueprint $table) {
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['vendor_paid_at']);
            $table->dropColumn(['vendor_id', 'vendor_amount', 'vendor_payout_reference', 'vendor_paid_at']);
        });
    }
};
