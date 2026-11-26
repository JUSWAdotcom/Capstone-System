<?php require 'config.php';header('Location: '.BASE.(isset($_SESSION['user'])?($_SESSION['user']['role']==='admin'?'Admin/admin.php':'Dashboard/dashboard.php'):'Logging/login.php'));
