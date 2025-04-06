@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">お問い合わせメール送信設定</h5>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form action="{{ route('admin.email-settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="from_name" class="form-label">差出人名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('from_name') is-invalid @enderror" id="from_name" name="from_name" value="{{ old('from_name', $settings->from_name) }}" required>
                                @error('from_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="from_email" class="form-label">送信元メールアドレス <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('from_email') is-invalid @enderror" id="from_email" name="from_email" value="{{ old('from_email', $settings->from_email) }}" required>
                                @error('from_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="reply_to" class="form-label">返信先メールアドレス <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('reply_to') is-invalid @enderror" id="reply_to" name="reply_to" value="{{ old('reply_to', $settings->reply_to) }}" required>
                                @error('reply_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="smtp_host" class="form-label">SMTPホスト <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('smtp_host') is-invalid @enderror" id="smtp_host" name="smtp_host" value="{{ old('smtp_host', $settings->smtp_host) }}" required>
                                @error('smtp_host')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="smtp_port" class="form-label">SMTPポート <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('smtp_port') is-invalid @enderror" id="smtp_port" name="smtp_port" value="{{ old('smtp_port', $settings->smtp_port) }}" required min="1" max="65535">
                                @error('smtp_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">一般的なポート番号: 587 (TLS), 465 (SSL)</div>
                            </div>

                            <div class="mb-3">
                                <label for="smtp_user" class="form-label">SMTPユーザー名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('smtp_user') is-invalid @enderror" id="smtp_user" name="smtp_user" value="{{ old('smtp_user', $settings->smtp_user) }}" required>
                                @error('smtp_user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="smtp_pass" class="form-label">SMTPパスワード <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('smtp_pass') is-invalid @enderror" id="smtp_pass" name="smtp_pass" value="{{ old('smtp_pass', $settings->smtp_pass) }}" required>
                                @error('smtp_pass')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">暗号化方式 <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="encryption" id="encryption_ssl" value="ssl" {{ old('encryption', $settings->encryption) === 'ssl' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="encryption_ssl">SSL</label>

                                    <input type="radio" class="btn-check" name="encryption" id="encryption_tls" value="tls" {{ old('encryption', $settings->encryption) === 'tls' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="encryption_tls">TLS</label>

                                    <input type="radio" class="btn-check" name="encryption" id="encryption_none" value="none" {{ old('encryption', $settings->encryption) === 'none' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="encryption_none">なし</label>
                                </div>
                                @error('encryption')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">設定を保存</button>

                                <div class="d-flex align-items-center gap-2">
                                    <input type="email" class="form-control" id="test_email" placeholder="テスト送信先メールアドレス">
                                    <button type="button" class="btn btn-outline-primary" id="sendTestBtn">テスト送信</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('sendTestBtn').addEventListener('click', function () {
                const testEmail = document.getElementById('test_email').value;
                if (!testEmail) {
                    alert('テスト送信先メールアドレスを入力してください。');
                    return;
                }

                this.disabled = true;
                this.textContent = '送信中...';

                fetch('{{ route('admin.email-settings.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ test_email: testEmail })
                })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('エラーが発生しました。');
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.textContent = 'テスト送信';
                    });
            });
        </script>
    @endpush
@endsection