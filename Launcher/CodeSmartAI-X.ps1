# CodeSmartAI-X launcher for Windows + XAMPP
# It starts Apache and MySQL in the background (no XAMPP Control Panel needed)
# and opens the website in the browser.
#
# Actions:  open     start Apache + MySQL if they are off, then open the website
#           servers  start Apache + MySQL only (used by auto-start)
#           stop     turn Apache + MySQL off
#           icon     make the CodeSmartAI-X icon on the Desktop and in the Start menu
#           autoon   start by itself when Windows starts
#           autooff  stop starting by itself
#           langs    download and install Java, C++ and C# into <xampp>\langs (version 47)
param([string]$Action = 'open')

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

$Title = 'CodeSmartAI-X'
$Me    = $PSCommandPath
$Here  = $PSScriptRoot
$Icon  = Join-Path $Here 'codesmartaix.ico'
$script:Tray = $null

function Say([string]$Msg, [string]$Kind = 'Information') {
    $owner = New-Object System.Windows.Forms.Form
    $owner.TopMost = $true
    [void][System.Windows.Forms.MessageBox]::Show($owner, $Msg, $Title,
        [System.Windows.Forms.MessageBoxButtons]::OK,
        [System.Windows.Forms.MessageBoxIcon]$Kind)
    $owner.Dispose()
}

function Ask([string]$Msg) {
    $owner = New-Object System.Windows.Forms.Form
    $owner.TopMost = $true
    $r = [System.Windows.Forms.MessageBox]::Show($owner, $Msg, $Title,
        [System.Windows.Forms.MessageBoxButtons]::YesNo,
        [System.Windows.Forms.MessageBoxIcon]::Question)
    $owner.Dispose()
    return ($r -eq [System.Windows.Forms.DialogResult]::Yes)
}

function Show-Note([string]$Text) {
    try {
        $script:Tray = New-Object System.Windows.Forms.NotifyIcon
        if (Test-Path $Icon) { $script:Tray.Icon = New-Object System.Drawing.Icon $Icon }
        else { $script:Tray.Icon = [System.Drawing.SystemIcons]::Information }
        $script:Tray.Text = $Title
        $script:Tray.Visible = $true
        $script:Tray.ShowBalloonTip(4000, $Title, $Text, [System.Windows.Forms.ToolTipIcon]::Info)
    } catch { }
}

function Test-Port([int]$Port) {
    $c = New-Object System.Net.Sockets.TcpClient
    try {
        $r = $c.BeginConnect('127.0.0.1', $Port, $null, $null)
        if (-not $r.AsyncWaitHandle.WaitOne(400)) { return $false }
        $c.EndConnect($r)
        return $true
    } catch {
        return $false
    } finally {
        $c.Close()
    }
}

# ---------- 1. Find the website folder and XAMPP (from where this file is) ----------
# This file is in  <xampp>\htdocs\<website folder>\Launcher
$Site  = Split-Path $Here -Parent
$Parts = @()
$Dir   = $Site
while ($Dir -and ((Split-Path $Dir -Leaf) -ne 'htdocs')) {
    $Parts = @(Split-Path $Dir -Leaf) + $Parts
    $Dir   = Split-Path $Dir -Parent
}
if (-not $Dir) {
    Say ("The Launcher folder must be inside your website folder in XAMPP, for example:`n`n" +
         "C:\xampp\htdocs\capstone-system\Launcher`n`nIt is now in:`n" + $Here) 'Warning'
    exit 1
}
$Xampp  = Split-Path $Dir -Parent
$Httpd  = Join-Path $Xampp 'apache\bin\httpd.exe'
$Mysqld = Join-Path $Xampp 'mysql\bin\mysqld.exe'
if (-not (Test-Path $Httpd) -or -not (Test-Path $Mysqld)) {
    Say ("XAMPP was not found in:`n" + $Xampp + "`n`nApache or MySQL is missing there.") 'Error'
    exit 1
}

# ---------- 2. Ports and the website address ----------
$ApachePort = 80
$Conf = Join-Path $Xampp 'apache\conf\httpd.conf'
if (Test-Path $Conf) {
    $m = Select-String -Path $Conf -Pattern '^\s*Listen\s+(?:[\d\.]+:)?(\d+)' | Select-Object -First 1
    if ($m) { $ApachePort = [int]$m.Matches[0].Groups[1].Value }
}
$MysqlPort = 3306
$Ini = Join-Path $Xampp 'mysql\bin\my.ini'
if (Test-Path $Ini) {
    $m = Select-String -Path $Ini -Pattern '^\s*port\s*=\s*(\d+)' | Select-Object -First 1
    if ($m) { $MysqlPort = [int]$m.Matches[0].Groups[1].Value }
}
$Base = 'http://localhost'
if ($ApachePort -ne 80) { $Base = 'http://localhost:' + $ApachePort }
$Url = $Base + '/'
if ($Parts.Count -gt 0) {
    $Url = $Base + '/' + (($Parts | ForEach-Object { [uri]::EscapeDataString($_) }) -join '/') + '/'
}

# XAMPP's own Apache / MySQL programs that are running now
function Get-Mine([string]$Name) {
    foreach ($p in @(Get-Process -Name $Name -ErrorAction SilentlyContinue)) {
        $path = $null
        try { $path = $p.Path } catch { }
        if (-not $path -or $path -like ($Xampp + '\*')) { $p }
    }
}

function Get-LogTail([string]$File) {
    if (Test-Path $File) {
        try { return ((Get-Content -Path $File -Tail 6) -join "`n") } catch { }
    }
    return '(no log found)'
}

# ---------- 3. Start Apache and MySQL ----------
function Start-Servers {
    $my = $null
    $ap = $null
    $started = $false

    if (-not (Test-Port $MysqlPort)) {
        if (@(Get-Mine 'mysqld').Count -eq 0) {
            $my = Start-Process -FilePath $Mysqld -ArgumentList @("--defaults-file=`"$Ini`"", '--standalone') `
                  -WorkingDirectory $Xampp -WindowStyle Hidden -PassThru
            $started = $true
        }
    }

    if (Test-Port $ApachePort) {
        if (@(Get-Process -Name 'httpd' -ErrorAction SilentlyContinue).Count -eq 0) {
            Say ("Another program is already using port $ApachePort, so Apache cannot start.`n`n" +
                 "Close that program (for example IIS, Skype or VMware) and try again.") 'Warning'
            return $false
        }
    } elseif (@(Get-Mine 'httpd').Count -eq 0) {
        $ap = Start-Process -FilePath $Httpd -WorkingDirectory $Xampp -WindowStyle Hidden -PassThru
        $started = $true
    }

    if ($started) { Show-Note 'Starting the website... it opens in a few seconds.' }

    $myOk = $false
    $apOk = $false
    for ($i = 0; $i -lt 60; $i++) {            # wait up to 30 seconds
        if (-not $myOk) { $myOk = Test-Port $MysqlPort }
        if (-not $apOk) { $apOk = Test-Port $ApachePort }
        if ($myOk -and $apOk) { break }
        if ($my -and $my.HasExited -and -not $myOk) { break }
        if ($ap -and $ap.HasExited -and -not $apOk) { break }
        Start-Sleep -Milliseconds 500
    }

    if (-not $myOk) {
        Say ("MySQL did not start.`n`nOpen the XAMPP Control Panel once and press Start on MySQL to see the problem.`n`n" +
             "Last lines of the MySQL log:`n" + (Get-LogTail (Join-Path $Xampp 'mysql\data\mysql_error.log'))) 'Error'
        return $false
    }
    if (-not $apOk) {
        Say ("Apache did not start.`n`nPort $ApachePort may be used by another program (for example IIS, Skype or VMware).`n`n" +
             "Last lines of the Apache log:`n" + (Get-LogTail (Join-Path $Xampp 'apache\logs\error.log'))) 'Error'
        return $false
    }
    return $true
}

# ---------- 4. Stop Apache and MySQL ----------
function Stop-Servers {
    $admin = Join-Path $Xampp 'mysql\bin\mysqladmin.exe'
    if ((@(Get-Mine 'mysqld').Count -gt 0) -and (Test-Path $admin)) {
        # a clean MySQL shutdown first (XAMPP: user root, no password)
        try {
            Start-Process -FilePath $admin -ArgumentList @('-u', 'root', '--connect-timeout=5', 'shutdown') `
                -WindowStyle Hidden -Wait
        } catch { }
        for ($i = 0; ($i -lt 20) -and (@(Get-Mine 'mysqld').Count -gt 0); $i++) { Start-Sleep -Milliseconds 500 }
    }
    foreach ($p in @(Get-Mine 'mysqld') + @(Get-Mine 'httpd')) {
        try { Stop-Process -Id $p.Id -Force } catch { }
    }
    Say 'CodeSmartAI-X is stopped (Apache and MySQL are off).'
}

# ---------- 5. Icons and auto-start ----------
function New-Link([string]$Path, [string]$Act, [string]$Desc) {
    $ws = New-Object -ComObject WScript.Shell
    $l = $ws.CreateShortcut($Path)
    $l.TargetPath = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
    $l.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + $Me + '" ' + $Act
    $l.WorkingDirectory = $Here
    if (Test-Path $Icon) { $l.IconLocation = $Icon + ',0' }
    $l.WindowStyle = 7
    $l.Description = $Desc
    $l.Save()
}

function Make-Icons {
    Get-ChildItem -Path $Here -File | Unblock-File -ErrorAction SilentlyContinue
    $desk = [Environment]::GetFolderPath('Desktop')
    $menu = [Environment]::GetFolderPath('Programs')
    New-Link (Join-Path $desk 'CodeSmartAI-X.lnk') 'open' 'Open CodeSmartAI-X (starts Apache and MySQL by itself)'
    New-Link (Join-Path $menu 'CodeSmartAI-X.lnk') 'open' 'Open CodeSmartAI-X (starts Apache and MySQL by itself)'
    New-Link (Join-Path $menu 'Stop CodeSmartAI-X.lnk') 'stop' 'Turn off Apache and MySQL'
    Say ("Done! There is now a CodeSmartAI-X icon on your Desktop and in the Start menu.`n`n" +
         "Double-click it to open the website. You do not need to open XAMPP or type localhost any more.`n`n" +
         "Website address: " + $Url)
}

function Set-AutoOn {
    $openToo = Ask ("Start CodeSmartAI-X by itself every time Windows starts?`n`n" +
                    "YES = start Apache and MySQL AND open the website.`n" +
                    "NO  = only start Apache and MySQL (the Desktop icon then opens the website right away).")
    $act = 'servers'
    if ($openToo) { $act = 'open' }
    $startup = [Environment]::GetFolderPath('Startup')
    New-Link (Join-Path $startup 'CodeSmartAI-X.lnk') $act 'CodeSmartAI-X auto-start'
    if ($openToo) { Say 'Auto-start is ON. When Windows starts, the website opens by itself.' }
    else { Say 'Auto-start is ON. When Windows starts, Apache and MySQL start by themselves in the background.' }
}

function Set-AutoOff {
    $startup = [Environment]::GetFolderPath('Startup')
    Remove-Item -Path (Join-Path $startup 'CodeSmartAI-X.lnk') -ErrorAction SilentlyContinue
    Say 'Auto-start is OFF.'
}

# ---------- 6. Install Java, C++ and C# (version 47) ----------
# Everything goes into <xampp>\langs (no admin rights needed). CodeSmartAI-X finds it there by itself.
function Step([string]$Text) {
    Write-Host ''
    Write-Host ('== ' + $Text) -ForegroundColor Cyan
}

function Invoke-Exe([string]$Exe, [string[]]$ArgList, [string]$Dir) {
    # runs a program and returns everything it printed
    $old = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    Push-Location $Dir
    try {
        return ((& $Exe @ArgList 2>&1 | ForEach-Object { "$_" }) -join "`n")
    } catch {
        return ('Could not start: ' + $_.Exception.Message)
    } finally {
        Pop-Location
        $ErrorActionPreference = $old
    }
}

function Get-File([string]$Url, [string]$Out) {
    $curl = Join-Path $env:SystemRoot 'System32\curl.exe'
    if (Test-Path $curl) {
        $old = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        & $curl -L --fail --retry 3 --retry-delay 3 --connect-timeout 30 -o $Out $Url
        $code = $LASTEXITCODE
        $ErrorActionPreference = $old
        if ($code -ne 0) { throw ('The download failed (check the internet connection): ' + $Url) }
    } else {
        Invoke-WebRequest -Uri $Url -OutFile $Out -UseBasicParsing
    }
}

function Expand-Zip([string]$Zip, [string]$Dest) {
    $tar = Join-Path $env:SystemRoot 'System32\tar.exe'
    if (Test-Path $tar) {
        $old = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        & $tar -xf $Zip -C $Dest
        $code = $LASTEXITCODE
        $ErrorActionPreference = $old
        if ($code -ne 0) { throw ('Could not unzip ' + $Zip) }
    } else {
        Expand-Archive -Path $Zip -DestinationPath $Dest -Force
    }
}

function Find-Java([string]$Langs) {
    $all = @(Get-ChildItem -Path $Langs -Directory -Filter 'jdk*' -ErrorAction SilentlyContinue |
             Sort-Object Name -Descending |
             ForEach-Object { Join-Path $_.FullName 'bin\java.exe' } |
             Where-Object { Test-Path $_ })
    if ($all.Count -gt 0) { return $all[0] }
    return $null
}

function Install-Langs {
    $Langs = Join-Path $Xampp 'langs'
    $Tmp   = Join-Path $Langs '_downloads'
    $Test  = Join-Path $Tmp 'test'

    $go = Ask ("Install Java, C++ and C# for CodeSmartAI-X?`n`n" +
               "They are downloaded (about 700 MB, needs internet) into:`n" + $Langs + "`n`n" +
               "It takes about 5 to 15 minutes. Students do NOT need this on their own laptops: only the computer that runs CodeSmartAI-X.")
    if (-not $go) { return }

    $drive = Get-PSDrive -Name ((Split-Path -Qualifier $Xampp).TrimEnd(':')) -ErrorAction SilentlyContinue
    if ($drive -and $drive.Free -lt 3GB) {
        $gb = [math]::Round($drive.Free / 1GB, 1)
        if (-not (Ask ("Only $gb GB is free on this drive. The languages need about 2 GB.`n`nContinue anyway?"))) { return }
    }

    New-Item -ItemType Directory -Force -Path $Test | Out-Null
    [Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12
    $ProgressPreference = 'SilentlyContinue'
    $env:DOTNET_NOLOGO = '1'
    $env:DOTNET_CLI_TELEMETRY_OPTOUT = '1'
    $env:DOTNET_SKIP_FIRST_TIME_EXPERIENCE = '1'
    $res = [ordered]@{}

    Write-Host 'CodeSmartAI-X: installing Java, C++ and C#' -ForegroundColor Green
    Write-Host ('Folder: ' + $Langs)
    Write-Host 'Keep this window open until it says DONE. Languages that are already installed are skipped.'

    # --- Java ---
    Step 'Java (Eclipse Temurin JDK 21)'
    try {
        $java = Find-Java $Langs
        if ($java) {
            Write-Host ('Already installed: ' + $java)
        } else {
            $zip = Join-Path $Tmp 'jdk.zip'
            Get-File 'https://api.adoptium.net/v3/binary/latest/21/ga/windows/x64/jdk/hotspot/normal/eclipse' $zip
            Write-Host 'Unzipping Java...'
            Expand-Zip $zip $Langs
            $java = Find-Java $Langs
        }
        if (-not $java) { throw 'java.exe was not found after unzipping.' }
        Write-Host 'Testing Java...'
        Set-Content -Path (Join-Path $Test 'Main.java') -Encoding ASCII -Value 'public class Main { public static void main(String[] a) { System.out.println("Java OK"); } }'
        $o = Invoke-Exe $java @('Main.java') $Test
        if ($o -match 'Java OK') { $res['Java'] = 'ready' } else { throw ('the test program did not run: ' + $o) }
    } catch {
        $res['Java'] = 'NOT ready (' + $_.Exception.Message + ')'
    }

    # --- C++ ---
    Step 'C++ (g++ from WinLibs MinGW-w64)'
    try {
        $gpp = Join-Path $Langs 'mingw64\bin\g++.exe'
        if (Test-Path $gpp) {
            Write-Host ('Already installed: ' + $gpp)
        } else {
            Write-Host 'Finding the newest version...'
            $rels = Invoke-RestMethod -Uri 'https://api.github.com/repos/brechtsanders/winlibs_mingw/releases?per_page=15' `
                    -Headers @{ 'User-Agent' = 'CodeSmartAI-X' } -UseBasicParsing
            $asset = $null
            foreach ($r in $rels) {
                if ($r.prerelease) { continue }
                $asset = $r.assets | Where-Object { $_.name -match '^winlibs-x86_64-posix-seh-gcc-.+\.zip$' -and $_.name -notmatch 'llvm' } |
                         Select-Object -First 1
                if ($asset) { break }
            }
            if (-not $asset) { throw 'the C++ download was not found on GitHub.' }
            $zip = Join-Path $Tmp 'mingw.zip'
            Write-Host ('Downloading ' + $asset.name)
            Get-File $asset.browser_download_url $zip
            Write-Host 'Unzipping C++ (this can take a few minutes)...'
            Expand-Zip $zip $Langs
        }
        if (-not (Test-Path $gpp)) { throw 'g++.exe was not found after unzipping.' }
        Write-Host 'Testing C++...'
        Set-Content -Path (Join-Path $Test 'a.cpp') -Encoding ASCII -Value ("#include <iostream>`nint main() { std::cout << `"C++ OK`" << std::endl; return 0; }")
        $env:PATH = (Split-Path $gpp) + ';' + $env:PATH
        $b = Invoke-Exe $gpp @('-std=c++17', '-static', 'a.cpp', '-o', 'a.exe') $Test
        $exe = Join-Path $Test 'a.exe'
        if (-not (Test-Path $exe)) { throw ('the test program did not compile: ' + $b) }
        $o = Invoke-Exe $exe @() $Test
        if ($o -match 'C\+\+ OK') { $res['C++'] = 'ready' } else { throw ('the test program did not run: ' + $o) }
    } catch {
        $res['C++'] = 'NOT ready (' + $_.Exception.Message + ')'
    }

    # --- C# ---
    Step 'C# (.NET SDK, long-term support version)'
    try {
        $dn = Join-Path $Langs 'dotnet\dotnet.exe'
        if (Test-Path $dn) {
            Write-Host ('Already installed: ' + $dn)
        } else {
            $inst = Join-Path $Tmp 'dotnet-install.ps1'
            Get-File 'https://dot.net/v1/dotnet-install.ps1' $inst
            & $inst -Channel LTS -InstallDir (Join-Path $Langs 'dotnet') -NoPath
        }
        if (-not (Test-Path $dn)) { throw 'dotnet.exe was not found after installing.' }
        $sdk = Get-ChildItem -Path (Join-Path $Langs 'dotnet\sdk') -Directory -ErrorAction SilentlyContinue |
               Where-Object { $_.Name -match '^\d+\.\d+\.\d+$' } | Sort-Object { [version]$_.Name } -Descending | Select-Object -First 1
        if (-not $sdk) { throw 'no .NET SDK was found after installing.' }
        $tfm = 'net' + $sdk.Name.Split('.')[0] + '.0'
        Write-Host ('Testing C# (' + $tfm + ', the first build takes about 30 seconds)...')
        $cs = Join-Path $Test 'cs'
        New-Item -ItemType Directory -Force -Path $cs | Out-Null
        Set-Content -Path (Join-Path $cs 'Program.cs') -Encoding ASCII -Value 'Console.WriteLine("C# OK");'
        Set-Content -Path (Join-Path $cs 'app.csproj') -Encoding ASCII -Value ('<Project Sdk="Microsoft.NET.Sdk"><PropertyGroup><OutputType>Exe</OutputType><TargetFramework>' + $tfm + '</TargetFramework><ImplicitUsings>enable</ImplicitUsings><Nullable>disable</Nullable></PropertyGroup></Project>')
        $o = Invoke-Exe $dn @('run', '--nologo', '-v', 'q') $cs
        if ($o -match 'C# OK') { $res['C#'] = 'ready' } else { throw ('the test program did not run: ' + $o) }
    } catch {
        $res['C#'] = 'NOT ready (' + $_.Exception.Message + ')'
    }

    Remove-Item -Path $Tmp -Recurse -Force -ErrorAction SilentlyContinue
    $lines = @()
    foreach ($k in $res.Keys) { $lines += ($k + ': ' + $res[$k]) }
    Write-Host ''
    Write-Host 'DONE' -ForegroundColor Green
    $lines | ForEach-Object { Write-Host $_ }
    $bad = @($res.Values | Where-Object { $_ -ne 'ready' })
    $short = ($lines | ForEach-Object { if ($_.Length -gt 300) { $_.Substring(0, 300) + '...' } else { $_ } }) -join "`n"
    if ($bad.Count -eq 0) {
        Say ("All languages are ready:`n`n" + $short + "`n`nPython, JavaScript, HTML / CSS and PHP were already working.`nOpen the Playground and press Run. You do not need to restart anything.")
    } else {
        Say ("Some languages are not ready yet:`n`n" + $short + "`n`nCheck the internet connection and double-click Install languages.bat again. Languages that are ready are skipped.") 'Warning'
    }
}

# ---------- 7. Do it ----------
try {
    switch ($Action.ToLower()) {
        'open'    { if (Start-Servers) { Start-Process $Url } }
        'servers' { [void](Start-Servers) }
        'stop'    { Stop-Servers }
        'icon'    { Make-Icons }
        'autoon'  { Set-AutoOn }
        'autooff' { Set-AutoOff }
        'langs'   { Install-Langs }
        default   { Say ('Unknown action: ' + $Action) 'Warning' }
    }
} catch {
    Say ("Something went wrong:`n`n" + $_.Exception.Message) 'Error'
} finally {
    if ($script:Tray) { Start-Sleep -Milliseconds 300; $script:Tray.Dispose() }
}
