<?php
session_start();
session_destroy();
header('Location: /documanager/index.php');
exit;
