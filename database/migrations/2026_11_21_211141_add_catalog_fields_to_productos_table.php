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
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'costo')) {
                $table->decimal('costo', 10, 2)->default(0)->after('precio');
            }
            if (! Schema::hasColumn('productos', 'descripcion')) {
                $table->string('descripcion', 1000)->nullable()->after('nombre');
            }
            if (! Schema::hasColumn('productos', 'imagen')) {
                $table->string('imagen')->nullable()->after('descripcion');
            }
            if (! Schema::hasColumn('productos', 'activo')) {
                $table->boolean('activo')->default(true)->after('stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            //
        });
    }
};
