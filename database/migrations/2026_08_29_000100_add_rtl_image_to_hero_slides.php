<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A second photograph for the Arabic hero.
 *
 * The hero is composed with the model to one side and the copy in the open
 * half beside her. Reading direction flips which half that is, so Arabic needs
 * a plate with the model on the other side — mirroring the English one would
 * reverse the jacket's buttons and placket and show a garment that does not
 * exist.
 *
 * Until now that twin was found by filename convention (hero-1.jpg paired with
 * hero-1-rtl.jpg), which only the seeded brand plates follow. A slide uploaded
 * through the admin had no twin and fell back to its left-composed image, so
 * the Arabic hero put the model under the words.
 *
 * Nullable: a slide with one image keeps working and simply uses it for both
 * directions, which is what every existing row does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table): void {
            $table->string('image_path_rtl')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table): void {
            $table->dropColumn('image_path_rtl');
        });
    }
};
