<?php
require_once 'config.php';

$manga = isset($_GET['m']) ? $_GET['m'] : '';
$manga = basename($manga); // chống path traversal

if ($manga === '' || !is_dir(MANGA_DIR . '/' . $manga)) {
    header('Location: index.php');
    exit;
}

$chapters = list_chapters($manga);
$cover = get_cover($manga);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($manga) ?> - <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require_once $_SERVER['DOCUMENT_ROOT'].'/inc/header.php'; ?>
    <div id="content">
        <div class="crumb">
            <a href="/">Đọc truyện</a> &raquo; <?= e($manga) ?>
        </div>
        <table style="width:100%; margin-bottom:15px;">
            <tr>
                <td style="width:140px; vertical-align:top;">
                    <img class="manga-cover-large" src="<?= $cover ? e($cover) : '' ?>" alt="cover">
                </td>
                <td style="vertical-align:top; padding-left:15px;">
                    <h2 style="margin-top:0;"><?= e($manga) ?></h2>
                    <p><?= count($chapters) ?> chương</p>
                </td>
            </tr>
        </table>
        <?php if (empty($chapters)): ?>
            <div class="empty-msg">
                Chưa có chương nào.<br>
            </div>
        <?php else: ?>
            <table class="list">
                <tr><th>#</th><th>Chương</th><th>Số trang</th></tr>
                <?php foreach ($chapters as $i => $c):
                    $pages = list_pages($manga, $c);
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><a href="reader.php?m=<?= urlencode($manga) ?>&c=<?= urlencode($c) ?>"><?= e($c) ?></a></td>
                    <td><?= count($pages) ?> trang</td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <?php require_once $_SERVER['DOCUMENT_ROOT'].'/inc/footer.php'; ?>
</body>
</html>
