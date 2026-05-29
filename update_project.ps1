Get-ChildItem -Path . -Recurse -File | Where-Object { $_.Extension -match 'vue|js|css|html|php|md|yml|json' -and $_.FullName -notmatch 'node_modules|vendor' } | ForEach-Object {
    Write-Host "Processing $($_.FullName)"
    $content = Get-Content $_.FullName -Raw
    $content = $content -replace 'emerald', 'blue'
    $content = $content -replace 'teal', 'indigo'
    $content = $content -replace 'attaqwa', 'assalaam'
    $content = $content -replace 'Attaqwa', 'Assalaam'
    $content = $content -replace 'ATTAQWA', 'AS-SALAAM'
    $content = $content -replace 'AT-TAQWA', 'AS-SALAAM'
    [System.IO.File]::WriteAllText($_.FullName, $content)
}
