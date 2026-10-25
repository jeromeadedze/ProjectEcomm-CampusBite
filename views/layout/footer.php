    </main>

    <!-- CampusBite Footer -->
    <footer class="cb-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5 class="text-white fw-bold"><i class="bi bi-fire text-warning me-2"></i>CampusBite</h5>
                    <p class="small text-secondary">
                        The campus-native food ordering and delivery service for Ashesi University. Pre-order from Hallmark, Akonnor, Munchees, Peckish & Eat Healthy without standing in lunch queues.
                    </p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Zone A: GH₵ 4.00</span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Zone B: GH₵ 7.00</span>
                    </div>
                </div>
                <div class="col-md-3 offset-md-1">
                    <h6 class="text-white fw-semibold mb-3">Partner Kitchens</h6>
                    <ul class="list-unstyled small">
                        <li><a href="<?php echo $ROOT_DIR; ?>/index.php?brand=1" class="text-decoration-none">Hallmark Cafeteria</a></li>
                        <li><a href="<?php echo $ROOT_DIR; ?>/index.php?brand=2" class="text-decoration-none">Akonnor Cafeteria</a></li>
                        <li><a href="<?php echo $ROOT_DIR; ?>/index.php?brand=3" class="text-decoration-none">Munchees Grills</a></li>
                        <li><a href="<?php echo $ROOT_DIR; ?>/index.php?brand=4" class="text-decoration-none">Peckish Berekuso</a></li>
                        <li><a href="<?php echo $ROOT_DIR; ?>/index.php?brand=5" class="text-decoration-none">Eat Healthy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white fw-semibold mb-3">Campus Drop Points</h6>
                    <p class="small text-secondary mb-2">
                        Delivered directly to Walter Sisulu, Wangari Maathai, FabLab, Engineering block, Library courtyard & faculty offices by fellow verified student couriers.
                    </p>
                    <p class="small text-muted mb-0">Payments supported via MTN MoMo, Telecel Cash & Paystack.</p>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-secondary small">
                <span>&copy; <?php echo date('Y'); ?> CampusBite (Ashesi E-Commerce Project). All rights reserved.</span>
                <span>Team: Jerome, Kofi, Augustus, Markin</span>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $ROOT_DIR; ?>/js/main.js"></script>
</body>
</html>
