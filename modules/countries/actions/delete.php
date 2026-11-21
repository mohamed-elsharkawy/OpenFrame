<?php
/**
 * OpenFrame — Module: Countries / Action: delete
 * Delete a country (cannot delete if it has cities).
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$id = (int) input('id');
$cityCount = db_count('cities', 'country_id = ?', array($id));

if ($cityCount > 0) {
    flash('danger', lang('countries.cannot_delete_has_cities', 'Cannot delete: country has cities.'));
} else {
    $deleted = db_delete('countries', 'id = ?', array($id));
    flash($deleted ? 'success' : 'warning', $deleted
        ? lang('countries.deleted_ok', 'Country deleted.')
        : lang('msg.not_found', 'Not found.'));
}

redirect(url('index.php?module=countries'));
