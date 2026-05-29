$replacements = @{
    'Ã¢â€šÂ¦' = '₦'
    'â‚¦' = '₦'
    'â€“' = '–'
    'Ã¢â‚¬Â¢' = '•'
    'Ã¢â‚¬Â¦' = '...'
    'Ã¢â‚¬â€' = '—'
    'Ã°Å¸â€â€' = '🔔'
    'Ã°Å¸Å¡Â¨' = '🚨'
    'Ã¢Å¾Â¡Ã¯Â¸Â' = '➡️'
    'Ã°Å¸â€œÂ' = '📍'
    'Ã¢Å¡Â Ã¯Â¸Â' = '⚠️'
    'Ã¢Å¡â€“Ã¯Â¸Â' = '⚖️'
    'Ã°Å¸â€”Â³Ã¯Â¸Â' = '🗳️'
    'Ã¢ÂÂ³' = '⏳'
    'Ã°Å¸â€â€˜' = '🔑'
    'Ã¢â‚¬Â¢' = '•'
}

$files = Get-ChildItem -Recurse -Include *.php,*.vue,*.js,*.md -Exclude node_modules,vendor,storage,public

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $changed = $false
    foreach ($key in $replacements.Keys) {
        if ($content.Contains($key)) {
            $content = $content.Replace($key, $replacements[$key])
            $changed = $true
        }
    }
    if ($changed) {
        # Use [System.IO.File]::WriteAllText to ensure UTF-8 without BOM if possible, 
        # but Set-Content with UTF8 encoding is usually fine for modern versions.
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        Write-Host "Fixed encoding in: $($file.FullName)"
    }
}
