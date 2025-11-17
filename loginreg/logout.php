<?php
// ✅ Destroy both possible session types
session_name('USER_SESSION');
session_start();
session_unset();
session_destroy();

session_name('ADMIN_SESSION');
session_start();
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

header("Location: login.php");
exit();
?>
