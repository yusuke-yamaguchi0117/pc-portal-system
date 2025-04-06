<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EmailSetting extends Model
{
    protected $fillable = [
        'from_name',
        'from_email',
        'reply_to',
        'smtp_host',
        'smtp_port',
        'smtp_user',
        'smtp_pass',
        'encryption'
    ];

    /**
     * パスワードを暗号化して保存
     */
    public function setSmtpPassAttribute($value)
    {
        $this->attributes['smtp_pass'] = Crypt::encryptString($value);
    }

    /**
     * パスワードを復号化して取得
     */
    public function getSmtpPassAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '';
        }
    }
}