# PowerShell wrapper to run Laravel Sail via WSL from the project root
# Usage (PowerShell): .\sail.ps1 up -d   or simply: .\sail up -d
# This forwards all arguments to the existing ./sail shell script inside WSL.
$ErrorActionPreference = 'Stop'

# Convert the current Windows path to a WSL path
$projectPath = (Get-Location).Path
$wslProjectPath = (wsl wslpath -a "$projectPath").Trim()

function Escape-ForBash([string]$s) {
  return "'" + ($s -replace "'", "'\"'\"'") + "'"
}

$argString = ($args | ForEach-Object { Escape-ForBash $_ }) -join ' '

# Build and execute the command in WSL
$cmd = "cd '$wslProjectPath' && sh ./sail $argString"
wsl sh -lc $cmd
