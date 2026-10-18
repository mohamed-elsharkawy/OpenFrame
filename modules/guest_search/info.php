<?php
/**
 * Module: Search Guest Information — search by name, national ID /
 * passport or record number; all matching visits are listed in a grid.
 */

return array(
    'name'        => 'Search Guest',
    'name_ar'     => 'البحث عن بيانات ضيف',
    'description' => 'Search guests by name, ID/passport or record number.',
    'icon'        => 'bi-search',
    'order'       => 40,
    'menu'        => true,
    'layout'      => 'app',
    'permission'  => 'guest_search',
);
