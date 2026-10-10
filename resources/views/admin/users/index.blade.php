@extends('layouts.admin')

@section('title', 'Kullanıcılar')

@section('content')
<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-gold/15 text-gold"><i class="fas fa-users"></i></span>
                    Kullanıcılar
                </h2>
                <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-primary text-white rounded hover:bg-light-primary transition text-sm font-medium">
                    <i class="fas fa-user-plus mr-1"></i> Yeni Kullanıcı
                </a>
            </div>

            @if($users->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-user-slash text-3xl mb-3 text-gray-300"></i>
                    <p class="font-semibold mb-2">Kullanıcı bulunamadı.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-center w-10 text-xs font-medium text-gray-500 uppercase">N</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Ad Soyad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">E-posta</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Durum</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Oluşturuldu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-center">
                                        <span class="text-xs text-gray-500 font-semibold font-mono select-none">{{ $users->firstItem() + $loop->index }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-900 font-medium">
                                        {{ $user->full_name }}
                                        @if($user->id === auth()->id())
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">(siz)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->email }}</td>
                                    <td class="px-6 py-4">
                                        @if($user->isAdmin())
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gold/15 text-yellow-800 border border-yellow-300">Yönetici</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">Personel</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($user->is_active)
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aktif</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Pasif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-sm">{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                    <td class="px-6 py-4 space-x-2">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:underline">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
