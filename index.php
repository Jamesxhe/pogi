<?php
require_once "db.php";
require_once "helpers.php";

if (!empty($_SESSION['user_id'])) {
    redirect("dashboard.php");
}
redirect("login.php");
