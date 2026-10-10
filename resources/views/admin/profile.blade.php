@extends('layouts.admin')

@section('title', 'Profilim')

@section('content')
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-gold/15 text-gold"><i class="fas fa-id-badge"></i></span>
                    Profilim
                </h2>
                <div class="flex items-center gap-2">
                    @if(auth()->user()->isAdmin())
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gold/15 text-yellow-800 border border-yellow-300">Yönetici</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">Personel</span>
                    @endif
                    @if(auth()->user()->is_active)
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aktif</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Pasif</span>
                    @endif
                </div>
            </div>

            <div class="p-6">
                <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ad</label>
                            <input type="text" name="name" required maxlength="100"
                                   value="{{ old('name', $user->name) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Soyad</label>
                            <input type="text" name="surname" maxlength="100"
                                   value="{{ old('surname', $user->surname) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('surname') border-red-500 @enderror">
                            @error('surname')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">E-posta</label>
                        <input type="email" name="email" required
                               value="{{ old('email', $user->email) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <p class="text-sm font-semibold text-gray-900 mb-4">
                            <i class="fas fa-key mr-1.5 text-gold"></i>Şifre Değiştir
                            <span class="font-normal text-gray-500">(değiştirmek istemiyorsanız boş bırakın)</span>
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mevcut Şifre</label>
                                <input type="password" name="current_password" autocomplete="current-password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('current_password') border-red-500 @enderror">
                                @error('current_password')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Yeni Şifre</label>
                                    <input type="password" name="password" minlength="8" autocomplete="new-password"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('password') border-red-500 @enderror">
                                    @error('password')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Yeni Şifre (Tekrar)</label>
                                    <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">Yeni şifre girerseniz mevcut şifrenizi de girmeniz gerekir. En az 8 karakter.</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded hover:bg-light-primary transition">
                            <i class="fas fa-save mr-1"></i> Güncelle
                        </button>
                        <a href="{{ route('admin.dashboard') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition">
                            İptal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
