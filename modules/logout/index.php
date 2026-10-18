<?php
/**
 * Module: Logout (view) — just log out and go to the login page.
 */

logout();
redirect(url('index.php?module=login'));
