<?php
/**
 * Module: Dashboard — centered date & time counter (updates every second)
 * and the action buttons. The second row (reports + departments) is
 * visible to admins only, through the permission of each module.
 */

return array(
    'name'        => 'Dashboard',
    'name_ar'     => 'لوحة التحكم',
    'description' => 'Date/time clock and the main front office actions.',
    'icon'        => 'bi-speedometer2',
    'order'       => 10,
    'menu'        => true,
    'layout'      => 'app',
    'permission'  => null, // any logged-in user
);
