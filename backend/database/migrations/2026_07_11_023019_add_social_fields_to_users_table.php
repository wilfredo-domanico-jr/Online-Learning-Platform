<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * This environment's `users` table was already hand-modified outside of
     * migrations at some point: `social_provider`/`social_id` columns exist,
     * but the plain unique constraint on `email` was replaced with a bad
     * composite unique index on (email, social_provider) — which allows
     * duplicate emails across providers. This migration reconciles that
     * drift back to the intended schema instead of assuming a clean slate.
     */
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('users'))->keyBy('name');

        if ($indexes->has('email_social_provider')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('email_social_provider');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'social_provider')) {
                $table->string('social_provider')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'social_id')) {
                $table->string('social_id')->nullable()->after('social_provider');
            }
        });

        $indexes = collect(Schema::getIndexes('users'))->keyBy('name');

        if (! $indexes->has('users_email_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        }

        if (! $indexes->has('users_social_provider_social_id_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['social_provider', 'social_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['social_provider', 'social_id']);
            $table->dropColumn(['social_provider', 'social_id']);
        });
    }
};
