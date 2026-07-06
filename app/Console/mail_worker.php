<?php

declare(strict_types=1);

/*
 * Воркер очереди почты ArtStudio CMS.
 *   php app/Console/mail_worker.php
 *
 * Запускать по Cron (например, каждую минуту):
 *   * * * * * php /path/to/app/Console/mail_worker.php >> /path/to/storage/logs/mail_worker.log 2>&1
 *
 * Забирает pending-письма из mail_queue и отправляет их через нативный SMTP
 * (App\Core\Mailer). При ошибке увеличивает счётчик попыток; после трёх
 * неудач письмо помечается как failed.
 */

require __DIR__ . '/../Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../Core/bootstrap.php';

\App\Core\Heartbeat::touch('mail'); // группа 2.1

use App\Core\Mailer;
use App\Models\MailQueue;

if (!Mailer::isConfigured()) {
    fwrite(STDERR, 'SMTP не настроен (config[mail][host] пуст). Нечего отправлять.' . PHP_EOL);
    exit(0);
}

$batch = MailQueue::pendingBatch(20);
if ($batch === []) {
    fwrite(STDOUT, 'Очередь пуста.' . PHP_EOL);
    exit(0);
}

$mailer = new Mailer();
$sent = 0;
$failed = 0;

foreach ($batch as $item) {
    $id = (int) $item['id'];
    $ok = $mailer->send(
        (string) $item['to_email'],
        (string) $item['subject'],
        (string) $item['body'],
        $item['to_name'] !== null ? (string) $item['to_name'] : null
    );

    if ($ok) {
        MailQueue::markSent($id);
        $sent++;
    } else {
        MailQueue::markFailed($id, 'SMTP send returned false');
        $failed++;
    }
}

fwrite(STDOUT, sprintf('Обработано: отправлено %d, ошибок %d.%s', $sent, $failed, PHP_EOL));
