# === Hipnolink Custom Access Login ===
Contributors: hipnolinkteam
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistem login halaman custom untuk WordPress dengan user mandiri, session khusus, dan redirect berbeda untuk setiap user. Dibuat oleh Hipnolink Team.

== Description ==

Hipnolink Custom Access Login adalah plugin WordPress yang memungkinkan Anda untuk membuat sistem login mandiri yang sepenuhnya terpisah dari sistem user WordPress bawaan (`wp-login.php`). Plugin ini sangat berguna untuk membuat "Client Area" atau halaman rahasia dengan akses yang diberikan secara manual oleh Administrator.

Fitur Utama:
*   Sistem user mandiri tanpa membuat user WordPress.
*   Login melalui shortcode di halaman mana saja.
*   Redirect URL yang dapat disesuaikan untuk setiap pengguna.
*   Keamanan maksimal dengan token dan batasan percobaan login (Login Attempt Limit).
*   Proteksi halaman mudah melalui meta box atau shortcode.

== Installation ==

1. Upload folder `hipnolink-custom-access-login` ke direktori `/wp-content/plugins/`.
2. Aktifkan plugin melalui menu 'Plugins' di WordPress.
3. Masuk ke menu 'Hipnolink Access' -> 'Settings' untuk mengatur URL Halaman Login.
4. Tambahkan user baru di 'Hipnolink Access' -> 'Add New User'.
5. Gunakan shortcode `[hipnolink_access_login]` pada halaman yang Anda inginkan.

== Shortcodes ==

1. `[hipnolink_access_login]` : Menampilkan form login.
2. `[hipnolink_access_logout]` : Menampilkan link logout.
3. `[hipnolink_access_protected] Konten Rahasia [/hipnolink_access_protected]` : Menyembunyikan konten untuk user yang belum login.

== Security ==

- Password disimpan dengan enkripsi `password_hash()`.
- Tidak menggunakan cookies login WordPress default, melainkan token acak 64-karakter dengan session mandiri (transient).
- Form diproteksi dengan Nonce WordPress.
