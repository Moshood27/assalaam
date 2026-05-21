<?php
require 'backend/vendor/autoload.php';

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Models\Concerns\CausesActivity;

class TestUser {
    use LogsActivity, CausesActivity;
}

$user = new TestUser();
echo "Success: Traits are loadable and usable.\n";
