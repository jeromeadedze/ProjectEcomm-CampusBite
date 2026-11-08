        </main>
        <?php require __DIR__ . '/panel.php'; ?>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Stamped with its edit time, like the stylesheets, so browsers never run an old copy -->
    <script src="<?php echo $ROOT_DIR; ?>/js/main.js?v=<?php echo filemtime(__DIR__ . '/../../js/main.js'); ?>"></script>
</body>
</html>
