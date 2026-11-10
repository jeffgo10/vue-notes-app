<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->string('notarized_hash', 66)->nullable()->after('attachment');
            $table->string('tx_hash', 66)->nullable()->after('notarized_hash');
            $table->string('wallet_address', 42)->nullable()->after('tx_hash');
            $table->timestamp('notarized_at')->nullable()->after('wallet_address');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['notarized_hash', 'tx_hash', 'wallet_address', 'notarized_at']);
        });
    }
};
