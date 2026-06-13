<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
    <div style="background: #0d6efd; color: #fff; padding: 20px; text-align: center;">
        <h2 style="margin: 0;">SocialPilot AI</h2>
    </div>
    <div style="padding: 30px;">
        <p>Merhaba <?= Security::escape($name ?? '') ?>,</p>
        <h3><?= Security::escape($title ?? '') ?></h3>
        <p><?= nl2br(Security::escape($message ?? '')) ?></p>
    </div>
    <div style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;">
        &copy; <?= date('Y') ?> SocialPilot AI
    </div>
</div>
</body>
</html>
