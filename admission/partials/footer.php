    <script src="../assets/js/jquery-3.6.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admission.js"></script>

    <?php if (!empty($_SESSION['toast'])): ?>
        <script>
            toast('<?= h($_SESSION['toast']['type']) ?>', '<?= h($_SESSION['toast']['message']) ?>');
        </script>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>