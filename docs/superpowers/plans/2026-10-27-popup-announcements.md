# Popup Announcements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let admins choose for each announcement whether it shows as a dismissible banner (default) or as a blocking popup. The popup closes only through "I've read this", and once a user confirms it they don't see it again.

**Architecture:** A `display` string column plus the `AnnouncementDisplay` enum on `Announcement`. `Announcements::register()` keeps the banner hooks for banners and for the `SimplePage` fallback. For popups it registers one `BODY_END` hook per `Page` scope. That hook mounts a single `AnnouncementPopups` Livewire component, which queues the pending popups for that page. Confirmations are ordinary `AnnouncementDismissal` rows, so **Reset dismissals** works without changes.

**Tech Stack:** PHP 8.3, Laravel (Testbench 9/10), Filament 5, Livewire, Pest 4 (+ pest-plugin-livewire), Tailwind 4 (package CSS build).

**Spec:** `docs/superpowers/specs/2026-10-27-popup-announcements-design.md` (read it with this plan; the section numbers below refer to it).

## Global Constraints

- Existing rows and announcements created without `display` must stay `banner` and behave exactly as they do today.
- "Login-type" page = any subclass of `Filament\Pages\SimplePage`. Check with `is_a($scope, SimplePage::class, true)` / `is_subclass_of` on strings. Never instantiate a class to test it.
- A popup has no X, Esc does nothing, and clicking the overlay does nothing. The only control is the "I've read this" button.
- Guests never get a popup. On a page where an announcement shows as a popup, it never also shows as a banner.
- Popup order: `created_at` ascending, then `id`.
- Every user-facing string in this change goes through `resources/lang/en/backstage-announcements.php` via the package translation namespace (Task 2 pins the namespace). Exact copy:
  - `Display as` · `Banner` · `Popup with confirmation required`
  - `A coloured bar at the top of the page that users can dismiss.`
  - `A modal that users must close by clicking “I've read this”.` (curly quotes around the button text, straight apostrophe in "I've")
  - `Select at least one page other than login or registration.`
  - `Login and registration pages always show a banner.`
  - `I've read this` · `:current of :total`
- Do not build the "NEW" badge from the mockup. Do not change the Reset dismissals action.
- `docs/` is in `.gitignore`. Anything under `docs/` must be added with `git add -f`.
- Before every commit, run `vendor/bin/pint` on the files you touched. Task 6 ends with `composer analyse` passing.

## Review Focus

1. **Markup in title or content** (`<script>`, `<b>`): the popup has to show it as escaped plain text and keep the newlines. Test in Task 3.
2. **A queued popup that was soft-deleted** after the page was rendered: it has to be skipped without an error, and the next popup shown. Test in Task 3.
3. **A stale scope class** (a page class that no longer exists stored in `scopes`): registration must not fatal, and the popup must still show on the valid pages. Test in Task 4.
4. **Legacy rows** (inserted without `display`): they must read as `banner` and open in the Edit form as Banner. Saving them must not change their behaviour. Test in Task 5.
5. **A guest hitting a page hook**, or a user who has confirmed everything: the `BODY_END` hook must render an empty string and mount no component. Test in Task 4.

---

## File Structure

| File | Responsibility |
|---|---|
| `database/migrations/add_display_to_announcements_table.php.stub` (new) | `display` column |
| `src/Enums/AnnouncementDisplay.php` (new) | enum + labels/descriptions |
| `src/Models/Announcement.php` | cast, default, `scopesSupportPopup`, `isPopup`, `pendingPopupsFor`, save guard, `HasFactory` |
| `database/factories/AnnouncementFactory.php` (replaces `ModelFactory.php`) | factory with `popup()` / `scopes()` states |
| `src/Livewire/AnnouncementPopups.php` + `resources/views/livewire/announcement-popups.blade.php` (new) | popup queue component |
| `src/Announcements.php` | hook registration (banner + popup grouping) |
| `src/AnnouncementsServiceProvider.php` | register migration + Livewire component |
| `src/Resources/Announcements/Schemas/AnnouncementForm.php` | "Display as" field, live scopes |
| `src/Resources/Announcements/Schemas/AnnouncementInfolist.php`, `Tables/AnnouncementsTable.php` | display badge |
| `resources/lang/en/backstage-announcements.php` | strings |
| `resources/dist/backstage-announcements.css` | rebuilt CSS |
| `tests/TestCase.php`, `tests/Pest.php`, `tests/Fixtures/User.php`, `tests/Fixtures/TestPanelProvider.php`, `tests/database/migrations/*` (new) | test harness |
| `README.md` | docs |

---

### Task 1: Test harness, factory and banner regression tests

The repo has no real tests, no factory and no panel. `vendor/` is not installed. The existing `defineDatabaseMigrations()` loads `database/migrations`, but every file in it is a `.stub`, so in practice it loads nothing.

**Files:**
- Create: `tests/Fixtures/User.php`, `tests/Fixtures/TestPanelProvider.php`
- Create: `tests/database/migrations/0001_01_01_000000_create_users_table.php`, `tests/database/migrations/0001_01_01_000001_create_package_tables.php`
- Create: `database/factories/AnnouncementFactory.php`; Delete: `database/factories/ModelFactory.php`, `tests/ExampleTest.php`
- Modify: `tests/TestCase.php`, `tests/Pest.php`, `src/Models/Announcement.php` (add `HasFactory` only)
- Test: `tests/HarnessTest.php`, `tests/BannerTest.php`

**Interfaces:**
- Produces:
  - `Backstage\Announcements\Tests\Fixtures\User` (Eloquent `Authenticatable`, implements `Filament\Models\Contracts\FilamentUser`, `canAccessPanel()` returns `true`; fillable `name`, `email`, `password`).
  - `TestPanelProvider`: panel id `admin`, path `admin`, `->default()->login()->registration()->pages([\Filament\Pages\Dashboard::class])->plugin(AnnouncementsPlugin::make())`.
  - Pest helper `function login(): User` in `tests/Pest.php`. It creates a user, calls `test()->actingAs($user)`, and returns the user.
  - `AnnouncementFactory` (namespace `Backstage\Announcements\Database\Factories`). Defaults: `title` = sentence, `content` = sentence, `scopes` = `[\Filament\Pages\Dashboard::class]`, `color` = `'info'`, `start_date`/`end_date` = `null`. State `scopes(array $scopes): static`. (Task 2 adds the `display` default and the `popup()` state.)
  - `Announcement::newFactory()` returns `AnnouncementFactory::new()` (plus the `HasFactory` trait).

- [ ] **Step 1: Install dependencies**

Run: `composer install && npm ci`
Expected: both finish with exit code 0. Note the installed class names for the auth pages: `ls vendor/filament/filament/src/Auth/Pages` should list `Login.php` and `Register.php` (`Filament\Auth\Pages\Login` / `Register`). The tests below use those names. If the location differs, use the real names everywhere.

- [ ] **Step 2: Build the harness**

- The users migration has `id`, `name`, `email` (unique), `password`, `rememberToken`, `timestamps`.
- `create_package_tables` `up()` requires and runs each package stub in order: `(require __DIR__.'/../../../database/migrations/'.$name.'.php.stub')->up()`, for `create_backstage_announcements_table`, `create_announcement_dismissals_table`, `add_start_date_end_date_to_announcements_table`. Task 2 adds its stub to this list.
- In `TestCase`, `defineDatabaseMigrations()` should load `__DIR__.'/database/migrations'`. Add `TestPanelProvider::class` to the providers, after `AnnouncementsServiceProvider`. (Providers are currently `sort()`ed, so append it after the sort.) `getEnvironmentSetUp` sets `auth.providers.users.model` to `Fixtures\User::class` and `app.key` to a 32-char base64 key. `setUp()` calls `Filament::setCurrentPanel(Filament::getPanel('admin'))`.

- [ ] **Step 3: Write the harness and banner characterization tests**

`tests/HarnessTest.php`:
```php
it('migrates the package tables and builds announcements', function () {
    $a = Announcement::factory()->create();
    expect($a->scopes)->toBe([Dashboard::class])
        ->and(Schema::hasTable('announcement_dismissals'))->toBeTrue();
});

it('registers the announcement resource on the test panel', function () {
    expect(Filament::getPanel('admin')->getResources())->toContain(AnnouncementResource::class);
});
```

`tests/BannerTest.php` (existing behaviour, should pass straight away):
```php
it('renders a banner with title, content and dismiss button for a logged-in user', function () {
    login();
    $a = Announcement::factory()->create(['title' => 'Maintenance', 'content' => 'Tonight']);
    livewire(LivewireAnnouncement::class, ['announcement' => $a, 'scope' => Dashboard::class])
        ->assertSee('Maintenance')->assertSee('Tonight')
        ->assertSeeHtml('wire:click="markAsRead"');
});

it('hides the dismiss button for guests', function () {
    $a = Announcement::factory()->create();
    livewire(LivewireAnnouncement::class, ['announcement' => $a, 'scope' => Login::class])
        ->assertSee($a->title)->assertDontSeeHtml('wire:click="markAsRead"');
});

it('stops rendering a banner once dismissed', function () {
    $user = login();
    $a = Announcement::factory()->create();
    livewire(LivewireAnnouncement::class, ['announcement' => $a, 'scope' => Dashboard::class])
        ->call('markAsRead')
        ->assertDontSee($a->title);
    expect($a->dismissals()->where('user_id', $user->id)->count())->toBe(1);
});
```

- [ ] **Step 4: Run the suite**

Run: `vendor/bin/pest`
Expected: all tests PASS (Harness, Banner, DebugTest). If a banner test fails, the harness is wrong, not the banner code. Fix the harness.

- [ ] **Step 5: Commit**

```bash
git add tests database/factories src/Models/Announcement.php
git commit -m "test: add Testbench panel, user fixture, factory and banner regression tests"
```

---

### Task 2: `display` column, enum, translations and model rules

**Files:**
- Create: `database/migrations/add_display_to_announcements_table.php.stub`, `src/Enums/AnnouncementDisplay.php`
- Modify: `src/AnnouncementsServiceProvider.php` (`getMigrations()`: append `'add_display_to_announcements_table'`), `tests/database/migrations/0001_01_01_000001_create_package_tables.php` (append the same stub), `src/Models/Announcement.php`, `database/factories/AnnouncementFactory.php`, `resources/lang/en/backstage-announcements.php`
- Test: `tests/AnnouncementModelTest.php`

**Interfaces:**
- Produces:
  - `enum AnnouncementDisplay: string implements HasLabel, HasDescription { case Banner = 'banner'; case Popup = 'popup'; }` with `getLabel(): string`, `getDescription(): string`, and `public static function isPopupState(mixed $state): bool` (true for `Popup` or the string `'popup'`; form state may be either).
  - `Announcement::scopesSupportPopup(array $scopes): bool` (static)
  - `Announcement::isPopup(): bool`
  - `Announcement::pendingPopupsFor(array $ids, ?int $userId): Collection<Announcement>`. It runs one query: `whereKey($ids)` plus `whereDoesntHave('dismissals', user_id = $userId)`, so trashed rows are excluded. The result is ordered by the position in `$ids`. It returns an empty collection when `$userId` is null.
  - Factory: default `'display' => AnnouncementDisplay::Banner`; state `popup(): static`.
  - Translation keys (file `backstage-announcements.php`), under namespace `backstage/announcements::backstage-announcements`:
    `display.label`, `display.banner.label`, `display.banner.description`, `display.popup.label`, `display.popup.description`, `hints.popup_unavailable`, `hints.popup_fallback`, `popup.confirm`, `popup.counter`. The values are the exact copy listed under Global Constraints.

- [ ] **Step 1: Write the failing tests** (`tests/AnnouncementModelTest.php`)

```php
it('resolves the package translation namespace', function () {
    expect(__('backstage/announcements::backstage-announcements.popup.confirm'))->toBe("I've read this")
        ->and(__('backstage/announcements::backstage-announcements.popup.counter', ['current' => 1, 'total' => 2]))->toBe('1 of 2');
});

it('defaults display to banner and casts to the enum', function () {
    expect((new Announcement)->display)->toBe(AnnouncementDisplay::Banner);
    $a = Announcement::factory()->create();
    expect($a->fresh()->display)->toBe(AnnouncementDisplay::Banner);
});

it('reads legacy rows without display as banner', function () {
    $id = DB::table('announcements')->insertGetId(['title' => 'Old', 'content' => 'x', 'scopes' => json_encode([Dashboard::class]), 'color' => 'info', 'created_at' => now(), 'updated_at' => now()]);
    expect(Announcement::find($id)->display)->toBe(AnnouncementDisplay::Banner);
});

it('decides popup support from the scopes', function () {
    expect(Announcement::scopesSupportPopup([Dashboard::class, Login::class]))->toBeTrue()
        ->and(Announcement::scopesSupportPopup([Login::class, Register::class]))->toBeFalse()
        ->and(Announcement::scopesSupportPopup(['*']))->toBeFalse()
        ->and(Announcement::scopesSupportPopup(['App\\Does\\Not\\Exist']))->toBeFalse()
        ->and(Announcement::scopesSupportPopup([]))->toBeFalse();
});

it('downgrades a popup with only login-type scopes to banner on save', function () {
    $a = Announcement::factory()->popup()->scopes([Login::class])->create();
    expect($a->fresh()->display)->toBe(AnnouncementDisplay::Banner);
});

it('downgrades a popup with empty scopes (saved as *) to banner', function () {
    $a = Announcement::factory()->popup()->scopes([])->create();
    expect($a->fresh()->scopes)->toBe(['*'])->and($a->fresh()->display)->toBe(AnnouncementDisplay::Banner);
});

it('keeps a popup with a mix of scopes', function () {
    $a = Announcement::factory()->popup()->scopes([Dashboard::class, Login::class])->create();
    expect($a->fresh()->isPopup())->toBeTrue();
});

it('returns pending popups in the given order, skipping dismissed and trashed ones', function () {
    $user = login();
    [$a, $b, $c, $d] = Announcement::factory()->popup()->count(4)->create()->all();
    $b->dismissals()->create(['user_id' => $user->id]);
    $d->delete();
    expect(Announcement::pendingPopupsFor([$c->id, $a->id, $b->id, $d->id], $user->id)->pluck('id')->all())->toBe([$c->id, $a->id])
        ->and(Announcement::pendingPopupsFor([$a->id], null))->toBeEmpty();
});

it('labels the enum cases', function () {
    expect(AnnouncementDisplay::Popup->getLabel())->toBe('Popup with confirmation required')
        ->and(AnnouncementDisplay::Banner->getDescription())->toBe('A coloured bar at the top of the page that users can dismiss.');
});
```

- [ ] **Step 2: Run them to verify they fail**

Run: `vendor/bin/pest tests/AnnouncementModelTest.php`
Expected: FAIL (`AnnouncementDisplay` not found / missing translation).

- [ ] **Step 3: Implement**

- Migration: `$table->string('display')->default('banner')->after('color')`. `down()` drops it.
- Model: add `display` to `$fillable`, `'display' => AnnouncementDisplay::class` to `$casts`, and `protected $attributes = ['display' => 'banner']`. In the existing `saving` closure, *after* the `['*']` fallback: `if ($announcement->display === AnnouncementDisplay::Popup && ! static::scopesSupportPopup($announcement->scopes)) $announcement->display = AnnouncementDisplay::Banner;`. `scopesSupportPopup` returns true when some scope satisfies `class_exists($s) && is_a($s, Page::class, true) && ! is_a($s, SimplePage::class, true)`.
- If the namespace test fails, check `Package::shortName()` in `vendor/spatie/laravel-package-tools/src/Package.php` and use the real namespace in every key in this plan.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest`
Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add database src/Enums src/Models src/AnnouncementsServiceProvider.php resources/lang tests
git commit -m "feat: add display column and AnnouncementDisplay enum with popup save guard"
```

---

### Task 3: `AnnouncementPopups` Livewire component

**Files:**
- Create: `src/Livewire/AnnouncementPopups.php`, `resources/views/livewire/announcement-popups.blade.php`
- Modify: `src/AnnouncementsServiceProvider.php` (register `Livewire::component('backstage.announcements.livewire.announcement-popups', AnnouncementPopups::class)`), `resources/dist/backstage-announcements.css` (rebuilt)
- Test: `tests/AnnouncementPopupsTest.php`

**Interfaces:**
- Consumes: `Announcement::pendingPopupsFor()`, translation keys `popup.confirm` and `popup.counter` (Task 2).
- Produces: `AnnouncementPopups` with `#[Locked] public array $announcementIds`, `#[Locked] public int $total`, `mount(array $announcementIds): void`, and `confirm(int $id): void`. `$total` = the number of pending popups at mount time, so a popup the user confirmed earlier is not counted. Counter `current = total - pending + 1`, shown only when `total > 1`. With nothing pending it renders `<div></div>`.

- [ ] **Step 1: Write the failing tests**

```php
it('renders the first pending popup as a dialog with a single confirm button', function () {
    login();
    $a = Announcement::factory()->popup()->create(['title' => 'New terms', 'content' => "Line one\nLine two"]);
    $html = livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->assertSee('New terms')->assertSee("I've read this")
        ->assertSeeHtml('role="dialog"')->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('whitespace-pre-line')->assertSeeHtml('x-trap.noscroll')
        ->assertDontSee('1 of 1')
        ->html();
    expect(substr_count($html, '<button'))->toBe(1);
});

it('escapes markup in title and content', function () {
    login();
    $a = Announcement::factory()->popup()->create(['title' => '<b>Bold</b>', 'content' => '<script>alert(1)</script>']);
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->assertDontSeeHtml('<script>alert(1)</script>')->assertDontSeeHtml('<b>Bold</b>')
        ->assertSee('<b>Bold</b>');
});

it('confirm creates exactly one dismissal even when called twice', function () {
    $user = login();
    $a = Announcement::factory()->popup()->create();
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->call('confirm', $a->id)->call('confirm', $a->id)
        ->assertDontSee($a->title);
    expect($a->dismissals()->where('user_id', $user->id)->count())->toBe(1);
});

it('walks through several popups with a counter', function () {
    login();
    $first = Announcement::factory()->popup()->create(['title' => 'First']);
    $second = Announcement::factory()->popup()->create(['title' => 'Second']);
    livewire(AnnouncementPopups::class, ['announcementIds' => [$first->id, $second->id]])
        ->assertSee('First')->assertDontSee('Second')->assertSee('1 of 2')
        ->call('confirm', $first->id)
        ->assertSee('Second')->assertSee('2 of 2')
        ->call('confirm', $second->id)
        ->assertDontSee('Second')->assertDontSeeHtml('role="dialog"');
});

it('ignores ids that are not in its list', function () {
    login();
    $a = Announcement::factory()->popup()->create();
    $other = Announcement::factory()->popup()->create();
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->call('confirm', $other->id)->assertSee($a->title);
    expect(AnnouncementDismissal::count())->toBe(0);
});

it('skips a popup that was soft-deleted after mounting', function () {
    login();
    $a = Announcement::factory()->popup()->create(['title' => 'Gone']);
    $b = Announcement::factory()->popup()->create(['title' => 'Stays']);
    $component = livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id, $b->id]]);
    $a->delete();
    $component->call('$refresh')->assertDontSee('Gone')->assertSee('Stays');
});

it('renders nothing and confirms nothing for guests', function () {
    $a = Announcement::factory()->popup()->create();
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->assertDontSeeHtml('role="dialog"')->call('confirm', $a->id);
    expect(AnnouncementDismissal::count())->toBe(0);
});
```

- [ ] **Step 2: Run them to verify they fail**

Run: `vendor/bin/pest tests/AnnouncementPopupsTest.php`
Expected: FAIL (class `AnnouncementPopups` not found).

- [ ] **Step 3: Implement the component and view**

- `confirm` returns early unless `in_array($id, $this->announcementIds, true)` and `Filament::auth()->check()`. It then calls `AnnouncementDismissal::firstOrCreate(['announcement_id' => $id, 'user_id' => Filament::auth()->id()])`.
- Render: compute the pending list once (`pendingPopupsFor($this->announcementIds, Filament::auth()->id())`). Return `'<div></div>'` when it is empty. Otherwise pass `current` (the first pending popup), `position` and `total` to the view.
- View: follow spec §5.4 and the mockup `popup-users-must-confirm-with-i-ve-read-this.png`:
  - a fixed full-screen overlay `div` without click or Esc handlers;
  - a panel `role="dialog" aria-modal="true" aria-labelledby="announcement-popup-title-{{ id }}" aria-describedby="announcement-popup-content-{{ id }}"` with `x-data x-trap.noscroll="true"`, about `max-w-md`, rounded, white / `dark:bg-gray-900`, with a shadow;
  - the icon `x-filament::icon` `Heroicon::OutlinedExclamationCircle` in a circle tinted with the announcement's colour (`get_color_css_variables($color, shades: [50, 100, 400, 500, 600])` on the panel);
  - the title, then the content in `{{ }}` with `whitespace-pre-line`;
  - the counter when `total > 1`;
  - a full-width `<button type="button" wire:click="confirm({{ id }})" wire:loading.attr="disabled" x-init="$nextTick(() => $el.focus())">` with the background `var(--color-600)` (like the banner) and the label `popup.confirm`.

  Key the root element on the current id (`wire:key`) so focus moves to the next popup's button.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest`
Expected: all PASS.

- [ ] **Step 5: Rebuild CSS and check the classes**

Run: `npm run build && grep -c "whitespace-pre-line" resources/dist/backstage-announcements.css`
Expected: build exits 0 and the count is ≥ 1. Spot-check one overlay class from your view the same way, for example the `bg-gray-950/…` overlay class.

- [ ] **Step 6: Commit**

```bash
git add src/Livewire/AnnouncementPopups.php resources/views/livewire/announcement-popups.blade.php src/AnnouncementsServiceProvider.php resources/dist tests/AnnouncementPopupsTest.php
git commit -m "feat: add AnnouncementPopups component with required confirmation"
```

---

### Task 4: Register popup and banner hooks

`register()` returns early in the console, so tests can't reach it. Split it into the guards and a testable method.

**Files:**
- Modify: `src/Announcements.php`, `src/Facades/Announcements.php` (docblock `@method static registerHooks()`)
- Test: `tests/RegistrationTest.php`

**Interfaces:**
- Consumes: `Announcement::isPopup()`, `pendingPopupsFor()` (Task 2), `AnnouncementPopups` with mount param `announcementIds` (Task 3).
- Produces: `Announcements::registerHooks(): void`, which does everything `register()` did after its two guards. `register()` keeps both guards (`Schema::hasTable`, `App::runningInConsole`) and then calls `registerHooks()`.

- [ ] **Step 1: Write the failing tests**

Helper in the test file: `function hook(string $name, string $scope): string { return FilamentView::renderHook($name, scopes: $scope)->toHtml(); }`

```php
it('shows a popup on Page scopes and a banner on SimplePage scopes, never both', function () {
    login();
    Announcement::factory()->popup()->scopes([Dashboard::class, Login::class])->create(['title' => 'Terms']);
    app(Announcements::class)->registerHooks();

    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->toContain('Terms')->toContain('role="dialog"')
        ->and(hook(PanelsRenderHook::CONTENT_START, Dashboard::class))->not->toContain('Terms')
        ->and(hook(PanelsRenderHook::SIMPLE_PAGE_START, Login::class))->toContain('Terms')->not->toContain('role="dialog"');
});

it('mounts one popup component per page, oldest announcement first', function () {
    login();
    Announcement::factory()->popup()->create(['title' => 'Newer', 'created_at' => now()]);
    Announcement::factory()->popup()->create(['title' => 'Older', 'created_at' => now()->subDay()]);
    app(Announcements::class)->registerHooks();

    $html = hook(PanelsRenderHook::BODY_END, Dashboard::class);
    expect(substr_count($html, 'role="dialog"'))->toBe(1)
        ->and($html)->toContain('Older')->toContain('1 of 2')->not->toContain('Newer');
});

it('keeps banners on CONTENT_START for banner announcements', function () {
    login();
    Announcement::factory()->create(['title' => 'Plain banner']);
    app(Announcements::class)->registerHooks();
    expect(hook(PanelsRenderHook::CONTENT_START, Dashboard::class))->toContain('Plain banner')
        ->and(hook(PanelsRenderHook::BODY_END, Dashboard::class))->not->toContain('Plain banner');
});

it('renders nothing for inactive announcements', function () {
    login();
    Announcement::factory()->popup()->create(['title' => 'Expired', 'end_date' => now()->subDay()]);
    app(Announcements::class)->registerHooks();
    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->not->toContain('Expired');
});

it('renders no popup component for guests or when everything is confirmed', function () {
    $a = Announcement::factory()->popup()->create();
    app(Announcements::class)->registerHooks();
    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->toBe('');

    $user = login();
    $a->dismissals()->create(['user_id' => $user->id]);
    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->toBe('');
});

it('ignores scope classes that no longer exist', function () {
    login();
    Announcement::factory()->popup()->scopes([Dashboard::class, 'App\\Filament\\Pages\\Removed'])->create(['title' => 'Survivor']);
    app(Announcements::class)->registerHooks();
    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->toContain('Survivor');
});
```

- [ ] **Step 2: Run them to verify they fail**

Run: `vendor/bin/pest tests/RegistrationTest.php`
Expected: FAIL (`registerHooks` undefined).

- [ ] **Step 3: Implement `registerHooks()`**

- Load the announcements with `Announcement::query()->orderBy('created_at')->orderBy('id')->get()` and keep only the active ones.
- Skip any scope where `! class_exists($scope)`. Decide page type with `is_a($scope, SimplePage::class, true)` / `is_a($scope, Page::class, true)` instead of `new $scope`.
- For each announcement and scope:
  - if it is a banner, or the scope is a `SimplePage`, register the existing banner hook (same closure, same hook choice as today);
  - otherwise, append its id to `$popupIdsByScope[$scope]`.
- After the loop, register one `PanelsRenderHook::BODY_END` hook per `$scope => $ids`, with `scopes: $scope`. The hook returns `''` when `! Filament::auth()->check()` or when `Announcement::pendingPopupsFor($ids, Filament::auth()->id())` is empty. Otherwise it returns `new HtmlString(Livewire::mount(AnnouncementPopups::class, ['announcementIds' => $ids]))`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest`
Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Announcements.php src/Facades/Announcements.php tests/RegistrationTest.php
git commit -m "feat: register one popup hook per page and keep banner fallback for simple pages"
```

---

### Task 5: "Display as" field in the admin form

**Files:**
- Modify: `src/Resources/Announcements/Schemas/AnnouncementForm.php`
- Test: `tests/AnnouncementFormTest.php`

**Interfaces:**
- Consumes: `AnnouncementDisplay` (+ `isPopupState`), `Announcement::scopesSupportPopup()`, translation keys `display.label`, `hints.*` (Task 2); `AnnouncementPopups` (Task 3, for the reset test).
- Produces: a form field `display` (`Filament\Forms\Components\Radio`) directly after `scopes`; `scopes` becomes `->live()`.

- [ ] **Step 1: Write the failing tests**

Use `Filament\Schemas\Components\Utilities\Get/Set` in the implementation. In the tests, `$popup = fn (Radio $f) => $f->isOptionDisabled('popup', AnnouncementDisplay::Popup->getLabel());`.

```php
beforeEach(fn () => login());

it('disables the popup option when only login-type pages are selected', function () {
    livewire(CreateAnnouncement::class)
        ->fillForm(['scopes' => [Login::class]])
        ->assertFormFieldExists('display', fn (Radio $f) => $f->isOptionDisabled('popup', AnnouncementDisplay::Popup->getLabel())
            && $f->getHelperText() === 'Select at least one page other than login or registration.')
        ->fillForm(['scopes' => [Login::class, Dashboard::class]])
        ->assertFormFieldExists('display', fn (Radio $f) => ! $f->isOptionDisabled('popup', AnnouncementDisplay::Popup->getLabel()));
});

it('shows the fallback hint only when popup is selected', function () {
    livewire(CreateAnnouncement::class)
        ->fillForm(['scopes' => [Dashboard::class], 'display' => 'popup'])
        ->assertFormFieldExists('display', fn (Radio $f) => $f->getHelperText() === 'Login and registration pages always show a banner.')
        ->fillForm(['display' => 'banner'])
        ->assertFormFieldExists('display', fn (Radio $f) => blank($f->getHelperText()));
});

it('resets display to banner when the last non-login scope is removed', function () {
    livewire(CreateAnnouncement::class)
        ->fillForm(['scopes' => [Dashboard::class, Login::class], 'display' => 'popup'])
        ->fillForm(['scopes' => [Login::class]])
        ->assertFormSet(['display' => 'banner']);
});

it('creates a popup announcement with mixed scopes', function () {
    livewire(CreateAnnouncement::class)
        ->fillForm(['title' => 'Terms', 'content' => 'Read me', 'scopes' => [Dashboard::class, Login::class], 'color' => 'info', 'display' => 'popup'])
        ->call('create')->assertHasNoFormErrors();
    expect(Announcement::sole()->display)->toBe(AnnouncementDisplay::Popup);
});

it('opens a legacy row as banner and keeps it a banner on save', function () {
    $id = DB::table('announcements')->insertGetId(['title' => 'Old', 'content' => 'x', 'scopes' => json_encode([Dashboard::class]), 'color' => 'info', 'created_at' => now(), 'updated_at' => now()]);
    livewire(EditAnnouncement::class, ['record' => $id])
        ->assertFormSet(['display' => 'banner'])
        ->call('save')->assertHasNoFormErrors();
    expect(Announcement::find($id)->display)->toBe(AnnouncementDisplay::Banner);
});

it('shows a confirmed popup again after Reset dismissals', function () {
    $a = Announcement::factory()->popup()->create(['title' => 'Again']);
    $a->dismissals()->create(['user_id' => auth()->id()]);
    livewire(EditAnnouncement::class, ['record' => $a->getRouteKey()])->callAction('reset-dismissals');
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])->assertSee('Again');
});
```

If `assertFormSet` sees the enum instance instead of the string, compare `->value`. Do not weaken the assertion.

- [ ] **Step 2: Run them to verify they fail**

Run: `vendor/bin/pest tests/AnnouncementFormTest.php`
Expected: FAIL (field `display` does not exist). The reset test may already pass; that's fine.

- [ ] **Step 3: Implement the field**

- `Radio::make('display')`:
  - `->label(display.label)->options(AnnouncementDisplay::class)` (descriptions come from `HasDescription`);
  - `->default(AnnouncementDisplay::Banner->value)->required()->columns(2)`;
  - `->disableOptionWhen(fn (string $value, Get $get) => $value === 'popup' && ! Announcement::scopesSupportPopup($get('scopes') ?? []))`;
  - `->helperText(...)` returning `hints.popup_unavailable` when popup isn't supported, else `hints.popup_fallback` when `AnnouncementDisplay::isPopupState($get('display'))`, else `null`;
  - `->live()`.
- On `scopes`, add `->live()->afterStateUpdated(fn ($state, Get $get, Set $set) => …)`, which sets `display` to `'banner'` when it is popup and `! scopesSupportPopup($state ?? [])`.

Lay the field out as the two option cards from `choose-banner-or-popup-for-an-announcement.png`. `ToggleButtons` is acceptable if the tests are adapted.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest`
Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Resources/Announcements/Schemas/AnnouncementForm.php tests/AnnouncementFormTest.php
git commit -m "feat: add Display as field with popup availability rules"
```

---

### Task 6: Infolist entry, table column, README

**Files:**
- Modify: `src/Resources/Announcements/Schemas/AnnouncementInfolist.php` (add `TextEntry::make('display')->label(display.label)->badge()` in the "Announcement Details" section, after `color`), `src/Resources/Announcements/Tables/AnnouncementsTable.php` (add `TextColumn::make('display')->label(display.label)->badge()` after `title`), `README.md`
- Test: `tests/AnnouncementResourceTest.php`

**Interfaces:**
- Consumes: the `AnnouncementDisplay` labels (Task 2). The enum cast gives the label automatically.

- [ ] **Step 1: Write the failing tests**

```php
beforeEach(fn () => login());

it('shows the display type in the table', function () {
    Announcement::factory()->popup()->create();
    livewire(ListAnnouncements::class)
        ->assertTableColumnExists('display')
        ->assertSee('Popup with confirmation required');
});

it('shows the display type on the view page', function () {
    $a = Announcement::factory()->popup()->create();
    livewire(ViewAnnouncement::class, ['record' => $a->getRouteKey()])
        ->assertSee('Display as')->assertSee('Popup with confirmation required');
});
```

- [ ] **Step 2: Run them to verify they fail**

Run: `vendor/bin/pest tests/AnnouncementResourceTest.php`
Expected: FAIL (column `display` not found).

- [ ] **Step 3: Add the entry and the column, then update the README**

Under "Creating Announcements", add item 5 **Display as**: Banner (default) or Popup with confirmation required. Add a "Popup announcements" subsection that covers:
- the popup blocks the page until "I've read this";
- login and registration (all `SimplePage`) scopes always show a banner;
- the option is disabled unless a non-login page is selected;
- several popups show one after another;
- **Reset dismissals** makes everyone confirm again.

Under the migrations instructions, add a note that existing installs must publish and run the new `add_display_to_announcements_table` migration.

- [ ] **Step 4: Run the full verification**

Run: `vendor/bin/pest && vendor/bin/pint --test && composer analyse`
Expected: all tests PASS, pint reports no changes, and PHPStan reports no errors. Fix any new findings rather than adding them to `phpstan-baseline.neon`.

- [ ] **Step 5: Commit**

```bash
git add src/Resources README.md tests/AnnouncementResourceTest.php
git commit -m "feat: show display type in table and infolist; document popup announcements"
```
