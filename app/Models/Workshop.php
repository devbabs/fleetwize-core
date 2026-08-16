<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $slug
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $address
 * @property int|null $country_id
 * @property int|null $state_id
 * @property int|null $city_id
 * @property string|null $postal_code
 * @property int|null $contact_person_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read City|null $city
 * @property-read Company|null $company
 * @property-read User|null $contactPerson
 * @property-read Country|null $country
 * @property-read Collection<int, VehicleDiagnosticReport> $diagnosticReports
 * @property-read int|null $diagnostic_reports_count
 * @property-read State|null $state
 * @property-read Collection<int, Vehicle> $vehicles
 * @property-read int|null $vehicles_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereContactPersonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workshop whereWebsite($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'slug', 'phone', 'website', 'address', 'postal_code'])]
class Workshop extends Model
{
    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function contactPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contact_person_id');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsToMany<Vehicle, $this>
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_workshops');
    }

    /**
     * @return HasMany<VehicleDiagnosticReport, $this>
     */
    public function diagnosticReports(): HasMany
    {
        return $this->hasMany(VehicleDiagnosticReport::class);
    }
}
