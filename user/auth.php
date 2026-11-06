    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    function is_logged_in(): bool {
        return isset($_SESSION['user_id']);
    }

    function current_username(): string {
        return $_SESSION['username'] ?? '';
    }

    function redirect_after_login(): string {
        return $_SESSION['redirect_after_login'] ?? '../index.php';
    }

    function set_redirect_after_login(string $url): void {
        $_SESSION['redirect_after_login'] = $url;
    }

    function clear_redirect_after_login(): void {
        unset($_SESSION['redirect_after_login']);
    }

    function require_login(): void {
        if (!is_logged_in()) {
            // simpan halaman yang sedang dibuka agar setelah login balik ke sini
            $current = $_SERVER['REQUEST_URI'] ?? '/';
            set_redirect_after_login($current);

            header("Location: login.php");
            exit;
        }
    }
