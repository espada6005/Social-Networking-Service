<?php
$success = \Response\FlashData::getFlashData("success");
$error = \Response\FlashData::getFlashData("error");
?>

<div class="position-fixed top-0 start-0 end-0 z-3">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>
