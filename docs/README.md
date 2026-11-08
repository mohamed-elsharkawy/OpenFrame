# OpenFrame documentation

| File | Audience | Contents |
|---|---|---|
| [../README.md](../README.md) | Everyone | GitHub landing page: install, modules, actions, function index |
| [DEVELOPER.md](DEVELOPER.md) | Developers | Full function reference (CRUD, helpers, routing, OTP, QR) with examples |
| [OpenFrame-User-Guide.md](OpenFrame-User-Guide.md) | Beginners | Tutorial walkthrough (install, first module, CRUD, exports) |
| [examples/notes_crud](examples/notes_crud) | Developers | Copy this folder into `modules/` for a working CRUD screen |

## Start here

1. Import `schema.sql` and set database credentials in `includes/config.php`.
2. Sign in (`superadmin` / `admin` / `user`, password `password`).
3. Create a module: folder + `info.php` + `index.php` — see [README §7](../README.md#7-create-a-new-module).
4. Add POST actions with `input('action')` — see [README §8](../README.md#8-create-an-action-inside-a-module).
5. Look up any function in [DEVELOPER.md](DEVELOPER.md).
