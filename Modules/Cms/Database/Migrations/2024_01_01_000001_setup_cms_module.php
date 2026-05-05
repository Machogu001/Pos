<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class SetupCmsModule extends Migration
{
    public function up()
    {
        // CMS pages
        if (! Schema::hasTable('cms_pages')) {
            Schema::create('cms_pages', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id')->index();
                $table->string('title');
                $table->string('slug')->index();
                $table->longText('content')->nullable();
                $table->string('status', 20)->default('draft'); // draft, published
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // CMS media
        if (! Schema::hasTable('cms_media')) {
            Schema::create('cms_media', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id')->index();
                $table->string('filename');
                $table->string('path');
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->unsignedInteger('uploaded_by')->nullable();
                $table->timestamps();
            });
        }

        // Register permissions
        $permissions = [
            'cms.view_page',
            'cms.create_page',
            'cms.edit_page',
            'cms.delete_page',
            'cms.manage_media',
        ];

        foreach ($permissions as $permission) {
            if (! Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('cms_media');
        Schema::dropIfExists('cms_pages');
    }
}
