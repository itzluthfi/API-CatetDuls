# 🔐 Implementasi Forgot Password dengan Kode Verifikasi 6 Digit

## ✅ Yang Sudah Diimplementasikan

### 1. **Database**

-   ✅ Migration `password_reset_codes` table dengan kolom:
    -   `id` - Primary key
    -   `email` - Email user
    -   `code` - Kode verifikasi 6 digit
    -   `is_verified` - Status verifikasi
    -   `expires_at` - Waktu kadaluarsa (15 menit)
    -   `created_at` & `updated_at` - Timestamps
    -   Index untuk performa query

### 2. **Model**

-   ✅ `PasswordResetCode` model dengan:
    -   Fillable fields
    -   Casts untuk datetime dan boolean
    -   Helper methods:
        -   `isExpired()` - Cek apakah kode sudah kadaluarsa
        -   `isValid()` - Cek apakah kode masih valid

### 3. **Email System**

-   ✅ `PasswordResetCodeMail` - Mailable class
-   ✅ Email template (`resources/views/emails/password-reset-code.blade.php`)
    -   Design modern dengan gradient
    -   Kode 6 digit yang jelas
    -   Informasi expiry time
    -   Warning message
    -   Responsive design

### 4. **API Endpoints**

#### a. **POST /api/auth/forgot-password**

-   Request kode verifikasi
-   Generate kode 6 digit random
-   Hapus kode lama untuk email yang sama
-   Simpan kode baru dengan expiry 15 menit
-   Kirim email ke user

#### b. **POST /api/auth/verify-reset-code** (Opsional)

-   Verifikasi kode yang diinput user
-   Validasi kode belum expired
-   Validasi kode belum digunakan
-   Mark kode sebagai verified

#### c. **POST /api/auth/reset-password**

-   Reset password dengan kode yang sudah diverifikasi
-   Validasi kode dan expiry
-   Update password user
-   Hapus semua kode reset untuk email tersebut
-   Force logout dari semua device

### 5. **Routes**

-   ✅ Semua route sudah ditambahkan di `routes/api.php`
-   ✅ Public routes (tidak perlu authentication)

### 6. **Dokumentasi**

-   ✅ `FORGOT_PASSWORD_API.md` - Dokumentasi lengkap API
-   ✅ `.env.example` - Updated dengan konfigurasi email

---

## 🚀 Cara Menggunakan

### Setup Email (Wajib)

1. **Buka file `.env`** dan konfigurasi email:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="CatetDuls"
```

2. **Untuk Gmail:**

    - Aktifkan 2-Factor Authentication
    - Generate App Password di Google Account Settings
    - Gunakan App Password, bukan password akun biasa

3. **Test email configuration:**

```bash
php artisan tinker
Mail::raw('Test email', function($message) {
    $message->to('test@example.com')->subject('Test');
});
```

---

## 📱 Flow untuk Mobile App

### Recommended Flow (3 Steps):

```
1. Forgot Password Screen
   ↓
   User input email
   ↓
   POST /api/auth/forgot-password
   ↓

2. Verify Code Screen
   ↓
   User input 6-digit code from email
   ↓
   POST /api/auth/verify-reset-code (optional validation)
   ↓

3. New Password Screen
   ↓
   User input new password & confirmation
   ↓
   POST /api/auth/reset-password
   ↓

4. Login Screen
```

### Simplified Flow (2 Steps):

```
1. Forgot Password Screen
   ↓
   User input email
   ↓
   POST /api/auth/forgot-password
   ↓

2. Reset Password Screen
   ↓
   User input code + new password
   ↓
   POST /api/auth/reset-password
   ↓

3. Login Screen
```

---

## 🧪 Testing

### 1. Test dengan Postman/Thunder Client

**Step 1: Request Code**

```http
POST http://localhost:8000/api/auth/forgot-password
Content-Type: application/json

{
  "email": "test@example.com"
}
```

**Step 2: Cek Email**

-   Buka email yang didaftarkan
-   Salin kode 6 digit

**Step 3: Verify Code (Optional)**

```http
POST http://localhost:8000/api/auth/verify-reset-code
Content-Type: application/json

{
  "email": "test@example.com",
  "code": "123456"
}
```

**Step 4: Reset Password**

```http
POST http://localhost:8000/api/auth/reset-password
Content-Type: application/json

{
  "email": "test@example.com",
  "code": "123456",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### 2. Test dengan Laravel Tinker

```php
php artisan tinker

// Test generate code
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
echo $code;

// Test create reset code
$reset = \App\Models\PasswordResetCode::create([
    'email' => 'test@example.com',
    'code' => '123456',
    'expires_at' => \Carbon\Carbon::now()->addMinutes(15),
    'is_verified' => false,
]);

// Test send email
$user = \App\Models\User::first();
\Mail::to($user->email)->send(new \App\Mail\PasswordResetCodeMail('123456', $user->name));
```

---

## 📋 Checklist untuk Mobile App Developer

### UI/UX yang Perlu Dibuat:

-   [ ] **Forgot Password Screen**

    -   Input field untuk email
    -   Button "Kirim Kode"
    -   Loading state saat request
    -   Success message dengan instruksi cek email

-   [ ] **Verify Code Screen**

    -   6 input boxes untuk kode (atau 1 input field)
    -   Timer countdown 15 menit
    -   Button "Verifikasi"
    -   Button "Kirim Ulang Kode"
    -   Error handling untuk kode salah/expired

-   [ ] **New Password Screen**
    -   Input field untuk password baru
    -   Input field untuk konfirmasi password
    -   Password strength indicator
    -   Show/hide password toggle
    -   Button "Reset Password"

### Error Handling:

-   [ ] Email tidak terdaftar
-   [ ] Kode verifikasi salah
-   [ ] Kode sudah kadaluarsa (15 menit)
-   [ ] Kode sudah digunakan
-   [ ] Password tidak match dengan confirmation
-   [ ] Network error
-   [ ] Server error

### State Management:

-   [ ] Store email setelah request code
-   [ ] Store verified status
-   [ ] Handle loading states
-   [ ] Handle error states
-   [ ] Clear state after success

---

## 🔒 Security Features

1. **Kode Random 6 Digit** - Sulit ditebak
2. **Expiry Time 15 Menit** - Membatasi waktu penggunaan
3. **One-time Use** - Kode hanya bisa digunakan sekali
4. **Auto Delete Old Codes** - Kode lama dihapus saat request baru
5. **Force Logout** - Semua token dihapus setelah reset password
6. **Email Verification** - Hanya pemilik email yang bisa reset

---

## 📝 Notes

-   Kode verifikasi valid selama **15 menit**
-   Kode hanya bisa digunakan **1 kali**
-   Setelah reset password, user harus login ulang
-   Email template sudah responsive dan modern
-   Semua endpoint sudah handle error dengan baik

---

## 🐛 Troubleshooting

### Email tidak terkirim?

1. Cek konfigurasi MAIL di `.env`
2. Pastikan menggunakan App Password untuk Gmail
3. Cek log di `storage/logs/laravel.log`
4. Test dengan `MAIL_MAILER=log` untuk development

### Kode tidak valid?

1. Pastikan kode belum expired (15 menit)
2. Pastikan kode belum digunakan
3. Cek database table `password_reset_codes`

### Migration error?

```bash
php artisan migrate:fresh  # WARNING: This will delete all data
# atau
php artisan migrate:rollback
php artisan migrate
```

---

## 📞 Support

Jika ada pertanyaan atau issue, silakan hubungi developer atau buat issue di repository.

---

**Happy Coding! 🚀**
