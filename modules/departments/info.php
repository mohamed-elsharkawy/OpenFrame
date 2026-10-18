<?php
/**
 * Module: Departments hierarchy — admin only.
 * Add a department (with an optional parent), rename it, or delete it
 * (sub-departments move up one level when their parent is deleted).
 */

return array(
    'name'        => 'Departments',
    'name_ar'     => 'هيكل الإدارات',
    'description' => 'Add / edit / delete the departments hierarchy (admin).',
    'icon'        => 'bi-diagram-3-fill',
    'order'       => 60,
    'menu'        => false, // reachable from the Dashboard buttons
    'layout'      => 'app',
    'permission'  => 'departments_manage',
);
