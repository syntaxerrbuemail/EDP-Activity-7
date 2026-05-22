    <script src="assets/js/notifications.js"></script>
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
        
        <?php if (isset($_SESSION['toast'])): ?>
            showToast("<?php echo $_SESSION['toast']['message']; ?>", "<?php echo $_SESSION['toast']['type']; ?>");
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
