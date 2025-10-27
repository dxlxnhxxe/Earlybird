# EarlyBird Hosts File Setup (For Windows)
# Run this script as Administrator

Write-Host "EarlyBird - Adding hosts file entries..." -ForegroundColor Green

$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
$hostsEntries = @(
    "127.0.0.1 earlybird-front",
    "127.0.0.1 earlybird-dashboard", 
    "127.0.0.1 earlybird-api"
)

# Check if running as Administrator
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click on PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    pause
    exit 1
}

# Read current hosts file
$hostsContent = Get-Content $hostsPath

# Check if entries already exist
$needsUpdate = $false
foreach ($entry in $hostsEntries) {
    if ($hostsContent -notcontains $entry) {
        $needsUpdate = $true
        break
    }
}

if (-not $needsUpdate) {
    Write-Host "All EarlyBird entries already exist in hosts file!" -ForegroundColor Yellow
    pause
    exit 0
}

# Backup hosts file
$backupPath = "$hostsPath.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Copy-Item $hostsPath $backupPath
Write-Host "Hosts file backed up to: $backupPath" -ForegroundColor Cyan

# Add entries
Write-Host "`nAdding entries to hosts file..." -ForegroundColor Yellow
foreach ($entry in $hostsEntries) {
    if ($hostsContent -notcontains $entry) {
        Add-Content -Path $hostsPath -Value $entry
        Write-Host "  Added: $entry" -ForegroundColor Green
    } else {
        Write-Host "  Skipped (exists): $entry" -ForegroundColor Gray
    }
}

Write-Host "`nSuccess! EarlyBird hosts entries have been added." -ForegroundColor Green
Write-Host "`nYou can now access:" -ForegroundColor Cyan
Write-Host "  - Frontend: http://earlybird-front" -ForegroundColor White
Write-Host "  - Dashboard: http://earlybird-dashboard" -ForegroundColor White
Write-Host "  - API: http://earlybird-api" -ForegroundColor White

pause
