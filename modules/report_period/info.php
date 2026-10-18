<?php
/**
 * Module: Guests report for a period — admin only.
 * All visits whose entry time falls inside the selected period,
 * with totals, filters, print view and CSV export.
 */

return array(
    'name'        => 'Guests report',
    'name_ar'     => 'تقرير الضيوف لفترة',
    'description' => 'All guest visits inside a period, with totals (admin).',
    'icon'        => 'bi-calendar3-range',
    'order'       => 50,
    'menu'        => false, // reachable from the Dashboard buttons
    'layout'      => 'app',
    'permission'  => 'report_period',
);
