<?php
/**
 * OpenFrame — Module: Cities / Action: delete
 * Delete a city.
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$id = (int) input('id');
$deleted = db_delete('cities', 'id = ?', array($id));
flash($deleted ? 'success' : 'warning', $deleted
    ? lang('cities.deleted_ok', 'City deleted.')
    : lang('msg.not_found', 'Not found.'));

redirect(url('index.php?module=cities'));
