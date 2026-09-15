<?php

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('middlename')
                ->nullable();
            $table->string('lastname');
            $table->string('fullname');
            $table->string('email')->unique();
            $table->string('mobile_no');
            $table->string('fax_no')
                ->nullable();
            $table->integer('age');
            $table->string('gender');
            $table->string('address');
            $table->string('region');
            $table->string('province');
            $table->string('municipality');
            $table->string('tel_no');

            $table->enum('type_client', array_column(ClientType::cases(), 'value'));

            // if private and government
            $table->string('company')
                ->nullable();

            // if academe
            $table->string('school_name')
                ->nullable();

            // if private
            $table->enum('business_role', array_column(ClientBusinessRole::cases(), 'value'))
                ->nullable();
            $table->enum('enterprise_size', array_column(ClientEnterpriseSize::cases(), 'value'))
                ->nullable();
            $table->enum('market', array_column(ClientMarket::cases(), 'value'))
                ->nullable();
            $table->tinyText('products')
                ->nullable();

            $table->enum('source', array_column(ClientSource::cases(), 'value'));
            $table->enum('service', array_column(ClientService::cases(), 'value'));
            $table->longText('description');

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
