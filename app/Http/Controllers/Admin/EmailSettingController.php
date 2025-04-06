<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;

class EmailSettingController extends Controller
{
    /**
     * 設定画面を表示
     */
    public function index()
    {
        $settings = EmailSetting::first() ?? new EmailSetting();
        return view('admin.email_settings.index', compact('settings'));
    }

    /**
     * 設定を更新
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'reply_to' => 'required|email|max:255',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|between:1,65535',
            'smtp_user' => 'required|string|max:255',
            'smtp_pass' => 'required|string|max:255',
            'encryption' => 'required|in:ssl,tls,none',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settings = EmailSetting::first() ?? new EmailSetting();
        $settings->fill($request->all());
        $settings->save();

        return redirect()->back()->with('success', 'メール設定を保存しました。');
    }

    /**
     * テストメールを送信
     */
    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '有効なメールアドレスを入力してください。'
            ]);
        }

        try {
            $settings = EmailSetting::first();
            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'メール設定が保存されていません。'
                ]);
            }

            // メール設定を一時的に変更
            Config::set('mail.mailers.smtp.host', $settings->smtp_host);
            Config::set('mail.mailers.smtp.port', $settings->smtp_port);
            Config::set('mail.mailers.smtp.username', $settings->smtp_user);
            Config::set('mail.mailers.smtp.password', $settings->smtp_pass);
            Config::set('mail.mailers.smtp.encryption', $settings->encryption === 'none' ? null : $settings->encryption);
            Config::set('mail.from.address', $settings->from_email);
            Config::set('mail.from.name', $settings->from_name);

            // テストメール送信
            Mail::raw('これはテストメールです。', function ($message) use ($request, $settings) {
                $message->to($request->test_email)
                    ->subject('メール設定テスト')
                    ->replyTo($settings->reply_to);
            });

            return response()->json([
                'success' => true,
                'message' => 'テストメールを送信しました。'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'テストメール送信に失敗しました: ' . $e->getMessage()
            ]);
        }
    }
}