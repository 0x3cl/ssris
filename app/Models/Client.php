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
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['firstname', 'middlename', 'lastname', 'fullname', 'email', 'mobile_no', 'fax_no', 'age', 'gender', 'address', 'region', 'province', 'municipality', 'tel_no', 'type_client', 'company', 'school_name', 'business_role', 'enterprise_size', 'market', 'products', 'source', 'service', 'description'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    use SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Client $client): void {
            $client->is_deleted = $client->trashed();
        });
    }

    /** Archive both deletion markers in the same database update. */
    protected function runSoftDelete(): void
    {
        $time = $this->freshTimestamp();
        $columns = [
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
            'is_deleted' => true,
        ];
        $this->{$this->getDeletedAtColumn()} = $time;
        $this->is_deleted = true;

        if ($this->usesTimestamps() && $this->getUpdatedAtColumn() !== null) {
            $this->{$this->getUpdatedAtColumn()} = $time;
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $this->setKeysForSaveQuery($this->newModelQuery())->update($columns);
        $this->syncOriginalAttributes(array_keys($columns));
        $this->fireModelEvent('trashed', false);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
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
