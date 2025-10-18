<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('notas_fiscais', function (Blueprint $t) {
            $t->string('plugnotas_id')->nullable()->index();
            $t->string('plugnotas_status')->nullable()->index(); // CONCLUIDO, REJEITADO, etc.
            $t->string('pdf_url')->nullable();
            $t->string('xml_url')->nullable();
        });
    }
    public function down(): void {
        Schema::table('notas_fiscais', function (Blueprint $t) {
            $t->dropColumn(['plugnotas_id','plugnotas_status','pdf_url','xml_url']);
        });
    }
};
