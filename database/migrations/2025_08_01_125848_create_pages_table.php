<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');                    // Titre de la page
            $table->string('slug')->unique();          // URL slug (about, faq, etc.)
            $table->text('excerpt')->nullable();       // Description courte
            $table->longText('content');              // Contenu principal (HTML)
            $table->string('meta_title')->nullable(); // SEO title
            $table->text('meta_description')->nullable(); // SEO description
            $table->string('template')->default('default'); // Template à utiliser
            $table->boolean('is_active')->default(true); // Page active/inactive
            $table->boolean('show_in_menu')->default(false); // Afficher dans le menu
            $table->integer('menu_order')->default(0); // Ordre dans le menu
            $table->json('custom_fields')->nullable(); // Champs personnalisés
            $table->timestamps();
            
            $table->index(['slug', 'is_active']);
            $table->index('show_in_menu');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pages');
    }
};