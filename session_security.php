<?php

session_start();

// Function to forcibly end the session
function end_session() {
    session_unset();
    session_destroy();
}

// Does the request IP match the stored value?
function request_ip_matches_session() {
    // return false if either value is not set
    if (!isset($_SESSION ['ip']) || !isset($_SERVER['REMOTE_ADDR'])) {
        return false;
    } else {
        return true;
    }
}
    // Does the request user agent match the stored value
    function request_user_agent_matches_session() {
        // return false if either value is not set
        if (!isset($_SESSION['user_agent']) || !isset($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }

// Has too much time passed since the last login?
function last_login_is_recent() {
    $max_elapsed = 60 * 60 * 24; // 1 day
    // return false if value is not set 
    if (!isset($_SESSION['last_login'])) {
        return false;
    }
    if (($_SESSION['last_login'] + $max_elapsed) >= time()) {
        return true;
    } else {
        return false;
    }
}

// Should the session be considered valid?
function is_session_valid() {
    $check_ip = true;
    $check_user_agent = true;
    $check_last_login = true;
    
    if ($check_ip && ! request_ip_matches_session()) {
        return false;
    }
    if ($check_user_agent && ! request_user_agent_matches_session()) {
        return false;
    }
    if ($check_last_login && ! last_login_is_recent()) {  
        return false;
    }
    return true;
}

// If session is not valid, end and redirect to login page.
function confirm_session_is_valid() {
    if (!is_session_valid()) {
        end_session();
        header("Location: index.php");
        exit;
    }
}

// Is user logged in already?
function is_logged_in() {
    return (isset($_SESSION['logged_in']) && $_SESSION['logged_in']);
}

function confirm_user_logged_in() {
    if (!is_logged_in()) {
        end_session();
        header("Location: index.php");
        exit;
    }
}

// Actions to perfrom after every successful login
function after_successful_login() {
    // Regenerate session ID to invalidate the old one.
    session_regenerate_id();
    $_SESSION['logged_in'] = true;

    // Save these values in the session, even when checks aren't enabled
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['last_login'] = time();
}

// Action to perform after ever successful logout
function after_successful_logout() {
    $_SESSION['logged_in'] = false;
    end_session();
}

//Actions to perfore before giving access to any
function before_every_protected_page() {
    confirm_user_logged_in();
    confirm_session_is_valid();
}

?>