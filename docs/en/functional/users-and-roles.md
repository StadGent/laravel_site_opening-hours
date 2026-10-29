# Users and roles

## Roles

| Role | Name in the UI | Scope |
| --- | --- | --- |
| Admin | Admin | Global: all services and users |
| Editor | Redacteur | Global: opening hours of all services |
| Owner | Beheerder van een dienst | Per service |
| Member | Lid van een dienst | Per service |

A user has either a global role (Admin or Editor), or one or more roles per
service. An Admin cannot get a role per service.

## Permissions

| Action | Admin | Editor | Owner | Member |
| --- | --- | --- | --- | --- |
| Delete a user | x | | | |
| Make a user Admin | super admin only | | | |
| Invite a user as Owner or Member | all services | | own service | |
| Change or revoke a user's role in a service | all services | | own service | |
| Activate / deactivate a service | x | | | |
| Create and edit channels | all services | all services | own service | own service |
| Delete channels | all services | | own service | own service |
| Manage versions and calendars | all services | all services | own service | own service |

Nobody can change their own role or delete themselves.

The rules live in `app/Policies/UserPolicy.php` and in the `admin`, `isOwner`
and `hasRoleInService` middleware (`app/Http/Middleware`).

## Super admin

One user is the **super admin**, set with `SUPER_ADMIN_ID` (default `1`, the
admin created by `./artisan db:seed`). Only the super admin can:

- grant or revoke the Admin role;
- invite someone as Admin, or invite an existing Admin with another role (the
  Admin role is then removed);
- delete an Admin.

!!! warning
    Make sure `SUPER_ADMIN_ID` points to an existing user with the Admin role,
    otherwise nobody can manage admins through the UI.

## Inviting and registering

1. An Admin or Owner invites someone with an e-mail address, a role and one or
   more services.
2. If the user doesn't exist yet, it is created without a password and receives
   the mail **"Activeer uw gebruiker"** with a link to
   `/register/confirm/{token}`, where they choose a password.
3. Every invite also sends the mail **"U werd toegang verleend tot een
   dienst"** with the list of services.

Forgot password sends the mail **"Reset wachtwoord"**. All mails are sent
through SMTP.
