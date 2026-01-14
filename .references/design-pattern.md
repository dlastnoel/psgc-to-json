# Tech Stack & Design Patterns

## Tech Stack Overview

**Laravel 12** backend with **Vue 3 Composition API** (TypeScript) frontend via Inertia.js, using **Pest** for testing and **Tailwind CSS 4** for styling.

---

## Detected Patterns (From Existing Codebase)

### Backend Architecture

**Controllers:**
- Singular naming: `ProfileController`, `PasswordController`
- Feature-based organization in subfolders (`Settings/`)
- Thin controllers with typed returns: `Response`, `RedirectResponse`
- Inertia rendering: `Inertia::render('settings/Profile')`

**Models:**
- Standard Laravel models with traits (`HasFactory`, `Notifiable`)
- PHP 8.2+ array syntax for casts: `protected function casts(): array`
- Singular naming: `User`

**Requests/Validation:**
- FormRequest classes organized in subfolders matching controllers
- Typed `rules()` method: `public function rules(): array`

**Architectural Patterns:**
- **Action pattern** via Laravel Fortify (`CreateNewUser`, `ResetUserPassword`)
- **Traits** for reusable validation (`PasswordValidationRules`)
- **Middleware** for shared data (`HandleInertiaRequests`)

### Frontend Architecture

**Vue Component Structure:**
- Order: `<script setup lang="ts">` → `<template>` (no explicit `<style>` - uses Tailwind)
- Composition API exclusively with `<script setup>`
- Props interfaces defined before usage

**TypeScript Usage:**
- Full TypeScript adoption, strict mode enabled
- Type-safe component props: `interface Props { ... }`
- Module augmentation in `globals.d.ts` for Vue/Inertia

**Composables:**
- Pattern: utility functions + reactive state exported
- Multiple exports per file: `updateTheme`, `initializeTheme`, `useAppearance()`
- `use` prefix naming: `useAppearance`, `useInitials`, `useTwoFactorAuth`

**Component Organization:**
- **Atomic UI components** with barrel exports (`index.ts`)
- **Shadcn/ui pattern**: Variants via CVA
- **Feature components**: AppHeader, DeleteUser, UserInfo

### File Organization

**Routes:**
- Modular: `web.php` includes `settings.php`
- Route grouping by feature
- Closure-based for simple routes, controller-based for complex

**Pages:**
- Feature-based subfolders: `auth/`, `settings/`
- Matches Inertia render paths: `settings/Profile.vue` → `Inertia::render('settings/Profile')`

**Types:**
- Centralized in `index.d.ts`: `User`, `BreadcrumbItem`, `AppPageProps`
- Module augmentation in `globals.d.ts`

**Layouts:**
- Hierarchical: `AppLayout.vue` wraps `AppSidebarLayout.vue`
- Feature subfolders: `settings/`, `auth/`, `app/`

### Naming Conventions

- **Controllers**: Singular (`ProfileController`, `PasswordController`)
- **Models**: Singular (`User`)
- **Components**: PascalCase (`AppHeader`, `DeleteUser`)
- **Composables**: camelCase with `use` prefix (`useAppearance`, `useInitials`)
- **Files**: PascalCase for Vue components, camelCase for TypeScript files
- **UI components**: Lowercase folders (`button`, `card`, `dialog`)

---

## Agreed-Upon Design Patterns

### Backend Patterns

**1. No Repository Layer**
- Access Eloquent models directly in controllers
- Eloquent is sufficient for PSGC CRUD operations
- Matches existing codebase pattern

**2. Action Pattern for Complex Logic**
- Place actions in `app/Actions/Psgc/`
- Single responsibility per action
- Examples:
  - `ImportPsgcData` - Handles entire import process
  - `NormalizePsgcRow` - Normalizes Excel row data
  - `SyncPsgcLatest` - Orchestrates sync process
  - `RollbackPsgcVersion` - Handles version rollback

**3. Controller Organization (Cruddy-by-Design)**
- Singular naming: `RegionController`, `ProvinceController`
- 7 standard REST actions only: `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`
- Feature-based folders: `app/Http/Controllers/Psgc/`
- For sync/rollback: Use Artisan commands, not controller actions

**4. Model Relationships & Versioning**
- Standard Eloquent relationships with return types
- **Full data per version approach**: Each PSGC version has complete copies of all data
- Tables have `psgc_version_id` foreign key
- Add scopes for version queries: `current()`, `version($id)`

**5. Eloquent API Resources**
- Use `php artisan make:resource` to create resources
- Singular naming: `RegionResource`, `ProvinceResource`
- Transform models with proper type hints
- Support version filtering in resources

**6. Artisan Commands**
- Place in `app/Console/Commands/Psgc/`
- Commands:
  - `psgc:sync` - Download, parse, import latest PSGC
  - `psgc:rollback {version_id}` - Set previous version as current
  - `psgc:list` - List all PSGC versions

---

### Frontend Patterns (Optional - Future)

**1. Component Structure**
- Follow existing pattern: `<script setup lang="ts">` → `<template>`
- No explicit `<style>` section (uses Tailwind CSS 4)

**2. Type Safety**
- Create TypeScript interfaces matching Laravel models
- Place in `resources/js/types/psgc.ts`
- Example: `interface Region { id: number; name: string; psgcCode: string; }`

**3. Composables for API Calls**
- Use `use` prefix pattern
- Example: `usePsgcApi()` for API interactions
- Export multiple functions per file as needed

---

## Code Organization Guidelines

### Directory Structure

```
app/
├── Actions/
│   └── Psgc/
│       ├── ImportPsgcData.php
│       ├── NormalizePsgcRow.php
│       ├── SyncPsgcLatest.php
│       └── RollbackPsgcVersion.php
├── Console/
│   └── Commands/
│       └── Psgc/
│           ├── SyncCommand.php
│           ├── RollbackCommand.php
│           └── ListCommand.php
├── Http/
│   ├── Controllers/
│   │   └── Psgc/
│   │       ├── RegionController.php
│   │       ├── ProvinceController.php
│   │       ├── MunicipalityController.php
│   │       ├── BarangayController.php
│   │       └── PsgcVersionController.php
│   └── Resources/
│       ├── RegionResource.php
│       ├── ProvinceResource.php
│       ├── MunicipalityResource.php
│       └── BarangayResource.php
└── Models/
    ├── Region.php
    ├── Province.php
    ├── Municipality.php
    ├── Barangay.php
    └── PsgcVersion.php

database/
└── migrations/
    ├── create_psgc_versions_table.php
    ├── create_regions_table.php
    ├── create_provinces_table.php
    ├── create_municipalities_table.php
    └── create_barangays_table.php

resources/js/
├── types/
│   └── psgc.ts (future)
└── composables/
    └── usePsgcApi.ts (future)

routes/
└── api.php

tests/
├── Feature/
│   ├── Psgc/
│   │   ├── RegionApiTest.php
│   │   ├── SyncCommandTest.php
│   │   └── RollbackCommandTest.php
│   └── ...
└── Unit/
    └── Psgc/
        ├── NormalizePsgcRowTest.php
        └── ImportPsgcDataTest.php
```

---

## Naming Conventions

### Backend
- **Controllers**: Singular (`RegionController`)
- **Models**: Singular (`Region`, `Province`)
- **API Resources**: Singular (`RegionResource`)
- **Actions**: Verb-based (`ImportPsgcData`, `SyncPsgcLatest`)
- **Commands**: Verb-based (`SyncCommand`, `RollbackCommand`)
- **Migrations**: Snake case with timestamps (`create_regions_table.php`)

### Database
- **Tables**: Plural snake case (`psgc_versions`, `regions`)
- **Columns**: Snake case (`psgc_version_id`, `is_district`)
- **Foreign keys**: `{table}_id` (`region_id`, `province_id`)

### Frontend (Future)
- **Components**: PascalCase (`RegionSelector`, `BarangayPicker`)
- **Composables**: camelCase with `use` prefix (`usePsgcApi`, `useRegionSelect`)
- **Types/Interfaces**: PascalCase (`Region`, `Province`)
- **Files**: PascalCase for components, camelCase for utilities

---

## Example Code Structure

### Model with Versioning

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }

    public function psgcVersion(): BelongsTo
    {
        return $this->belongsTo(PsgcVersion::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('psgc_version_id', PsgcVersion::current()->id);
    }

    public function scopeVersion($query, int $versionId)
    {
        return $query->where('psgc_version_id', $versionId);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

### Controller with API Resources

```php
<?php

namespace App\Http\Controllers\Psgc;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegionResource;
use App\Models\Region;
use Illuminate\Http\JsonResponse;

class RegionController extends Controller
{
    public function index(): JsonResponse
    {
        $regions = Region::current()->with('provinces')->get();

        return RegionResource::collection($regions)->response();
    }

    public function show(Region $region): JsonResponse
    {
        $region->load('provinces.municipalities.barangays');

        return RegionResource::make($region)->response();
    }
}
```

### Action Pattern

```php
<?php

namespace App\Actions\Psgc;

use App\Models\PsgcVersion;

class SyncPsgcLatest
{
    public function execute(): PsgcVersion
    {
        // Download latest PSGC
        $downloadPath = (new DownloadPsgc())->execute();

        // Parse and normalize data
        $data = (new NormalizePsgcData())->execute($downloadPath);

        // Import to database
        return (new ImportPsgcData())->execute($data);
    }
}
```

### API Resource

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'psgcCode' => $this->psgc_code,
            'regionCode' => $this->region_code,
            'provinces' => ProvinceResource::collection($this->whenLoaded('provinces')),
        ];
    }
}
```

---

## Testing Strategy

### Feature Tests
- Test API endpoints return correct data
- Test sync command successfully imports data
- Test rollback command switches versions
- Test version filtering in queries

### Unit Tests
- Test normalization logic (Manila districts → municipalities)
- Test Excel parsing
- Test model scopes (`current()`, `version()`)
- Test action classes

### Test Organization
- Feature tests: `tests/Feature/Psgc/`
- Unit tests: `tests/Unit/Psgc/`
- Follow Pest testing patterns (it(), expect()->toBe(), etc.)

---

## Rationale for Pattern Choices

**Why no repository layer?**
- Eloquent provides sufficient abstraction
- Matches existing codebase patterns
- Reduces unnecessary boilerplate
- PSGC operations are straightforward CRUD

**Why Action pattern?**
- Encapsulates complex domain logic
- Single responsibility principle
- Easy to test in isolation
- Matches Fortify Action pattern already in use

**Why full data per version?**
- Simpler to implement and maintain
- Fast queries for any version
- Easy to compare/diff versions
- Storage is negligible (~2-5 MB per version)
- Clear semantic meaning (each version is complete snapshot)

**Why Eloquent API Resources?**
- Built-in Laravel feature
- Consistent transformation layer
- Type-safe output
- Supports version filtering
- Can expand to additional formats later

**Why Cruddy-by-Design?**
- Standard REST actions reduce controller complexity
- Forces discovery of new resources
- Follows Laravel conventions
- Prevents bloated controllers

---

## Future Considerations

**If frontend is built later:**
- Add Vue composables for API calls
- Create reusable PSGC components (RegionSelector, etc.)
- Add TypeScript interfaces matching models
- Use Inertia for admin dashboard (optional)

**If API expands:**
- Add pagination support to resources
- Add filtering/sorting to queries
- Consider GraphQL for complex queries
- Add rate limiting (optional)

**If performance needs optimization:**
- Add caching layer (Redis)
- Add database indexes on frequently queried columns
- Consider materialized views for complex queries
