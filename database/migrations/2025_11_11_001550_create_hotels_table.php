<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StatutHotel;
use App\Enums\Device;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('adresse');
            $table->string('mail');
            $table->string('telephone');
            $table->decimal('prix_par_nuit', 10, 2);
            $table->enum('device', [
                Device::FCFA->value,
                Device::EURO->value, 
                Device::DOLLARS->value
            ]);
            $table->string('photo')->nullable();
            $table->enum('statut', [
                StatutHotel::ACTIF->value,
                StatutHotel::INACTIF->value
            ])->default(StatutHotel::ACTIF->value);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotels');
    }
};