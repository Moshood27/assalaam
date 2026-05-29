<?php
$file = 'frontend/dist/assets/index-Cmuo8c_W.js';
$content = file_get_contents($file);
$pos = strpos($content, 'Qard Hasan Status');
echo bin2hex(substr($content, $pos - 500, 1000));
