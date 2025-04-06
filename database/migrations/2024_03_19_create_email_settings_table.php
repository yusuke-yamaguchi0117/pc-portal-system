<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('from_name')->comment('差出人名');
            $table->string('from_email')->comment('送信元メールアドレス');
            $table->string('reply_to')->comment('返信先メールアドレス');
            $table->string('smtp_host')->comment('SMTPホスト');
            $table->integer('smtp_port')->comment('SMTPポート');
            $table->string('smtp_user')->comment('SMTPユーザー名');
            $table->string('smtp_pass')->comment('SMTPパスワード（暗号化）');
            $table->enum('encryption', ['ssl', 'tls', 'none'])->default('tls')->comment('暗号化方式');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};