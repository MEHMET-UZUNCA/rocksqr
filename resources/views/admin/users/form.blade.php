@extends('layouts.admin')

@section('title', isset($user) ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı')

@php
    $isSelf = isset($user) && $user->id === auth()->id();
@endphp

@section('content')
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-gold/15 text-gold"><i class="fas fa-user"></i></span>
                    {{ isset($user) ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı' }}
                </h2>
            </div>

            <div class="p-6">
                <form method="POST"
                      action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}"
                      class="space-y-6">
                    @csrf
                    @if(isset($user))
                        @method('PUT')
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ad</label>
                            <input type="text" name="name" required maxlength="100"
                                   value="{{ old('name', $user->name ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Soyad</label>
                            <input type="text" name="surname" maxlength="100"
                                   value="{{ old('surname', $user->surname ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('surname') border-red-500 @enderror">
                            @error('surname')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">E-posta</label>
                        <input type="email" name="email" required
                               value="{{ old('email', $user->email ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                            <select name="role" {{ $isSelf ? 'disabled' : '' }}
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('role') border-red-500 @enderror {{ $isSelf ? 'bg-gray-100 text-gray-500' : '' }}">
                                <option value="admin" {{ old('role', $user->role ?? '') === 'admin' ? 'selected' : '' }}>Yönetici</option>
                                <option value="personel" {{ old('role', $user->role ?? 'personel') === 'personel' ? 'selected' : '' }}>Personel</option>
                            </select>
                            @if($isSelf)
                                <p class="text-xs text-gray-500 mt-1">Kendi rolünüz değiştirilemez.</p>
                            @endif
                            @error('role')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Durum</label>
                            <label class="flex items-center h-[42px]">
                                <input type="checkbox" name="is_active" value="1"
                                       @checked(old('is_active', ($user->is_active ?? true)))
                                       {{ $isSelf ? 'disabled' : '' }}
                                       class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Aktif (panel girişine izinli)</span>
                            </label>
                            @if($isSelf)
                                <p class="text-xs text-gray-500 mt-1">Kendi durumunuz değiştirilemez.</p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Şifre {{ isset($user) ? '(değiştirmek istemiyorsanız boş bırakın)' : '' }}
                        </label>
                        <input type="password" name="password" {{ isset($user) ? '' : 'required' }} minlength="8"
                               autocomplete="new-password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Şifre (Tekrar)</label>
                        <input type="password" name="password_confirmation" {{ isset($user) ? '' : 'required' }} minlength="8"
                               autocomplete="new-password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded hover:bg-light-primary transition">
                            <i class="fas fa-save mr-1"></i> {{ isset($user) ? 'Güncelle' : 'Oluştur' }}
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition">
                            İptal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
