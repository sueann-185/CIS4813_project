    </main>

        <footer class="bg-dark text-white py-4 mt-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-4 mb-md-0">
                        <h5 class="d-flex align-items-center">
                            <i class="bi bi-heart-fill me-2 text-danger"></i> DonationHub
                        </h5>
                        <p class="text-muted">Connecting generosity with need. Share what you have, find what you need.</p>
                        <div class="social-icons">
                            <a href="#" class="text-white me-2"><i class="bi bi-facebook fs-5"></i></a>
                            <a href="#" class="text-white me-2"><i class="bi bi-twitter fs-5"></i></a>
                            <a href="#" class="text-white me-2"><i class="bi bi-instagram fs-5"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-linkedin fs-5"></i></a>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4 mb-md-0">
                        <h5>Quick Links</h5>
                        <ul class="list-unstyled">
                            <li><a href="/project" class="text-white text-decoration-none">Home</a></li>
                            <li><a href="/project/donations.php" class="text-white text-decoration-none">Donations</a></li>
                            <li><a href="/project/requests.php" class="text-white text-decoration-none">Requests</a></li>
                            <li><a href="/project/about.php" class="text-white text-decoration-none">About Us</a></li>
                            <li><a href="/project/contact.php" class="text-white text-decoration-none">Contact</a></li>
                        </ul>
                    </div>

                    <div class="col-md-4">
                        <h5>Contact Us</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-geo-alt me-2"></i> 123 Charity St, City, Country</li>
                            <li class="mb-2"><i class="bi bi-telephone me-2"></i> +1 (555) 123-4567</li>
                            <li class="mb-2"><i class="bi bi-envelope me-2"></i> info@donationhub.org</li>
                        </ul>
                    </div>
                </div>

                <hr class="my-4 bg-light">

                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; <?= date('Y') ?> DonationHub. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="/project/privacy.php" class="text-white text-decoration-none me-3">Privacy Policy</a>
                        <a href="/project/terms.php" class="text-white text-decoration-none">Terms of Service</a>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <?php if (function_exists('page_specific_scripts')) page_specific_scripts(); ?>
    </body>
</html>