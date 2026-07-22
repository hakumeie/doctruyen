<?php
require_once 'config.php';
$mangaList = list_manga();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(SITE_NAME) ?> - Trang chủ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require_once $_SERVER['DOCUMENT_ROOT'].'/inc/header.php'; ?>
    <div id="content">
        <div class="crumb">Đọc truyện &raquo; Truyện (<?= count($mangaList) ?>)</div>
        <?php if (empty($mangaList)): ?>
            <div class="empty-msg">
                Chưa có truyện nào.
            </div>
        <?php else: ?>
            <div class="manga-grid">
            <?php foreach ($mangaList as $m):
                $cover = get_cover($m);
                $chapters = list_chapters($m);
            ?>
                <div class="manga-box">
                    <a href="manga.php?m=<?= urlencode($m) ?>">
                        <img class="manga-cover" src="<?= $cover ? e($cover) : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBTAA7' ?>" alt="cover">
                        <span class="title"><?= e($m) ?></span>
                    </a>
                    <div class="count"><?= count($chapters) ?> chương</div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php require_once $_SERVER['DOCUMENT_ROOT'].'/inc/footer.php'; ?>
</body>
</html>
