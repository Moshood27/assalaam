@echo off
REM Windows CMD wrapper to run Laravel Sail via WSL from the project root
REM Usage (CMD): sail.bat up -d
setlocal EnableExtensions

for /f "usebackq delims=" %%i in (`wsl wslpath -a "%CD%"`) do set "WSL_PROJECT_PATH=%%i"

set "ARGS="
:argloop
if "%~1"=="" goto run
set "ARG=%~1"
REM Escape single quotes for safe usage inside single-quoted bash strings
set ARG=%ARG:'='"'"'%
set "ARGS=%ARGS% '%ARG%'"
shift
goto argloop

:run
wsl sh -lc "cd '%WSL_PROJECT_PATH%' && ./sail%ARGS%"
endlocal
