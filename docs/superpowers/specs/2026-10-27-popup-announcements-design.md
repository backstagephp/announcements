# Popup announcements with required confirmation — design

Date: 2026-10-27
Package: `backstage/announcements` (Filament 5 plugin)
Mockups: `mockup-form.html` / `choose-banner-or-popup-for-an-announcement.png` (form) and `mockup-popup.html` / `popup-users-must-confirm-with-i-ve-read-this.png` (popup), kept with the run's design artifacts.

## 1. Intent

Banners are easy to overlook or to dismiss without reading them. Admins need a way to make sure every user has actually seen an important announcement, such as updated terms of service. A **popup announcement** shows a modal on the selected pages. The only way to close it is the "I've read this" button. Once a user has confirmed, they don't see it again.

## 2. Goals, non-goals, anti-goals

### Goals
- For each announcement, the admin picks how it is shown: **Banner** (today's behaviour and the default) or **Popup with confirmation required**.
- The popup blocks the page until the user clicks "I've read this". After that, that user never sees it again.
- The existing **Reset dismissals** action on the Edit page makes everyone see and confirm the popup again.

### Non-goals
- Popups on guest pages (login, registration and other `SimplePage`s). These pages always show a banner.
- Showing a popup again automatically after it is edited, or on every login.
- A report of who confirmed. The rows are in `announcement_dismissals`, but this change adds no UI for them.
- Rich text or links in the content, extra buttons, or "remind me later".

### Anti-goals (these count as failures even if the feature works)
- A popup that users can close without clicking "I've read this" (X, Esc, clicking outside).
- A popup announcement that *also* shows as a banner on a page where the popup appears.
- Changing how existing announcements behave. Existing rows must stay banners and behave exactly as they do now.
- A popup that can never be confirmed, such as one shown to a guest, who can't create a dismissal.

## 3. Current state (what the code does today)

- `src/Models/Announcement.php`: fields `title`, `content`, `scopes` (JSON array of page class names), `color`, `start_date`, `end_date`. `isActive()` checks the date range. `isDismissedBy()` returns `false` for guests. `render($scope)` mounts the Livewire banner.
- `src/Announcements.php::register()` (called from `AnnouncementsPlugin::register()`) loops over every active announcement and scope. It registers `SIMPLE_PAGE_START` for `SimplePage` scopes and `CONTENT_START` for other `Page` scopes. Each hook renders the banner unless the current user dismissed it.
- `src/Livewire/Announcement.php` + `resources/views/livewire/announcement.blade.php`: the banner. `markAsRead()` creates an `AnnouncementDismissal`, whose `creating` hook fills in `user_id` and `dismissed_at`. `announcement_dismissals` has a unique key on `(announcement_id, user_id)`.
- `src/Collections/ScopeCollection.php` builds the scope options: resource pages, panel pages, plus the login page and (when enabled) the registration page.
- `src/Resources/Announcements/Schemas/AnnouncementForm.php`: the form. `scopes` is a required multi-`Select` that is **not** `live()` today.
- `EditAnnouncement` has a **Reset dismissals** header action that deletes all dismissal rows for the record.
- Migrations are `.stub` files listed in `AnnouncementsServiceProvider::getMigrations()`.
- `resources/lang/en/backstage-announcements.php` is empty. Existing strings use bare `__('…')`.
- Tests: the only tests are placeholders (`ExampleTest`, `DebugTest`), and `database/factories/ModelFactory.php` is a commented-out stub. `TestCase` registers Filament and Livewire but sets up no panel.

## 4. Decisions made with the person

1. **Form control.** A "Display as" field with two options: `Banner` (default) and `Popup with confirmation required`. A popup announcement shows *only* as a modal, never also as a banner. In the mockup the options are two radio cards, each with a one-line description:
   - Banner: "A coloured bar at the top of the page that users can dismiss."
   - Popup with confirmation required: "A modal that users must close by clicking “I've read this”."
2. **Availability.** The popup option is enabled only when at least one selected scope is *not* a login-type page. Otherwise it is **greyed out (not hidden)** with the hint "Select at least one page other than login or registration."
3. **Login and registration pages in a popup announcement** fall back to the normal banner. All other selected pages show the popup. When popup is selected, the field shows the hint "Login and registration pages always show a banner."
4. **Showing again.** Once a user confirms, they never see the popup again, unless an admin clicks **Reset dismissals**. That action already exists and needs no changes.
5. **Closing.** No X, no Esc, no click-outside. Only "I've read this" closes the popup.
6. **Several popups on one page** are shown one after another, with a "1 of 2" counter.

## 5. Design

### 5.1 Data
- New migration stub `database/migrations/add_display_to_announcements_table.php.stub`. It adds a `string('display')->default('banner')` column after `color`, and `down()` drops it. Add it to the end of `getMigrations()`. Existing rows become `banner`.
- New enum `src/Enums/AnnouncementDisplay.php`: `string`-backed, cases `Banner = 'banner'` and `Popup = 'popup'`. It implements Filament's `HasLabel` (and `HasDescription` if the chosen form component uses it) and returns the translated labels and descriptions.
- `Announcement` model:
  - add `display` to `$fillable`, and cast `display` to `AnnouncementDisplay`.
  - default attribute `display => banner`, so new models without the field are banners.
  - `public static function scopesSupportPopup(array $scopes): bool` returns true when at least one scope is a class that exists and **is not** a `Filament\Pages\SimplePage` subclass. Use `is_subclass_of` / `is_a` with strings rather than instantiating. Unknown strings such as the `'*'` fallback in `saving` do not count. The form and the save guard both use this one helper.
  - `isPopup(): bool` is a convenience method.
  - in the existing `saving` hook: if `display` is `Popup` and `! scopesSupportPopup($scopes)`, set `display` to `Banner`. The server then never stores an invalid popup, whatever the form sends.

### 5.2 Admin form (`AnnouncementForm`)
- Make `scopes` `->live()` so the new field reacts to it.
- Add a `display` field directly after `scopes` (as in the mockup, before Color), labelled "Display as", required, default `banner`. Use Filament `Radio::make('display')->options(AnnouncementDisplay::class)` with descriptions, laid out in two columns, or `ToggleButtons`. Either is fine as long as both options show their label and description, as in the mockup.
- Greyed-out state: when `! Announcement::scopesSupportPopup($get('scopes') ?? [])`, disable the popup option (`->disableOptionWhen(fn ($value, Get $get) => …)`) and show the hint "Select at least one page other than login or registration." as helper text.
- When popup is selected and available, the helper text is "Login and registration pages always show a banner." (show it only when popup is selected).
- In `scopes` `afterStateUpdated`: if `display` is popup and the new scopes no longer support it, set `display` back to `banner`.
- Don't build the "NEW" badge from the mockup. It was only there to point out the field.

### 5.3 Showing announcements (`Announcements::register()`)
- **Banners and the SimplePage fallback** work as they do now. An announcement gets the existing banner hook for a scope when the announcement is a banner, **or** when it is a popup and the scope is a `SimplePage`.
- **Popups on `Page` scopes:** don't register one hook per announcement. Gather the active popup announcements per `Page` scope, then register **one** hook per scope on `PanelsRenderHook::BODY_END` (scoped to that page class). The hook mounts a new Livewire component, `AnnouncementPopups`, with the ordered IDs of the popup announcements for that scope. One component per page lets the popups show one at a time with a shared counter, instead of several modals stacking on top of each other.
- The hook renders nothing when there's no authenticated user (`Filament::auth()->check()`), or when the current user has already dismissed every popup in the list. Non-simple panel pages require login anyway; this is a safety net for the "can never be confirmed" anti-goal.
- Order: `created_at` ascending (oldest first), then `id`.
- Note: like today, `register()` runs once when the panel registers and does not run in the console. The date range is checked at that point, the same as for banners today. Dismissal checks happen when the hook is evaluated or the component renders.

### 5.4 Popup component (`src/Livewire/AnnouncementPopups.php` + `resources/views/livewire/announcement-popups.blade.php`)
- State: `array $announcementIds` (the original ordered list for this page) and `int $total` (fixed when the component is mounted).
- On mount and render, the pending list is the announcements from `$announcementIds` that are not dismissed by the current user (use one query, not N `exists()` calls). The current popup is the first pending one. Counter: `:current of :total`, where `current = total - pending + 1`. After confirming the first of two, the counter reads "2 of 2". Hide the counter when `total` is 1.
- `confirm(int $id)`: only acts if `$id` is in `$announcementIds` and the user is logged in. It creates the dismissal with `firstOrCreate` on `(announcement_id, user_id)`, which is safe against double clicks given the unique key, then re-renders. When nothing is pending, it renders an empty `<div></div>`, matching the banner component.
- Register it in `AnnouncementsServiceProvider` next to the existing component (`backstage.announcements.livewire.announcement-popups`).
- Markup and behaviour:
  - Fixed full-screen dark overlay with a centered panel about `28rem` wide, in Filament modal style (rounded, white / dark-mode gray, shadow). Use Tailwind classes that the package's compiled CSS / Filament theme already provides, and check they appear in `resources/dist` after `npm run build`.
  - Icon in a tinted circle and a full-width primary "I've read this" button, both coloured with the announcement's `color` (reuse `get_color_css_variables` as the banner does). Title as the heading, content as body text (escaped, plain text, newlines kept with `whitespace-pre-line`).
  - **No** close button. Esc and clicks on the overlay do nothing.
  - Accessibility: `role="dialog"`, `aria-modal="true"`, `aria-labelledby` set to the title and `aria-describedby` to the content. On show, focus moves to the button and stays trapped inside the dialog (Alpine `x-trap` is part of Filament's bundle). Lock page scroll while it is open (`x-trap.noscroll`).
  - The button shows a loading or disabled state while `confirm` runs (`wire:loading.attr="disabled"`).

### 5.5 Texts
Add the strings to `resources/lang/en/backstage-announcements.php` and use them through the package's translation namespace. The provider calls `$package->hasTranslations()`, so the namespace is whatever `Package::shortName()` resolves to for `backstage/announcements`. Check it with a test rather than guessing.
- "Display as", "Banner", "Popup with confirmation required"
- the two option descriptions from §4.1
- "Select at least one page other than login or registration."
- "Login and registration pages always show a banner."
- "I've read this", ":current of :total"

### 5.6 View page and table
- `AnnouncementInfolist`: add a "Display as" `TextEntry` (badge, enum label) in the Announcement Details section.
- `AnnouncementsTable`: add a `display` badge column (my call; the design marked this optional).

### 5.7 README
Document the "Display as" option, the login and registration fallback, and how Reset dismissals affects popups. Mention that existing installs must publish and run the new migration.

## 6. Testing (Pest, Testbench, Livewire)

The test setup has to be built first: a real `AnnouncementFactory` (replacing the stub), a test `User` model and `users` table, and a test Filament panel (panel provider in the Testbench workbench or `TestCase`) with the plugin, a dashboard `Page` and login. Load the package migration stubs in tests.

- **Model:** `display` defaults to `banner` and casts to the enum. Saving a popup whose scopes are only `SimplePage`s stores `banner`. `scopesSupportPopup()` is true for a mix, false for only login/registration, and false for `['*']`.
- **Form (Create/Edit page Livewire tests):** the popup option is disabled when scopes are only login/registration, and enabled once another page is added. Removing the last non-login scope resets `display` to `banner`. Creating with popup and a mix of scopes stores `popup`.
- **Registration:** a popup announcement renders the popup component for `Page` scopes and the banner for `SimplePage` scopes, and never a banner on the `Page` scope. Inactive announcements (outside their date range) render nothing.
- **Popup component:** renders the title, content and "I've read this", with no close button. `confirm` creates one dismissal for the current user, and calling it twice still leaves one row. After confirming the first of two popups the second shows with "2 of 2", and after the last the component renders empty. An ID that isn't in the list is ignored.
- **Reset dismissals:** after the Edit page action runs, the popup shows again for a user who had confirmed it.
- **Regression:** existing banner behaviour (rendering, dismiss button, dismissed banner hidden) is unchanged.

## 7. Left to the builder
- `Radio` versus `ToggleButtons` for the form field, and the exact layout of the option cards, as long as it matches the mockup's intent.
- The exact Tailwind classes and icon (the mockup uses an exclamation mark in a tinted circle; `Heroicon::OutlinedExclamationCircle` or similar is fine).
- Whether the scope-to-popup grouping lives in `Announcements::register()` or in a small helper.

## 8. Calls I made (not discussed with the person)
- "Login-type" means any `Filament\Pages\SimplePage` subclass (login, registration, password reset…). This matches how `register()` already picks `SIMPLE_PAGE_START`.
- Data: a `display` string column plus a backed enum, rather than a boolean, to leave room for more display types later.
- Confirmations are stored as `AnnouncementDismissal` rows, so Reset dismissals works unchanged and no new table is needed.
- One popup component per page holds the queue. Order is oldest first. The counter counts against the original total.
- Popups mount on `BODY_END` so the overlay sits above the whole layout, not inside the content area.
- Guests never get a popup.
- A display column in the table.
