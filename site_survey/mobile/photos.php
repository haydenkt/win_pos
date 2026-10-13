<?php
require_once __DIR__.'/../../includes/permissions.php';
if (hasPermission('site_survey_view')) {
    $photoQuery=$conn->prepare('SELECT id FROM survey_mobile_photos WHERE survey_id=? ORDER BY id');
    $photoQuery->execute([(int)$id]);
    $surveyPhotos=$photoQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    if ($surveyPhotos): ?>
    <section class="card my-4"><div class="card-header"><h2 class="h5 mb-0">Site photos</h2></div><div class="card-body d-flex flex-wrap gap-3">
    <?php foreach ($surveyPhotos as $photo): ?>
        <a href="mobile/photo.php?id=<?=(int)$photo['id'];?>" target="_blank" rel="noopener"><img src="mobile/photo.php?id=<?=(int)$photo['id'];?>" alt="Site survey photo" loading="lazy" style="width:200px;max-width:100%;height:160px;object-fit:cover;border-radius:10px"></a>
    <?php endforeach; ?>
    </div></section>
    <?php endif;
}
