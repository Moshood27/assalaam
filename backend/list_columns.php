<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$columns = Schema::getColumnListing('loan');
echo "Columns in 'loan' table:\n";
print_r($columns);
