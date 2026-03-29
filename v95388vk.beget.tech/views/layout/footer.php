<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($js)): ?>
        <?php foreach ((array)$js as $file): ?>
            <script src="/js/<?php echo e($file); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>