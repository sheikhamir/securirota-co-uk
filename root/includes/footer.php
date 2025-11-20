    </div> <!-- End container-fluid -->

    <!-- Footer -->
    <footer class="bg-light mt-5 py-3 border-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-crown me-1"></i>Root Control Center &copy; <?= date('Y') ?>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Version 2.0 | Last updated: <?= date('Y-m-d H:i:s') ?>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Root Common JavaScript -->
    <script src="assets/js/root-common.js"></script>
    
    <!-- Additional JavaScript if needed -->
    <?php if (isset($additional_js) && is_array($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?= htmlspecialchars($js_file) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Page-specific JavaScript -->
    <?php if (isset($inline_js)): ?>
    <script>
        <?= $inline_js ?>
    </script>
    <?php endif; ?>

</body>
</html>