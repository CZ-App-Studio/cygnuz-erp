<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Main menu items table
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->string('module')->nullable(); // Module that owns this menu
            $table->string('addon')->nullable(); // For addon checking
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('priority')->default(99);
            $table->string('menu_type')->default('vertical'); // vertical, horizontal
            $table->string('header_group')->nullable(); // Menu header it belongs to
            $table->json('metadata')->nullable(); // Additional data like badges, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['module', 'is_active']);
            $table->index(['menu_type', 'priority']);
            $table->foreign('parent_id')->references('id')->on('menus')->onDelete('cascade');
        });

        // Menu permissions table
        Schema::create('menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->string('permission'); // Permission name required
            $table->string('role')->nullable(); // Role required (alternative to permission)
            $table->timestamps();
            
            $table->unique(['menu_id', 'permission']);
            $table->index('permission');
            $table->index('role');
        });

        // Menu groups/categories
        Schema::create('menu_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->integer('priority')->default(99);
            $table->string('menu_type')->default('vertical');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['menu_type', 'priority']);
        });

        // User menu favorites
        Schema::create('user_menu_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'menu_id']);
            $table->index(['user_id', 'display_order']);
        });

        // User recently accessed menus
        Schema::create('user_recent_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->integer('access_count')->default(1);
            $table->timestamp('last_accessed_at');
            $table->timestamps();
            
            $table->unique(['user_id', 'menu_id']);
            $table->index(['user_id', 'last_accessed_at']);
            $table->index(['user_id', 'access_count']);
        });

        // Menu profiles for different work modes
        Schema::create('menu_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->json('menu_configuration'); // Stores complete menu state
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'is_active']);
        });

        // Enhanced user menu preferences (update existing table)
        if (!Schema::hasColumn('user_menu_preferences', 'is_collapsed')) {
            Schema::table('user_menu_preferences', function (Blueprint $table) {
                $table->boolean('is_collapsed')->default(false)->after('is_pinned');
                $table->boolean('is_hidden')->default(false)->after('is_collapsed');
                $table->json('custom_settings')->nullable()->after('display_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added columns from existing table
        if (Schema::hasColumn('user_menu_preferences', 'is_collapsed')) {
            Schema::table('user_menu_preferences', function (Blueprint $table) {
                $table->dropColumn(['is_collapsed', 'is_hidden', 'custom_settings']);
            });
        }

        Schema::dropIfExists('menu_profiles');
        Schema::dropIfExists('user_recent_menus');
        Schema::dropIfExists('user_menu_favorites');
        Schema::dropIfExists('menu_groups');
        Schema::dropIfExists('menu_permissions');
        Schema::dropIfExists('menus');
    }
};