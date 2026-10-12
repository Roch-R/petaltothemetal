param(
    [ValidateSet('shop', 'phpmyadmin')][string]$Mode = 'shop',
    [switch]$NoBrowser
)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$xamppRoot = if ($env:PETAL_XAMPP_HOME) { $env:PETAL_XAMPP_HOME } else { 'C:\xampp' }
$php = Join-Path $xamppRoot 'php\php.exe'
$mysqld = Join-Path $xamppRoot 'mysql\bin\mysqld.exe'
$installer = Join-Path $xamppRoot 'mysql\bin\mysql_install_db.exe'
$logs = Join-Path $projectRoot 'storage\logs'
$helper = Join-Path $PSScriptRoot 'prepare-local.php'
Set-Location -LiteralPath $projectRoot

function Test-LocalPort([int]$Port) {
    $client = New-Object System.Net.Sockets.TcpClient
    try { return $client.ConnectAsync('127.0.0.1', $Port).Wait(500) -and $client.Connected }
    catch { return $false }
    finally { $client.Dispose() }
}

function Wait-LocalPort([int]$Port, $Process) {
    for ($attempt = 0; $attempt -lt 40; $attempt++) {
        if (Test-LocalPort $Port) { return }
        if ($Process.HasExited) { throw "Server stopped. Check the startup logs in $logs." }
        Start-Sleep -Milliseconds 500
    }
    throw "Server did not start on port $Port. Check the startup logs in $logs."
}

function Start-PhpServer([int]$Port, [string]$WebRoot, [string]$LogName) {
    if (!(Test-LocalPort $Port)) {
        $process = Start-Process -FilePath $php -ArgumentList "-S 127.0.0.1:$Port -t `"$WebRoot`"" `
            -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru `
            -RedirectStandardOutput (Join-Path $logs "$LogName.out.log") `
            -RedirectStandardError (Join-Path $logs "$LogName.err.log")
        Wait-LocalPort $Port $process
    }
}

try {
    if (!(Test-Path -LiteralPath $php)) { throw "PHP not found at $php. Install XAMPP or set PETAL_XAMPP_HOME." }
    New-Item -ItemType Directory -Path $logs -Force | Out-Null
    $settingsJson = & $php $helper --settings
    if ($LASTEXITCODE -ne 0) { throw 'Could not read local database settings.' }
    $settings = $settingsJson | ConvertFrom-Json
    if (!(Test-LocalPort $settings.port)) {
        if ($settings.port -eq 3307) {
            $data = Join-Path $logs 'local-mariadb'
            if (!(Test-Path -LiteralPath (Join-Path $data 'my.ini'))) {
                if (!(Test-Path -LiteralPath $installer)) { throw 'XAMPP database installer is missing.' }
                & $installer "--datadir=$data" --port=3307
                if ($LASTEXITCODE -ne 0) { throw 'Could not initialize the project database.' }
            }
            # Rewrite only the data location so moving the project folder still works.
            $ini = "[mysqld]`r`ndatadir=$($data.Replace('\', '/'))`r`nport=3307`r`nbind-address=127.0.0.1`r`n[client]`r`nport=3307`r`n"
            [System.IO.File]::WriteAllText((Join-Path $data 'my.ini'), $ini, (New-Object System.Text.UTF8Encoding($false)))
            $dbArgs = "--defaults-file=`"$(Join-Path $data 'my.ini')`" --standalone --console"
        } elseif ($settings.port -eq 3306) {
            $dbArgs = "--defaults-file=`"$(Join-Path $xamppRoot 'mysql\bin\my.ini')`" --standalone --console"
        } else { throw 'Start your configured database server first, then rerun run.bat.' }
        $dbProcess = Start-Process -FilePath $mysqld -ArgumentList $dbArgs -WindowStyle Hidden -PassThru `
            -WorkingDirectory (Split-Path $mysqld -Parent) `
            -RedirectStandardOutput (Join-Path $logs 'database-start.out.log') `
            -RedirectStandardError (Join-Path $logs 'database-start.err.log')
        Wait-LocalPort $settings.port $dbProcess
    }
    & $php $helper
    if ($LASTEXITCODE -ne 0) { throw 'Database setup did not complete.' }
    Start-PhpServer 8000 (Join-Path $projectRoot 'public') 'web-start'
    $shopResponse = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/' -UseBasicParsing -TimeoutSec 10
    if ($shopResponse.Content -notmatch 'Petal') { throw 'Port 8000 belongs to another website. Stop that server first.' }
    $url = 'http://127.0.0.1:8000/'
    if ($Mode -eq 'phpmyadmin') {
        # A private copy connects phpMyAdmin to the same server as this shop.
        $pmaSource = Join-Path $xamppRoot 'phpMyAdmin'
        $pmaRoot = Join-Path $logs 'phpmyadmin'
        if (!(Test-Path -LiteralPath (Join-Path $pmaRoot 'index.php'))) {
            if (!(Test-Path -LiteralPath (Join-Path $pmaSource 'index.php'))) { throw 'XAMPP phpMyAdmin is missing.' }
            Write-Host 'Preparing project phpMyAdmin (first run only)...'
            New-Item -ItemType Directory -Path $pmaRoot -Force | Out-Null
            Get-ChildItem -LiteralPath $pmaSource -Force | Copy-Item -Destination $pmaRoot -Recurse -Force
        }
        New-Item -ItemType Directory -Path (Join-Path $pmaRoot 'tmp') -Force | Out-Null
        & $php $helper --phpmyadmin
        if ($LASTEXITCODE -ne 0) { throw 'Could not configure phpMyAdmin.' }
        Start-PhpServer 8001 $pmaRoot 'phpmyadmin-start'
        $page = Invoke-WebRequest -Uri 'http://127.0.0.1:8001/' -UseBasicParsing -TimeoutSec 20
        if ($page.Content -notmatch 'phpMyAdmin') { throw 'Port 8001 belongs to another website.' }
        $url = 'http://127.0.0.1:8001/'
    }
    Write-Host "Ready: $url"
    Write-Host 'Servers run in the background; you can close this window.'
    if (!$NoBrowser) { Start-Process $url }
    exit 0
} catch {
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
