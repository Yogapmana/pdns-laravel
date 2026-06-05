# DESIGN.md — Aplikasi Pengolahan Nilai Siswa

> **Stack:** Tailwind CSS · Alpine.js · Livewire · Laravel  
> **Versi:** 1.0.0 | **Last updated:** 2025

---

## Daftar Isi

1. [Prinsip Desain](#1-prinsip-desain)
2. [Design Tokens](#2-design-tokens)
3. [Tipografi](#3-tipografi)
4. [Komponen](#4-komponen)
5. [Layout & Struktur Halaman](#5-layout--struktur-halaman)
6. [Wireframe per Halaman](#6-wireframe-per-halaman)
7. [State & Interaksi](#7-state--interaksi)
8. [Aksesibilitas](#8-aksesibilitas)
9. [Checklist Implementasi](#9-checklist-implementasi)

---

## 1. Prinsip Desain

| # | Prinsip | Penjelasan |
|---|---------|------------|
| 1 | **Clarity First** | Satu halaman = satu tujuan utama. Hierarki visual mengarahkan ke aksi terpenting. |
| 2 | **Role-Driven Layout** | Admin, Guru, Siswa masing-masing punya dashboard & navigasi tersendiri. Tidak ada aksi yang terlihat tapi tidak bisa digunakan. |
| 3 | **Data at a Glance** | Nilai, status kelulusan, dan rekap tersaji dalam tabel + badge berwarna yang dapat dibaca sekilas. |
| 4 | **TALL-Native Interaction** | Validasi form, filter, dan konfirmasi pakai Alpine + Livewire — tidak ada full-page reload yang tidak perlu. |

---

## 2. Design Tokens

### 2.1 Warna

Daftarkan di `tailwind.config.js` → `theme.extend.colors`:

```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        primary:   '#1A56DB', // Indigo   — button utama, header, aksen nav
        secondary: '#374151', // Gray-700 — teks body, label, icon default
        accent:    '#0EA5E9', // Sky-500  — link, badge aktif, highlight
        success:   '#10B981', // Emerald  — status Lulus, konfirmasi sukses
        danger:    '#EF4444', // Red-500  — status Tidak Lulus, error, delete
        warning:   '#F59E0B', // Amber    — Draft, perhatian, validasi
        surface:   '#F1F5F9', // Slate-100 — background halaman, row ganjil
        navy:      '#1E3A5F', // Deep Navy — sidebar, header app
      }
    }
  }
}
```

| Token | Hex | Tailwind Class | Penggunaan |
|-------|-----|----------------|------------|
| `primary` | `#1A56DB` | `bg-primary`, `text-primary` | Button utama, navbar aktif, ring focus |
| `secondary` | `#374151` | `text-secondary` | Body text, label form, icon |
| `accent` | `#0EA5E9` | `bg-accent` | Link, counter badge, row highlight |
| `success` | `#10B981` | `bg-success`, `text-success` | Badge "Lulus", toast sukses, ikon ✓ |
| `danger` | `#EF4444` | `bg-danger`, `text-danger` | Badge "Tidak Lulus", error, tombol hapus |
| `warning` | `#F59E0B` | `bg-warning`, `text-warning` | Badge "Draft", alert perhatian |
| `surface` | `#F1F5F9` | `bg-surface` | Background page, row ganjil tabel |
| `navy` | `#1E3A5F` | `bg-navy` | Sidebar background, heading seksi |

### 2.2 Spacing

Skala 4px base unit (standar Tailwind). Tidak ada custom spacing.

| Konteks | Class | Nilai |
|---------|-------|-------|
| Sidebar width | `w-64` | 256px |
| Main content offset | `ml-64` | 256px |
| Page padding | `p-6` | 24px |
| Card padding | `p-6` | 24px |
| Section gap | `space-y-6` | 24px |
| Form label → input | `mb-2` | 8px |
| Button padding | `px-4 py-2` | 16px / 8px |
| Table row height | `h-12` | 48px |
| Cell padding | `px-4 py-3` | 16px / 12px |

### 2.3 Border Radius

| Konteks | Class |
|---------|-------|
| Button, Input | `rounded-lg` (8px) |
| Card | `rounded-xl` (12px) |
| Badge / Pill | `rounded-full` |
| Modal | `rounded-2xl` (16px) |

### 2.4 Shadow

| Konteks | Class |
|---------|-------|
| Card default | `shadow-sm` |
| Modal | `shadow-2xl` |
| Dropdown | `shadow-lg` |
| Sidebar | `shadow-md` |

---

## 3. Tipografi

Font utama: **Calibri** (system fallback: `ui-sans-serif, system-ui`).  
Font kode/identifier: **Consolas** (NIS, ID Guru).

```js
// tailwind.config.js
fontFamily: {
  sans: ['Calibri', 'ui-sans-serif', 'system-ui'],
  mono: ['Consolas', 'ui-monospace', 'monospace'],
}
```

| Elemen | Size | Weight | Class |
|--------|------|--------|-------|
| Page Title (H1) | 32px | Bold | `text-3xl font-bold text-navy` |
| Section Title (H2) | 24px | Bold | `text-2xl font-bold text-primary` |
| Sub Title (H3) | 20px | SemiBold | `text-xl font-semibold text-secondary` |
| Body / Label | 14px | Regular | `text-sm text-secondary` |
| Small / Caption | 12px | Regular | `text-xs text-slate-500` |
| Badge / Status | 11px | Bold | `text-xs font-bold` |
| Button | 14px | SemiBold | `text-sm font-semibold` |
| Table Header | 13px | Bold | `text-xs font-bold uppercase tracking-wide` |
| Table Cell | 13px | Regular | `text-sm` |
| NIS / ID (mono) | 13px | Regular | `font-mono text-sm` |

---

## 4. Komponen

### 4.1 Button

```html
<!-- Primary -->
<button class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-semibold
               hover:bg-blue-700 focus:ring-2 focus:ring-primary focus:ring-offset-2
               disabled:opacity-50 disabled:cursor-not-allowed transition">
  Simpan
</button>

<!-- Danger -->
<button class="bg-danger text-white px-4 py-2 rounded-lg text-sm font-semibold
               hover:bg-red-600 transition">
  Hapus
</button>

<!-- Secondary (outline) -->
<button class="border border-secondary text-secondary px-4 py-2 rounded-lg
               text-sm font-semibold hover:bg-surface transition">
  Batal
</button>

<!-- Success -->
<button class="bg-success text-white px-4 py-2 rounded-lg text-sm font-semibold
               hover:bg-emerald-600 transition">
  Validasi Final
</button>
```

**Livewire loading state:**
```html
<button wire:click="save" class="btn-primary">
  <span wire:loading.remove>Simpan</span>
  <span wire:loading class="flex items-center gap-2">
    <svg class="animate-spin h-4 w-4" ...></svg> Menyimpan...
  </span>
</button>
```

---

### 4.2 Badge / Status

```html
<!-- Lulus -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full
             text-xs font-bold bg-green-100 text-green-800">
  Lulus
</span>

<!-- Tidak Lulus -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full
             text-xs font-bold bg-red-100 text-red-800">
  Tidak Lulus
</span>

<!-- Draft -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full
             text-xs font-bold bg-yellow-100 text-yellow-800">
  Draft
</span>

<!-- Final -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full
             text-xs font-bold bg-blue-100 text-blue-800">
  Final
</span>

<!-- Belum diisi -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full
             text-xs font-bold bg-slate-100 text-slate-500">
  —
</span>
```

**Alpine dynamic badge (input nilai):**
```html
<span
  x-text="statusLulus"
  :class="statusLulus === 'Lulus'
    ? 'bg-green-100 text-green-800'
    : 'bg-red-100 text-red-800'"
  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold">
</span>
```

---

### 4.3 Form Input

```html
<!-- Text input standard -->
<div class="mb-4">
  <label for="nama" class="block text-sm font-medium text-secondary mb-2">
    Nama Lengkap <span class="text-danger">*</span>
  </label>
  <input
    type="text"
    id="nama"
    wire:model="nama"
    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
           focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary
           disabled:bg-surface disabled:cursor-not-allowed"
    placeholder="Masukkan nama lengkap"
  />
  @error('nama')
    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
  @enderror
</div>

<!-- Number input untuk nilai -->
<input
  type="number"
  min="0"
  max="100"
  x-model.number="tugas"
  :class="tugas < 0 || tugas > 100 ? 'border-danger focus:ring-danger' : 'border-slate-300'"
  class="w-20 border rounded-lg px-2 py-1.5 text-sm text-center
         focus:outline-none focus:ring-2"
  placeholder="0-100"
/>

<!-- Select -->
<select
  wire:model="kelas"
  class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
         focus:outline-none focus:ring-2 focus:ring-primary bg-white">
  <option value="">Pilih Kelas...</option>
  @foreach($kelasList as $k)
    <option value="{{ $k }}">{{ $k }}</option>
  @endforeach
</select>

<!-- Search input -->
<div class="relative">
  <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
    <!-- icon search -->
  </span>
  <input
    type="search"
    wire:model.debounce.500ms="search"
    class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm
           focus:outline-none focus:ring-2 focus:ring-primary"
    placeholder="Cari NIS, nama, atau kelas..."
  />
</div>
```

---

### 4.4 Card

```html
<!-- Card base -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
  <!-- konten -->
</div>

<!-- Card statistik (dashboard) -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center gap-4">
  <div class="p-3 bg-blue-100 rounded-lg">
    <!-- ikon 24px -->
  </div>
  <div>
    <p class="text-3xl font-bold text-navy">247</p>
    <p class="text-sm text-slate-500 mt-0.5">Total Siswa</p>
  </div>
</div>

<!-- Card dengan header + action -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200">
  <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
    <h2 class="text-lg font-semibold text-navy">Manajemen Siswa</h2>
    <button class="btn-primary">+ Tambah Siswa</button>
  </div>
  <div class="p-6">
    <!-- konten / tabel -->
  </div>
</div>
```

---

### 4.5 Tabel Data

```html
<div class="overflow-x-auto">
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-primary text-white">
        <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">
          NIS
        </th>
        <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide">
          Nama Siswa
        </th>
        <th scope="col" class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">
          Status
        </th>
        <th scope="col" class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide">
          Aksi
        </th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      @forelse($siswa as $index => $s)
        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-surface' }}
                   hover:bg-blue-50 transition-colors duration-150"
            wire:key="{{ $s->nis }}">
          <td class="px-4 py-3 font-mono text-sm">{{ $s->nis }}</td>
          <td class="px-4 py-3">{{ $s->nama_siswa }}</td>
          <td class="px-4 py-3 text-center">
            <!-- badge status -->
          </td>
          <td class="px-4 py-3 text-center">
            <div class="flex items-center justify-center gap-2">
              <button wire:click="edit({{ $s->nis }})"
                      class="p-1.5 text-primary hover:bg-blue-100 rounded transition"
                      aria-label="Edit siswa {{ $s->nama_siswa }}">
                <!-- icon edit -->
              </button>
              <button wire:click="confirmDelete({{ $s->nis }})"
                      class="p-1.5 text-danger hover:bg-red-100 rounded transition"
                      aria-label="Hapus siswa {{ $s->nama_siswa }}">
                <!-- icon trash -->
              </button>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="px-4 py-12 text-center text-slate-400">
            Tidak ada data siswa.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<!-- Pagination -->
<div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
  <p class="text-sm text-slate-500">
    Menampilkan {{ $siswa->firstItem() }}–{{ $siswa->lastItem() }}
    dari {{ $siswa->total() }} siswa
  </p>
  {{ $siswa->links() }}
</div>
```

---

### 4.6 Modal Konfirmasi

```html
<!-- Alpine modal — digunakan untuk konfirmasi hapus & validasi final -->
<div
  x-data="{ open: false, targetId: null }"
  x-on:confirm-delete.window="open = true; targetId = $event.detail.id">

  <!-- Backdrop -->
  <div
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    class="fixed inset-0 bg-black/50 z-40"
    @click="open = false">
  </div>

  <!-- Dialog -->
  <div
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md">
      <h3 id="modal-title" class="text-lg font-bold text-navy mb-2">
        Konfirmasi Hapus
      </h3>
      <p class="text-sm text-secondary mb-6">
        Data siswa dan seluruh nilai terkait akan dihapus permanen.
        Tindakan ini tidak dapat dibatalkan.
      </p>
      <div class="flex justify-end gap-3">
        <button @click="open = false" class="btn-secondary">Batal</button>
        <button
          wire:click="delete(targetId)"
          @click="open = false"
          class="bg-danger text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-600">
          Ya, Hapus
        </button>
      </div>
    </div>
  </div>
</div>
```

---

### 4.7 Alert & Toast

```html
<!-- Alert inline -->
<div class="bg-red-50 border-l-4 border-danger text-red-800 p-4 rounded-lg mb-4">
  <p class="text-sm font-medium">Username atau password salah.</p>
</div>

<div class="bg-green-50 border-l-4 border-success text-green-800 p-4 rounded-lg mb-4">
  <p class="text-sm font-medium">Data berhasil disimpan.</p>
</div>

<div class="bg-yellow-50 border-l-4 border-warning text-yellow-800 p-4 rounded-lg mb-4">
  <p class="text-sm font-medium">Nilai ini masih berstatus Draft.</p>
</div>

<!-- Toast (pojok kanan bawah, auto-dismiss 3 detik) -->
<div
  x-data="{ show: false, message: '' }"
  x-on:toast.window="show = true; message = $event.detail.msg; setTimeout(() => show = false, 3000)"
  x-show="show"
  x-transition:enter="transition ease-out duration-300"
  x-transition:enter-start="opacity-0 translate-y-2"
  x-transition:enter-end="opacity-100 translate-y-0"
  x-transition:leave="transition ease-in duration-200"
  x-transition:leave-start="opacity-100"
  x-transition:leave-end="opacity-0"
  class="fixed bottom-6 right-6 z-50 bg-navy text-white px-5 py-3
         rounded-xl shadow-2xl text-sm font-medium"
  x-text="message">
</div>
```

---

## 5. Layout & Struktur Halaman

### 5.1 Shell Layout

```
app-layout.blade.php
├── <aside> Sidebar (w-64, bg-navy, fixed)
│   ├── Logo + Nama Aplikasi
│   ├── <nav> Menu per role
│   └── User info + Logout
└── <main> (ml-64, flex-col)
    ├── <header> Page title + breadcrumb (bg-white, border-b)
    └── <div> Content area (p-6, overflow-y-auto)
        └── @yield('content')
```

```html
<!-- resources/views/layouts/app.blade.php -->
<div class="flex h-screen bg-surface font-sans">

  {{-- SIDEBAR --}}
  <aside class="w-64 bg-navy text-white flex-shrink-0 fixed h-full flex flex-col z-30">
    {{-- Logo --}}
    <div class="px-6 py-5 border-b border-blue-800">
      <p class="text-lg font-bold tracking-tight">📊 NilaiSiswa</p>
      <p class="text-xs text-blue-300 mt-0.5">Sistem Akademik</p>
    </div>
    {{-- Nav --}}
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
      @include('layouts.partials.nav-' . auth()->user()->role)
    </nav>
    {{-- User --}}
    <div class="p-4 border-t border-blue-800">
      <p class="text-sm font-medium truncate">{{ auth()->user()->username }}</p>
      <p class="text-xs text-blue-300 capitalize">{{ auth()->user()->role }}</p>
      <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit"
          class="w-full text-left text-xs text-blue-300 hover:text-white transition">
          Keluar →
        </button>
      </form>
    </div>
  </aside>

  {{-- MAIN --}}
  <main class="ml-64 flex-1 flex flex-col min-h-screen overflow-hidden">
    {{-- Header --}}
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-navy">@yield('page-title')</h1>
        <p class="text-xs text-slate-400 mt-0.5">@yield('breadcrumb')</p>
      </div>
    </header>
    {{-- Content --}}
    <div class="flex-1 overflow-y-auto p-6">
      @yield('content')
    </div>
  </main>
</div>
```

### 5.2 Nav Item (Partial)

```html
{{-- Contoh nav item aktif vs tidak aktif --}}
<a href="{{ route('admin.dashboard') }}"
   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
          {{ request()->routeIs('admin.dashboard')
             ? 'bg-primary text-white'
             : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
  {{-- ikon --}}
  Dashboard
</a>
```

### 5.3 Daftar Route & Halaman

| Role | Halaman | Route Name |
|------|---------|------------|
| — | Login | `login` |
| Admin | Dashboard | `admin.dashboard` |
| Admin | Manajemen Siswa | `admin.students.index` |
| Admin | Tambah Siswa | `admin.students.create` |
| Admin | Edit Siswa | `admin.students.edit` |
| Admin | Manajemen Guru | `admin.teachers.index` |
| Admin | Tambah/Edit Guru | `admin.teachers.create/edit` |
| Admin | Manajemen Akun | `admin.accounts.index` |
| Admin | Laporan | `admin.reports.index` |
| Guru | Dashboard | `guru.dashboard` |
| Guru | Input Nilai | `guru.nilai.index` |
| Guru | Edit Nilai | `guru.nilai.edit` |
| Guru | Rekap Nilai | `guru.rekap.index` |
| Siswa | Dashboard | `siswa.dashboard` |
| Siswa | Nilai Pribadi | `siswa.nilai.index` |

---

## 6. Wireframe per Halaman

### 6.1 Login

```
┌──────────────────────────────────────────────────────────┐
│  bg-gradient-to-br from-navy to-primary (full viewport)  │
│                                                          │
│           ┌──────────────────────────────────┐           │
│           │  📊 NilaiSiswa                   │           │
│           │  Sistem Manajemen Akademik       │           │
│           │  ──────────────────────────────  │           │
│           │                                  │           │
│           │  Username                        │           │
│           │  [                             ] │           │
│           │                                  │           │
│           │  Password                        │           │
│           │  [                          ] 👁 │           │
│           │                                  │           │
│           │  □ Ingat Saya                    │           │
│           │                                  │           │
│           │  [       MASUK      ]            │           │
│           │   ↑ btn-primary, full-width      │           │
│           │                                  │           │
│           │  ⚠ Error message (conditional)  │           │
│           └──────────────────────────────────┘           │
└──────────────────────────────────────────────────────────┘
```

**Blade Component:** `LoginForm` (Livewire)  
**Behaviour:**
- Validasi kosong: client-side sebelum submit
- Kredensial salah: `@error('credentials')` dari Livewire
- 3× gagal: muncul hint "Hubungi admin jika lupa password"
- Loading: `wire:loading` spinner + disable tombol
- Redirect: `/admin/dashboard` | `/guru/dashboard` | `/siswa/dashboard`

---

### 6.2 Dashboard Admin

```
┌─ Sidebar ──┐  ┌─ Main ──────────────────────────────────────────┐
│ 📊 Logo    │  │ Dashboard                    Semester Ganjil     │
│ ──────── │  │ ─────────────────────────────────────────────── │
│ Dashboard  │  │                                                 │
│ Siswa      │  │  ┌────────────┐ ┌────────────┐ ┌────────────┐  │
│ Guru       │  │  │ 👤 SISWA   │ │ 👩 GURU    │ │ ✅ LULUS   │  │
│ Akun       │  │  │    247     │ │     18     │ │   78.3%    │  │
│ Laporan    │  │  └────────────┘ └────────────┘ └────────────┘  │
│ ──────── │  │                                                 │
│ Admin      │  │  ┌───────────────────────┐ ┌────────────────┐  │
│ Logout     │  │  │ Rekap per Kelas       │ │ Aksi Cepat     │  │
└────────────┘  │  │ Kelas  Lulus  TdkLls  │ │ + Tambah Siswa │  │
                │  │ X-A    28     2       │ │ + Tambah Guru  │  │
                │  │ X-B    27     3       │ │ 📄 Cetak Lap.  │  │
                │  └───────────────────────┘ └────────────────┘  │
                └─────────────────────────────────────────────────┘
```

---

### 6.3 Manajemen Siswa

```
│ Manajemen Siswa                          [ + Tambah Siswa ] │
│ ──────────────────────────────────────────────────────────  │
│ [ 🔍 Cari NIS / Nama... ]     [ Filter Kelas: Semua ▾ ]    │
│                                                             │
│ ┌──────────┬──────────────────┬────────┬─────────────────┐  │
│ │ NIS      │ Nama Siswa       │ Kelas  │ Aksi            │  │
│ ├──────────┼──────────────────┼────────┼─────────────────┤  │
│ │ 0001     │ Ahmad Fauzi      │ X-A    │ [✏ Edit][🗑 Hps] │  │
│ │ 0002     │ Budi Santoso     │ X-A    │ [✏ Edit][🗑 Hps] │  │
│ │ ...      │ ...              │ ...    │ ...             │  │
│ └──────────┴──────────────────┴────────┴─────────────────┘  │
│                                                             │
│ Menampilkan 1–15 dari 247 siswa    [ < 1 2 3 ... 17 > ]   │
```

**Form Tambah/Edit Siswa:**
```
│ ┌─────────────────────────────────────┐ │
│ │ NIS *                               │ │
│ │ [0001]  ← disabled saat mode edit  │ │
│ │                                     │ │
│ │ Nama Lengkap *                      │ │
│ │ [Ahmad Fauzi                      ] │ │
│ │                                     │ │
│ │ Kelas *                             │ │
│ │ [ X-A ▾ ]                          │ │
│ │                                     │ │
│ │ [ Simpan ]   [ Batal ]              │ │
│ └─────────────────────────────────────┘ │
```

---

### 6.4 Manajemen Guru

```
│ Manajemen Guru                           [ + Tambah Guru ] │
│ ──────────────────────────────────────────────────────────  │
│ [ 🔍 Cari... ]          [ Filter Mapel: Semua ▾ ]          │
│                                                             │
│ ┌────┬───────────────┬────────────────┬────────┬─────────┐  │
│ │ ID │ Nama Guru     │ Mata Pelajaran │ Status │ Aksi    │  │
│ ├────┼───────────────┼────────────────┼────────┼─────────┤  │
│ │ 1  │ Ibu Sari      │ Matematika     │[Aktif] │[✏][🚫] │  │
│ │ 2  │ Bu Rini (off) │ IPA            │[Nonakt]│[✏][✅] │  │
│ └────┴───────────────┴────────────────┴────────┴─────────┘  │
│                                                             │
│  ⚠ Guru yang sudah punya nilai tidak dapat dihapus.        │
│    Gunakan tombol nonaktifkan (🚫) sebagai gantinya.        │
```

---

### 6.5 Input Nilai (Guru) ← halaman inti

```
│ Input Nilai                                                  │
│ ────────────────────────────────────────────────────────── │
│ Kelas: [ X-A ▾ ]    Mapel: [ Matematika ▾ ] (auto-filled) │
│                              [ Tampilkan Daftar Siswa ]    │
│ ────────────────────────────────────────────────────────── │
│ ┌──────┬──────────────┬───────┬──────┬──────┬──────┬─────┐ │
│ │ NIS  │ Nama         │ Tugas │ UTS  │ UAS  │Akhir │ Sta │ │
│ │      │              │ 30%   │ 30%  │ 40%  │(Auto)│     │ │
│ ├──────┼──────────────┼───────┼──────┼──────┼──────┼─────┤ │
│ │ 0001 │ Ahmad Fauzi  │ [90]  │ [85] │ [90] │ 89.0 │[Lls]│ │
│ │ 0002 │ Budi Santoso │ [  ]  │ [  ] │ [  ] │  —   │ —   │ │
│ │ 0025 │ Yanti Kusuma │ [60]  │ [55] │ [65] │ 61.0 │[TL] │ │
│ └──────┴──────────────┴───────┴──────┴──────┴──────┴─────┘ │
│                                                             │
│  ↑ Nilai akhir & status dihitung real-time (Alpine.js)      │
│                                                             │
│  [ 💾 Simpan sebagai Draft ]   [ ✅ Validasi Final ]        │
│                                                             │
│  ⚠ Validasi Final akan mengunci semua nilai di atas.        │
│    Nilai Final tidak dapat diubah oleh Guru.                │
```

---

### 6.6 Laporan (Admin)

```
│ Laporan Nilai                                                │
│ ────────────────────────────────────────────────────────── │
│ Pilih Kelas: [ X-A ▾ ]          [ Generate Laporan ]       │
│ ────────────────────────────────────────────────────────── │
│ Kelas X-A — 30 Siswa │ Lulus: 28 │ Tidak Lulus: 2          │
│ ────────────────────────────────────────────────────────── │
│ ┌──────┬──────────┬───────────┬──────┬──────┬──────┬──────┐ │
│ │ NIS  │ Nama     │ Mapel     │ Tgs  │ UTS  │ UAS  │ Akh  │ │
│ │      │          │           │ 30%  │ 30%  │ 40%  │ Stat │ │
│ ├──────┼──────────┼───────────┼──────┼──────┼──────┼──────┤ │
│ │ 0001 │ Ahmad F. │ Matematika│  90  │  85  │  90  │ 89.0 │ │
│ │      │          │           │      │      │      │[Lls] │ │
│ │ 0025 │ Yanti K. │ Matematika│  60  │  55  │  65  │ 61.0 │ │
│ │      │          │           │      │      │      │[TL]  │ │
│ └──────┴──────────┴───────────┴──────┴──────┴──────┴──────┘ │
│                                                             │
│  [ 📄 Ekspor PDF ]    [ 🌐 Ekspor HTML ]                    │
```

---

### 6.7 Nilai Pribadi (Siswa)

```
│ Nilai Saya — Ahmad Fauzi (NIS: 0001) — Kelas X-A           │
│ ────────────────────────────────────────────────────────── │
│ ┌────────────────┬──────┬──────┬──────┬───────┬──────────┐  │
│ │ Mata Pelajaran │ Tgs  │ UTS  │ UAS  │ Akhir │ Status   │  │
│ │                │ 30%  │ 30%  │ 40%  │       │          │  │
│ ├────────────────┼──────┼──────┼──────┼───────┼──────────┤  │
│ │ Matematika     │  90  │  85  │  90  │ 89.0  │ [Lulus]  │  │
│ │ Bahasa Ind.    │  80  │  75  │  80  │ 78.5  │ [Lulus]  │  │
│ │ IPA            │  65  │  60  │  65  │ 63.5  │ [TdkLls] │  │
│ │ IPS            │  —   │  —   │  —   │  —    │ [Belum]  │  │
│ └────────────────┴──────┴──────┴──────┴───────┴──────────┘  │
│                                                             │
│  🔒 Halaman ini hanya dapat dilihat, tidak dapat diubah.    │
```

---

## 7. State & Interaksi

### 7.1 Kalkulasi Nilai Real-time (Alpine.js)

```html
<tr x-data="{
  tugas: {{ $nilai->nilai_tugas ?? 0 }},
  uts:   {{ $nilai->nilai_uts ?? 0 }},
  uas:   {{ $nilai->nilai_uas ?? 0 }},
  get nilaiAkhir() {
    const t = parseFloat(this.tugas) || 0;
    const u = parseFloat(this.uts)   || 0;
    const s = parseFloat(this.uas)   || 0;
    return (t * 0.3) + (u * 0.3) + (s * 0.4);
  },
  get statusLulus() {
    return this.nilaiAkhir >= 70 ? 'Lulus' : 'Tidak Lulus';
  },
  get isValidInput() {
    return [this.tugas, this.uts, this.uas]
      .every(v => v >= 0 && v <= 100);
  }
}">
  <td><input type="number" min="0" max="100" x-model.number="tugas"
       :class="tugas < 0 || tugas > 100 ? 'border-danger' : 'border-slate-300'"
       class="w-20 border rounded-lg px-2 py-1.5 text-sm text-center" /></td>
  <td><input type="number" min="0" max="100" x-model.number="uts"
       class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-sm text-center" /></td>
  <td><input type="number" min="0" max="100" x-model.number="uas"
       class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-sm text-center" /></td>
  <td class="text-center font-semibold" x-text="isValidInput ? nilaiAkhir.toFixed(1) : '—'"></td>
  <td class="text-center">
    <span
      x-show="isValidInput"
      x-text="statusLulus"
      :class="statusLulus === 'Lulus' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold">
    </span>
  </td>
</tr>
```

### 7.2 Livewire Patterns

| Pattern | Kapan Digunakan |
|---------|-----------------|
| `wire:model.debounce.500ms` | Search bar — hindari query per keystroke |
| `wire:click` | Semua aksi: save, delete, generate laporan |
| `wire:loading.attr="disabled"` | Disable tombol saat request in-flight |
| `wire:loading.class="opacity-50"` | Visual feedback loading pada elemen |
| `wire:key="{{ $item->id }}"` | Wajib di `@foreach` untuk DOM tracking |
| `$emit('toast', ['msg' => '...'])` | Trigger toast notifikasi ke Alpine |
| `$refresh` | Reload komponen setelah aksi sukses |

### 7.3 Validasi Final — Alur Konfirmasi

```
Guru klik "Validasi Final"
  → Alpine modal terbuka (konfirmasi)
      → Guru klik "Ya, Kunci Nilai"
          → wire:click="validateFinal"
              → Livewire: status_validasi = 'Final'
              → Semua input row dinonaktifkan (disabled)
              → Badge "Final" biru tampil
              → Toast: "Nilai berhasil dikunci"
      → Guru klik "Batal"
          → Modal tutup, tidak ada perubahan
```

### 7.4 Guard 403 Siswa

```php
// app/Http/Middleware/AuthorizeRole.php
public function handle(Request $request, Closure $next, ...$roles): Response
{
    if (!in_array(auth()->user()->role, $roles)) {
        abort(403, 'Akses ditolak.');
    }
    return $next($request);
}

// routes/web.php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(...);
Route::middleware(['auth', 'role:guru'])->prefix('guru')->group(...);
Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->group(...);
```

---

## 8. Aksesibilitas

| Standar | Implementasi |
|---------|--------------|
| Label form | Semua `<input>` dan `<select>` memiliki `<label for="...">` |
| ARIA button | Tombol ikon memiliki `aria-label` deskriptif |
| Warna + teks | Status tidak hanya bergantung pada warna — disertai teks & ikon |
| Focus ring | `focus:ring-2 focus:ring-primary focus:ring-offset-2` di semua elemen interaktif |
| Modal ARIA | `role="dialog"` + `aria-modal="true"` + `aria-labelledby` |
| Focus trap | Alpine fokus dikembalikan ke trigger saat modal ditutup |
| Tabel semantik | `<thead>`, `<th scope="col">` untuk screen reader |
| Kontras | Semua teks pada background berwarna memenuhi rasio kontras WCAG AA (4.5:1) |

---

## 9. Checklist Implementasi

### Setup

- [ ] `tailwind.config.js` — custom colors, fontFamily (Calibri, Consolas)
- [ ] `app-layout.blade.php` — sidebar + main area + nav partials per role
- [ ] Komponen Blade: `x-button`, `x-badge`, `x-card`, `x-modal`, `x-alert`
- [ ] Livewire + Alpine.js terpasang via Vite atau CDN
- [ ] Middleware `AuthorizeRole` terdaftar di `bootstrap/app.php`
- [ ] Custom pagination view untuk Livewire (`resources/views/vendor/livewire/...`)

### Per Halaman

- [ ] **Login** — `LoginForm` Livewire + redirect per role + counter gagal
- [ ] **Dashboard Admin** — query statistik + stat cards + rekap kelulusan per kelas
- [ ] **Manajemen Siswa** — search/filter/pagination + CRUD + modal konfirmasi hapus
- [ ] **Manajemen Guru** — CRUD + toggle `is_active` + RESTRICT guard di delete
- [ ] **Input Nilai** — Alpine kalkulasi real-time + Livewire Draft/Final + row lock
- [ ] **Laporan** — generate per kelas + DomPDF ekspor + ekspor HTML
- [ ] **Dashboard Siswa** — read-only, data difilter berdasarkan NIS sesi
- [ ] **Nilai Pribadi** — tabel read-only, nilai null tampil sebagai "—", badge status

### Acceptance Criteria UI

| AC | Skenario | Ekspektasi Visual |
|----|----------|-------------------|
| AC-04 | Input nilai 105 | Border merah + pesan error inline "Nilai harus 0–100" |
| AC-05 | Tugas=80 UTS=70 UAS=90 | Kolom Akhir tampil "81.0", badge hijau "Lulus" (real-time) |
| AC-06 | Tugas=50 UTS=60 UAS=65 | Kolom Akhir tampil "59.0", badge merah "Tidak Lulus" |
| AC-07 | Siswa buka nilai pribadi | Semua sel tabel non-editable, tidak ada tombol aksi |
| AC-08 | Siswa akses URL edit | Halaman 403 dengan pesan jelas dan link kembali ke dashboard |
| AC-10 | Admin ekspor PDF | File terunduh: `kelas_XA_2025.pdf`, data lengkap dan benar |

---

*DESIGN.md — Aplikasi Pengolahan Nilai Siswa — v1.0.0*
