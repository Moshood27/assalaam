<?php

return [
    // Disable Sanctum's automatic last_used_at updates so we can manage idle timeout manually
    'last_used_at' => false,
];
