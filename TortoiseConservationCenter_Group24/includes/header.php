<?php
session_start();
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<header>
    <nav>
        <div class="nav-links">
            <a href="/TortoiseProjectFile/TortoiseConservationCenter_Group24/dashboard.php" class="nav-link">Dashboard</a>
            <a href="/TortoiseProjectFile/TortoiseConservationCenter_Group24/tortoises.php" class="nav-link">Tortoises</a>
            <a href="/TortoiseProjectFile/TortoiseConservationCenter_Group24/enclosure.php" class="nav-link">Enclosures</a>
            <a href="/TortoiseProjectFile/TortoiseConservationCenter_Group24/feeding.php" class="nav-link">Feeding</a>
            <a href="/TortoiseProjectFile/TortoiseConservationCenter_Group24/health.php" class="nav-link">Health</a>
            <a href="/TortoiseProjectFile/TortoiseConservationCenter_Group24/breeding.php" class="nav-link">Breeding</a>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <a href="/TortoiseProjectFile/TortoiseConservationCenter_Group24/staff.php" class="nav-link">Staff</a>
            <?php endif; ?>
        </div>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
            <button onclick="logout()" class="nav-link btn btn-danger">Logout</button>
        </div>
    </nav>
</header>
<script>
    // Highlight current page in navigation
    document.addEventListener('DOMContentLoaded', () => {
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-link').forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.endsWith(currentPage)) {
                link.classList.add('active');
            }
        });
    });

    // Logout function
    function logout() {
        fetch('logout.php', {
            method: 'POST',
            credentials: 'same-origin'
        }).then(() => {
            window.location.href = 'login.php';
        });
    }
</script>
