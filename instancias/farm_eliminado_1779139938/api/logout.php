<?php
session_name('filas_' . preg_replace('/[^a-zA-Z0-9_]/', '_', basename(dirname(__DIR__))));
session_start();
session_unset();
session_destroy();
header("Location: ../login.php");
exit;