<?php

namespace App\Support;

final class FormatLaporan
{
    public static function rupiah(mixed $nilai): string
    {
        return 'Rp '.number_format((float) $nilai, 0, ',', '.');
    }

    public static function meter(mixed $nilai): string
    {
        return number_format((float) $nilai, 2, ',', '.');
    }

    public static function persen(mixed $nilai): string
    {
        return number_format((float) $nilai, 1, ',', '.').'%';
    }
}
