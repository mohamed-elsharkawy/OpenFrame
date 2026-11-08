<?php
/**
 * OpenFrame — English translations
 * All user-facing strings used by the framework layout and helpers.
 */

return [
    /* ---- About Modal ---- */
    'about.title'     => 'About OpenFrame',
    'about.button'    => 'About',

    /* ---- DevTools ---- */
    'devtool.enable'  => 'Enable DevTools',
    'devtool.disable' => 'Disable DevTools',
    'about.app_name'  => 'Application Name',
    'about.version'   => 'Version',
    'about.framework' => 'Framework',
    'about.description' => 'A lightweight, module-driven PHP framework built with plain PHP and Bootstrap 5. No Composer, no templating engine, no ORM, and no build step.',
    'about.author'    => 'Author',
    'about.github'    => 'GitHub Repository',
    'about.license'   => 'License',

    /* ---- Application ---- */
    'app.name'        => 'OpenFrame',
    'app.version'     => 'Version',
    'app.powered_by'  => 'Powered by OpenFrame',
    'app.created_by'  => 'Created by OpenFrame',
    'app.github'      => 'GitHub',
    'app.php'         => 'PHP',

    /* ---- Common ---- */
    'common.home'             => 'Home',
    'common.dashboard'        => 'Dashboard',
    'common.settings'         => 'Settings',
    'common.system'           => 'System Configuration',
    'common.full_name'        => 'Full Name',
    'common.level'            => 'Level',
    'common.created'          => 'Created',
    'common.actions'          => 'Actions',
    'common.save'             => 'Save',
    'common.cancel'           => 'Cancel',
    'common.delete'           => 'Delete',
    'common.edit'             => 'Edit',
    'common.create'           => 'Create',
    'common.update'           => 'Update',
    'common.close'            => 'Close',
    'common.actions'          => 'Actions',
    'common.name'             => 'Name',
    'common.description'      => 'Description',
    'common.version'          => 'Version',
    'common.created'          => 'Created',
    'common.module'           => 'Module',
    'common.modules'          => 'Modules',
    'common.no_modules'       => 'No modules installed.',
    'common.profile'          => 'My Profile',
    'common.disabled'         => 'Disabled',
    'common.enabled'          => 'Enabled',
    'general'                 => 'General',

    /* ---- Navigation ---- */
    'nav.home'                => 'Home',
    'nav.modules'             => 'Modules',
    'nav.sidemenu'            => 'Menu',
    'nav.language'            => 'Language',
    'nav.english'             => 'English',
    'nav.arabic'              => 'Arabic',

    /* ---- Messages / Notifications ---- */
    'msg.saved'               => 'Saved successfully.',
    'msg.deleted'             => 'Deleted successfully.',
    'msg.created'             => 'Created successfully.',
    'msg.updated'             => 'Updated successfully.',
    'msg.error'               => 'An error occurred.',
    'msg.not_found'           => 'Not found.',
    'msg.access_denied'       => 'Access denied.',
    'msg.invalid_token'       => 'Invalid or expired form token. Go back, reload the page and try again.',
    'msg.wrong_credentials'   => 'Wrong username or password.',
    'msg.welcome_back'        => 'Welcome back.',

    /* ---- Errors ---- */
    'err.404_title'           => 'Module not found',
    'err.404_message'         => 'No enabled module matches the requested route.',
    'err.404_view_title'      => 'Module view missing',
    'err.404_view_message'    => 'The route has no index.php view.',
    'err.db_title'            => 'Database connection failed',
    'err.db_message'          => 'Could not connect to the database. Check your credentials in includes/config.php.',

    /* ---- OTP / TOTP ---- */
    'otp.title'               => 'Two-factor authentication',
    'otp.subtitle'            => 'Enter the 6-digit code from your authenticator app.',
    'otp.setup_title'         => 'Set up authenticator',
    'otp.setup_help'          => 'Scan this QR code with Google Authenticator, Microsoft Authenticator or a similar app, then enter the 6-digit code.',
    'otp.secret_label'        => 'Or enter this key manually',
    'otp.code'                => 'Authentication code',
    'otp.verify'              => 'Verify',
    'otp.cancel'              => 'Back to sign in',
    'otp.invalid'             => 'Invalid or expired code. Try again.',
    'otp.locked'              => 'Too many attempts. Sign in again.',
    'otp.reset'               => 'Reset authenticator',
    'otp.reset_ok'            => 'Authenticator reset. The user must scan a new QR code at next sign-in.',

    /* ---- Login module ---- */
    'login.title'             => 'Sign in',
    'login.username'          => 'Username',
    'login.password'          => 'Password',
    'login.signin'            => 'Sign in',
    'login.signout'           => 'Sign out',
    'login.welcome'           => 'Welcome back.',
    'login.invalid'           => 'Wrong username or password.',
    'login.required'          => 'Please enter both username and password.',
    'login.access_denied'     => 'Access denied.',

    /* ---- Home module ---- */
    'home.welcome'            => 'Welcome to',
    'home.subtitle'           => 'A lightweight PHP framework built with plain PHP and Bootstrap.',
    'home.get_started'        => 'Get started',
    'home.browse_modules'     => 'Browse modules',
    'home.app_info'           => 'Application info',
    'home.app_name'           => 'App name',
    'home.app_path'           => 'App path',
    'home.installed_modules'  => 'Installed modules',
    'home.how_routing'        => 'How routing works',
    'home.create_module'      => 'Create a module',
    'home.steps_1'            => 'index.php boots the framework (config → modules → routes).',
    'home.steps_2'            => 'routes.php builds the route table from the enabled modules.',
    'home.steps_3'            => 'current_module() reads ?module= and validates it.',
    'home.steps_4'            => 'load_module() executes the module view between header and footer.',
    'home.file_info_php'      => 'modules/<module>/info.php',
    'home.file_index_php'     => 'modules/<module>/index.php',
    'home.appears_in_navbar'  => 'appears automatically in the navbar and at ?module=<module>.',
];
