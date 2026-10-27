# Changelog

All notable changes to `backstage-announcements` will be documented in this file.

## v5.10.0: Popups users must confirm - 2026-10-27

### v5.10.0: Popups users must confirm

This release lets you show an important announcement as a popup that every user has to confirm before they can carry on.

#### TL;DR

- **Make sure people read it:** show an announcement as a popup that stays until the user clicks "I've read this" (#15)
- **One-time upgrade step:** publish and run the new migration; your existing announcements stay banners (#15)

#### ⚠️ Action needed

- [ ] Publish the new migration: `php artisan vendor:publish --tag="backstage-announcements-migrations"`
- [ ] Run `php artisan migrate`; existing announcements keep showing as banners

#### ✨ New

##### Popup announcements with required confirmation

Choose **Display as: Popup with confirmation required** to show an announcement as a popup that blocks the page until the user clicks "I've read this". There is no close button, so you know every signed-in user has seen your updated terms or other important news, and they only see it once.

![A popup announcement blocking the dashboard with an "I've read this" button](https://github.com/backstagephp/announcements/blob/hl-assets/release-v5.10.0/popup-dashboard.png?raw=true)

[#15](https://github.com/backstagephp/announcements/pull/15)

##### Choose banner or popup per announcement

The announcement form has a new **Display as** choice: Banner (the default) or Popup. The popup option is greyed out when you only picked login or registration pages, so you can't save a popup nobody can confirm.

![The announcement form with the new "Display as" choice between Banner and Popup](https://github.com/backstagephp/announcements/blob/hl-assets/release-v5.10.0/form-display-as.png?raw=true)

[#15](https://github.com/backstagephp/announcements/pull/15)

- **Several popups, one at a time:** when more than one popup applies, users see them oldest first with an "N of M" counter (#15)
- **Login pages keep working:** on login and registration pages a popup announcement shows as a normal banner, and guests never get popups (#15)
- **Ask everyone again:** the existing **Reset dismissals** action makes all users confirm a popup again (#15)
- **See the display type at a glance:** the announcements list and detail page show whether each one is a banner or a popup (#15)

#### 🛠️ Improved

- **Long popups stay readable:** popups with a lot of text scroll instead of running off the screen (#15)

#### 🧹 Under the hood

<details><summary>3 smaller changes</summary>
- **Safer changes:** new tests cover banners, popups, the form and registration, with a test panel and announcement factory (#15)
- **Clearer docs:** the README explains popup announcements and the upgrade step (#15)
- **Up-to-date CI:** bump shivammathur/setup-php from 2.37.2 to 2.40.0 (#12)

</details>
**Full changelog:** https://github.com/backstagephp/announcements/compare/v5.9.4...v5.10.0

## 1.0.0 - 202X-XX-XX

- initial release
