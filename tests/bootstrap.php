<?php
// Rutas del entorno de pruebas. SRC_DIR = código fuente real del sistema.
define('SRC_DIR', getenv('TS_SRC') ?: realpath(__DIR__ . '/..'));
define('SANDBOX', sys_get_temp_dir() . '/ts_sandbox');
define('SESS_DIR', sys_get_temp_dir() . '/ts_sessions');
require_once __DIR__ . '/BaseTest.php';
