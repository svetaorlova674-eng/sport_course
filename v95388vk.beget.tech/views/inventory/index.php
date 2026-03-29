<?php
$pageTitle = 'SibGo — Прокат спортивного инвентаря';
require ROOT . '/views/layout/header.php';

$filterGet = array_filter(array(
    'q'         => $f_search,
    'sport'     => $f_sport,
    'category'  => $f_category,
    'type'      => $f_type,
    'only_free' => $f_only_free ? '1' : '',
));
?>

<nav class="navbar navbar-light bg-light px-3 px-md-4 mb-4 shadow-sm">
    <div class="container-fluid d-flex flex-column flex-md-row gap-2 gap-md-0">
        <?php require ROOT . '/views/layout/logo.php'; ?>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <?php if (isLoggedIn()): ?>
                <a href="/profile" class="btn btn-outline-primary btn-sm">Мои аренды</a>
                <a href="/change-password" class="btn btn-outline-primary btn-sm">Сменить пароль</a>
                <?php if (isAdmin()): ?>
                    <a href="/admin" class="btn btn-outline-danger btn-sm">Панель админа</a>
                    <a href="/admin/add-item" class="btn btn-success btn-sm">+ Добавить</a>
                <?php endif; ?>
                <a href="/logout" class="btn btn-dark btn-sm">Выйти</a>
            <?php else: ?>
                <a href="/login" class="btn btn-primary btn-sm">Войти</a>
                <a href="/register" class="btn btn-outline-primary btn-sm">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-3">

    <div class="card mb-4 p-3 bg-light">
        <form action="/" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control"
                       placeholder="Поиск по названию..."
                       value="<?php echo e($f_search); ?>">
            </div>
            <div class="col-md-2">
                <select name="sport" class="form-select">
                    <option value="">Вид спорта</option>
                    <?php foreach (array('Велоспорт'=>'Велоспорт','Горные лыжи'=>'Лыжный спорт','Сноубординг'=>'Сноубординг','Самокаты'=>'Кикскутеринг') as $val => $label): ?>
                        <option value="<?php echo e($val); ?>" <?php echo $f_sport === $val ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select">
                    <option value="">Категория</option>
                    <option value="Инвентарь"  <?php echo $f_category === 'Инвентарь'  ? 'selected' : ''; ?>>Инвентарь</option>
                    <option value="Экипировка" <?php echo $f_category === 'Экипировка' ? 'selected' : ''; ?>>Экипировка</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">Тип</option>
                    <?php foreach (array('Велосипед','Лыжи','Сноуборд','Самокат','Шлем','Ботинки горнолыжные','Крепления для сноуборда','Палки горнолыжные','Велозащита') as $t): ?>
                        <option value="<?php echo e($t); ?>" <?php echo $f_type === $t ? 'selected' : ''; ?>><?php echo e($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <select name="only_free" class="form-select">
                    <option value="">Весь инвентарь</option>
                    <option value="1" <?php echo $f_only_free ? 'selected' : ''; ?>>Только свободный</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Найти</button>
                <a href="/" class="btn btn-outline-secondary w-100">Сброс</a>
            </div>
        </form>
    </div>

    <h2 class="mb-4">Каталог инвентаря</h2>

    <div class="row">
        <?php if (empty($items)): ?>
            <div class="col-12"><p class="text-muted">Инвентарь пока отсутствует.</p></div>
        <?php endif; ?>

        <?php foreach ($items as $item):
            $isArchived = ($item['status'] === 'archived');
            $img = !empty($item['image_url'])
                ? '/' . e($item['image_url'])
                : 'https://via.placeholder.com/300x200/3D9970/FFFFFF?text=Инвентарь';
        ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, \'UTF-8\'); ?>" class="card-img-top img-fluid" alt="Фото инвентаря"
                     style="height:200px;object-fit:contain;">
                <div class="card-body">
                    <h5 class="card-title">
                        <?php echo e($item['name']); ?>
                        <?php if (isAdmin() && $isArchived): ?>
                            <span class="badge bg-danger">Архив</span>
                        <?php endif; ?>
                    </h5>
                    <div class="mb-2 small text-muted">
                        <div>Вид спорта: <strong><?php echo e($item['sport']); ?></strong></div>
                        <div>Категория: <strong><?php echo e($item['category']); ?></strong></div>
                        <div>Тип: <strong><?php echo e($item['type']); ?></strong></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <?php if (!empty($item['price_per_hour'])): ?>
                            <span class="badge bg-success-subtle text-success border fs-6 px-3 py-2">Час: <?php echo (int)$item['price_per_hour']; ?> ₽</span>
                        <?php endif; ?>
                        <?php if (!empty($item['price_per_day'])): ?>
                            <span class="badge bg-primary-subtle text-primary border fs-6 px-3 py-2">День: <?php echo (int)$item['price_per_day']; ?> ₽</span>
                        <?php endif; ?>
                    </div>
                    <p class="card-text">
                        <?php
                        $desc  = $item['description'];
                        $short = mb_substr($desc, 0, 100, 'UTF-8');
                        echo e($short);
                        if (mb_strlen($desc, 'UTF-8') > 100) {
                            echo '... <a href="/rent?id=' . (int)$item['id'] . '">Подробнее</a>';
                        }
                        ?>
                    </p>
                    <p class="card-text fw-bold">Статус:
                        <?php if ($item['status'] === 'free'): ?>
                            <span class="badge bg-success">✅ Свободен</span>
                        <?php elseif ($item['status'] === 'busy'): ?>
                            <span class="badge bg-secondary">⏳ Занят</span>
                        <?php elseif ($isArchived): ?>
                            <span class="badge bg-danger">Архив</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <?php if ($isArchived): ?>
                        <button class="btn btn-secondary w-100" disabled>Недоступно</button>
                    <?php elseif ($item['status'] === 'free'): ?>
                        <?php if (isLoggedIn()): ?>
                            <a href="/rent?id=<?php echo (int)$item['id']; ?>" class="btn btn-primary w-100">Арендовать</a>
                        <?php else: ?>
                            <a href="/login" class="btn btn-outline-primary w-100">Войдите для аренды</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100" disabled>Занят</button>
                    <?php endif; ?>

                    <?php if (isAdmin()): ?>
                        <a href="/admin/edit-item?id=<?php echo (int)$item['id']; ?>" class="btn btn-warning w-100 mt-2">Редактировать</a>
                        <?php if (!$isArchived): ?>
                        <form method="POST" action="/admin/delete-item" onsubmit="return confirm('Отправить в архив?');">
                            <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                            <button type="submit" class="btn btn-danger w-100 mt-2">Удалить</button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($filterGet, array('page' => max(1, $currentPage - 1)))); ?>">«</a>
            </li>
            <?php
            $start = max(1, $currentPage - 2);
            $end   = min($totalPages, $currentPage + 2);
            if ($start > 1) {
                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($filterGet, array('page' => 1))) . '">1</a></li>';
                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?php echo $p === $currentPage ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filterGet, array('page' => $p))); ?>"><?php echo htmlspecialchars($p, ENT_QUOTES, \'UTF-8\'); ?></a>
                </li>
            <?php endfor;
            if ($end < $totalPages) {
                if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($filterGet, array('page' => $totalPages))) . '">' . $totalPages . '</a></li>';
            }
            ?>
            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($filterGet, array('page' => min($totalPages, $currentPage + 1)))); ?>">»</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<?php require ROOT . '/views/layout/footer.php'; ?>