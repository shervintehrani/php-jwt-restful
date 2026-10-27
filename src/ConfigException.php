<?php
declare(strict_types=1);

namespace App;

/** خطای پیکربندی سرور (مثلاً JWT_SECRET تنظیم نشده)؛ جدا از خطاهای نامعتبر بودن توکن */
final class ConfigException extends \RuntimeException
{
}
