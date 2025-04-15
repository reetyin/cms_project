    </div>
    <footer class="bg-dark text-light py-4 mt-auto">
        <div class="container">
            <div class="row gy-3">
                <div class="col-md-4">
                    <h5 class="mb-3"><i class="bi bi-building me-2"></i>School CMS</h5>
                    <p class="text-muted small">Your complete solution for school information management. Find, compare, and learn about educational institutions.</p>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-decoration-none text-light"><i class="bi bi-chevron-right small me-1"></i>Home</a></li>
                        <li><a href="about.php" class="text-decoration-none text-light"><i class="bi bi-chevron-right small me-1"></i>About Us</a></li>
                        <li><a href="contact.php" class="text-decoration-none text-light"><i class="bi bi-chevron-right small me-1"></i>Contact Us</a></li>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="login.php" class="text-decoration-none text-light"><i class="bi bi-chevron-right small me-1"></i>Login</a></li>
                        <li><a href="register.php" class="text-decoration-none text-light"><i class="bi bi-chevron-right small me-1"></i>Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Connect With Us</h5>
                    <div class="d-flex gap-3 mb-3">
                        <a href="#" class="text-light fs-5"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light fs-5"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-light fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light fs-5"><i class="bi bi-linkedin"></i></a>
                    </div>
                    <p class="text-muted small mb-0">&copy; <?php echo date('Y'); ?> School CMS. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
