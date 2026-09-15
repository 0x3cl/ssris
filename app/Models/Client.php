<?php

namespace App\Models;

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['firstname', 'middlename', 'lastname', 'fullname', 'email', 'mobile_no', 'fax_no', 'age', 'gender', 'address', 'region', 'province', 'municipality', 'tel_no', 'type_client', 'company', 'school_name', 'business_role', 'enterprise_size', 'market', 'products', 'source', 'service', 'description'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'type_client' => ClientType::class,
            'business_role' => ClientBusinessRole::class,
            'enterprise_size' => ClientEnterpriseSize::class,
            'market' => ClientMarket::class,
            'source' => ClientSource::class,
            'service' => ClientService::class,
        ];
    }
}
