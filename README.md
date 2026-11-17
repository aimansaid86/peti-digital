# Peti Digital

Portal fail kongsi jabatan untuk WordPress: halaman utama, pengurus fail, tong sampah, pengurusan pengguna dan log aktiviti, dalam **Bahasa Melayu dan English**.

*A shared department file portal for WordPress (home dashboard, file manager, trash, user management and activity log), in **Malay and English**.*

## Ciri / Features

- Pengurus fail: muat naik (seret & lepas), muat turun (fail atau zip), folder, nama semula, carian, pratonton gambar / PDF / video / audio / teks.
- Tong sampah dengan pemulihan ke lokasi asal dan pemadaman automatik selepas N hari.
- Pengguna dan peranan: Staf (urus fail) dan Pentadbir Portal (urus fail, pengguna & tetapan).
- Log aktiviti: log masuk, muat naik, muat turun, padam, pulih dan lain-lain.
- Dwibahasa: setiap pengguna memilih Bahasa Melayu atau English; pentadbir menetapkan bahasa lalai.
- Keselamatan: fail disimpan di luar capaian terus (dihantar melalui PHP), had cubaan log masuk, jenis fail berbahaya disekat.
- Halaman log masuk berjenama sendiri; tiada langkah binaan (PHP + JavaScript biasa).

## Keperluan / Requirements

- WordPress 6.0+
- PHP 8.0+ dengan sambungan `zip` (untuk muat turun zip)
- Apache dengan `.htaccess` dibenarkan, atau sekat capaian ke folder storan secara manual (nginx)

## Pemasangan / Installation

1. Salin folder ini ke `wp-content/plugins/peti-digital/` (atau muat naik sebagai zip melalui *Plugins → Add New → Upload Plugin*).
2. Aktifkan **Peti Digital** dalam *Plugins*.
3. Buka `/peti-digital/` pada laman anda.

Laluan portal / Portal routes: `/peti-digital/` (aplikasi), `/peti-digital/log-masuk/` (log masuk).

Fail disimpan dalam `wp-content/uploads/file-manager/PetiDigital` (boleh ditukar melalui tetapan `storage_root`).

## Terjemahan / Translations

Teks sumber ditulis dalam Bahasa Melayu terus dalam kod. Terjemahan English ada dalam [`languages/en.php`](languages/en.php), dikongsi oleh PHP dan JavaScript.

- PHP: `pd__( 'Teks' )`, `pd_e( 'Teks' )`, atau mesej `PD_Exception`.
- JavaScript (`assets/app.js`): `t('Teks')`.
- Pemegang tempat: `t('{n} fail', { n: 3 })`. Bentuk tunggal/jamak dalam kamus: `array( '{n} file', '{n} files' )`.
- Teks BM yang sama tetapi maksud English berbeza: tambah akhiran konteks, cth `t('Pengguna@lajur')`.
- Teks baharu yang belum ada dalam kamus dipaparkan dalam Bahasa Melayu.

Bahasa ditukar melalui `?pd_lang=en` atau `?pd_lang=ms` pada mana-mana laluan portal.

## Lesen / License

GPL-2.0-or-later
