<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) : 'SibGo — Прокат инвентаря'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <?php if (isset($css)): ?>
        <?php foreach ((array)$css as $file): ?>
            <link rel="stylesheet" href="/css/<?php echo e($file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>