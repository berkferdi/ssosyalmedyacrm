        </div>
        <footer class="text-center text-muted py-3 border-top">
            <small>&copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?></small>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>const APP_URL = '<?= app_url() ?>'; const CSRF_TOKEN = '<?= Security::generateCSRFToken() ?>';</script>
<script src="<?= app_url() ?>/assets/js/app.js"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $script): ?>
<script src="<?= app_url() ?>/assets/js/<?= $script ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
