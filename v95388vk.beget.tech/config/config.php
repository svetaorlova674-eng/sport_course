<?php
// Статусы инвентаря
define('STATUS_FREE', 'free');
define('STATUS_BUSY', 'busy'); 
define('STATUS_ARCHIVED', 'archived');

// Массив доступных статусов
$validStatuses = [STATUS_FREE, STATUS_BUSY, STATUS_ARCHIVED];