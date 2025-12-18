<?php
session_start();
session_unset();
session_destroy();

header("Location: /cine/index.php");
exit;
