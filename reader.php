<?php
require_once 'config.php';

$manga = isset($_GET['m']) ? basename($_GET['m']) : '';
$chapter = isset($_GET['c']) ? basename($_GET['c']) : '';

if ($manga === '' || $chapter === '' || !is_dir(MANGA_DIR . '/' . $manga . '/' . $chapter)) {
    header('Location: index.php');
    exit;
}

$chapters = list_chapters($manga);
$pages = list_pages($manga, $chapter);

$idx = array_search($chapter, $chapters);
$prevChapter = ($idx !== false && $idx > 0) ? $chapters[$idx - 1] : null;
$nextChapter = ($idx !== false && $idx < count($chapters) - 1) ? $chapters[$idx + 1] : null;

// Danh sách ảnh của chương kế tiếp, dùng để preload
$nextPages = $nextChapter ? list_pages($manga, $nextChapter) : [];
$nextPageUrls = array_map(function ($p) use ($manga, $nextChapter) {
    return MANGA_URL . '/' . rawurlencode($manga) . '/' . rawurlencode($nextChapter) . '/' . rawurlencode($p);
}, $nextPages);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($manga) ?> - <?= e($chapter) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require_once $_SERVER['DOCUMENT_ROOT'].'/inc/header.php'; ?>
    <div id="content">
        <div class="crumb">
            <a href="/">Đọc truyện</a> &raquo; <a href="manga.php?m=<?= urlencode($manga) ?>"><?= e($manga) ?></a> &raquo; <?= e($chapter) ?>
        </div>
    </div>
    <div style="max-width:960px;margin:8px auto 0;padding:0 15px;">
        <a class="nav-link" href="manga.php?m=<?= urlencode($manga) ?>">&laquo; Danh sách chương</a>
    </div>
    <div style="max-width:960px;margin:0 auto;padding:0 5px;" id="pages-wrap">
        <div class="page-nav page-nav-top" style="text-align:center; margin:10px 0;">
            <?php if ($prevChapter): ?>
                <a class="nav-link" href="reader.php?m=<?= urlencode($manga) ?>&c=<?= urlencode($prevChapter) ?>">&lsaquo; Chương trước</a>
            <?php endif; ?>
            <?php if ($nextChapter): ?>
                <a class="nav-link" href="reader.php?m=<?= urlencode($manga) ?>&c=<?= urlencode($nextChapter) ?>">Chương sau &rsaquo;</a>
            <?php endif; ?>
        </div>
        <div id="pages">
        <?php if (empty($pages)): ?>
            <p style="color:#fff; padding:20px;">Chương này chưa có ảnh trang nào.</p>
        <?php else: ?>
            <?php foreach ($pages as $p): ?>
                <img src="<?= MANGA_URL . '/' . rawurlencode($manga) . '/' . rawurlencode($chapter) . '/' . rawurlencode($p) ?>" alt="page">
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
        <div class="page-nav page-nav-bottom" style="text-align:center; margin:10px 0;">
            <?php if ($prevChapter): ?>
                <a class="nav-link" href="reader.php?m=<?= urlencode($manga) ?>&c=<?= urlencode($prevChapter) ?>">&lsaquo; Chương trước</a>
            <?php endif; ?>
            <?php if ($nextChapter): ?>
                <a class="nav-link" href="reader.php?m=<?= urlencode($manga) ?>&c=<?= urlencode($nextChapter) ?>">Chương sau &rsaquo;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php require_once $_SERVER['DOCUMENT_ROOT'].'/inc/footer.php'; ?>
    <div id="reader-bar" class="fixed" aria-hidden="false">
        <div class="container">
            <?php if ($prevChapter): ?>
                <a class="nav-link" href="reader.php?m=<?= urlencode($manga) ?>&c=<?= urlencode($prevChapter) ?>">&lsaquo;</a>
            <?php else: ?>
                <span style="width:38px;display:inline-block;"></span>
            <?php endif; ?>

            <select onchange="location = 'reader.php?m=<?= urlencode($manga) ?>&c=' + encodeURIComponent(this.value);">
                <?php foreach ($chapters as $c): ?>
                    <option value="<?= e($c) ?>" <?= $c === $chapter ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>

            <?php if ($nextChapter): ?>
                <a class="nav-link" href="reader.php?m=<?= urlencode($manga) ?>&c=<?= urlencode($nextChapter) ?>">&rsaquo;</a>
            <?php else: ?>
                <span style="width:38px;display:inline-block;"></span>
            <?php endif; ?>
        </div>
    </div>
    <script>
    (function(){
        const readerBar = document.getElementById('reader-bar');
        const pagesWrap = document.getElementById('pages-wrap');
        const pages = document.getElementById('pages');
        let lastScroll = window.scrollY || 0;
        const isLastChapter = <?= $nextChapter ? 'false' : 'true' ?>;

        // --- preload ảnh chương sau (chỉ khi ảnh chương hiện tại đã load xong) ---
        const nextPageUrls = <?= json_encode(array_values($nextPageUrls)) ?>;
        const currentImgs = Array.from(pages ? pages.querySelectorAll('img') : []);
        let preloadStarted = false;
        let currentChapterLoaded = currentImgs.length === 0;
        let pendingPreloadRequest = false;

        function countLoadedImages(){
            return currentImgs.filter(function (img) {
                return img.complete && img.naturalWidth > 0;
            }).length;
        }

        function markCurrentLoadedIfReady(){
            if (currentChapterLoaded) return;
            if (countLoadedImages() >= currentImgs.length) {
                currentChapterLoaded = true;
                // Nếu người dùng đã cuộn tới gần cuối trong lúc ảnh còn đang tải, preload ngay bây giờ
                if (pendingPreloadRequest) preloadNextChapter();
            }
        }

        currentImgs.forEach(function (img) {
            if (img.complete) {
                markCurrentLoadedIfReady();
            } else {
                img.addEventListener('load', markCurrentLoadedIfReady);
                img.addEventListener('error', markCurrentLoadedIfReady); // tránh treo nếu 1 ảnh lỗi
            }
        });

        function preloadNextChapter(){
            if (preloadStarted || nextPageUrls.length === 0) return;
            if (!currentChapterLoaded) {
                // Ảnh chương hiện tại chưa tải xong hết -> chờ, sẽ tự trigger lại khi load xong
                pendingPreloadRequest = true;
                return;
            }
            // Bỏ qua preload nếu người dùng đang bật chế độ tiết kiệm dữ liệu
            if (navigator.connection && navigator.connection.saveData) return;
            preloadStarted = true;
            nextPageUrls.forEach(function (src) {
                const img = new Image();
                img.src = src;
            });
        }
        // ---------------------------------------------------------------------

        function checkPosition(){
            const atBottom = (window.innerHeight + window.scrollY) >= (document.body.scrollHeight - 20);
            const nearBottom = (window.innerHeight + window.scrollY) >= (document.body.scrollHeight - 1500);

            if (nearBottom) preloadNextChapter();

            if (isLastChapter && atBottom) {
                if (readerBar.classList.contains('fixed')) {
                    readerBar.classList.remove('fixed');
                    readerBar.classList.add('static-after-pages');
                    pagesWrap.parentNode.insertBefore(readerBar, pagesWrap.nextSibling);
                    readerBar.classList.remove('hidden');
                }
            } else {
                if (!readerBar.classList.contains('fixed')) {
                    document.body.appendChild(readerBar);
                    readerBar.classList.remove('static-after-pages');
                    readerBar.classList.add('fixed');
                }
            }
        }
        window.addEventListener('scroll', function(){
            const st = window.scrollY || window.pageYOffset;
            if (st > lastScroll && st > 100) {
                if (readerBar.classList.contains('fixed')) readerBar.classList.add('hidden');
            } else {
                readerBar.classList.remove('hidden');
            }
            lastScroll = st <= 0 ? 0 : st;
            checkPosition();
        }, {passive:true});
        window.addEventListener('resize', checkPosition);
        document.addEventListener('keydown', function(e){
            <?php if ($prevChapter): ?>
            if (e.key === 'ArrowLeft') location.href = 'reader.php?m=<?= urlencode($manga) ?>&c=<?= urlencode($prevChapter) ?>';
            <?php endif; ?>
            <?php if ($nextChapter): ?>
            if (e.key === 'ArrowRight') location.href = 'reader.php?m=<?= urlencode($manga) ?>&c=<?= urlencode($nextChapter) ?>';
            <?php endif; ?>
        });
        checkPosition();
    })();
    </script>
</body>
</html>