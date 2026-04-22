@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Oracle Database Sync</h2>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-blue-900 text-sm">
                        <strong>Configuration:</strong> Oracle connection is configured to IP <code>192.168.0.10</code>
                    </p>
                    <p class="text-blue-900 text-sm mt-2">
                        You can sync products from Oracle database and compare with local products.
                    </p>
                </div>

                <div class="space-y-4">
                    <a href="{{ route('admin.oracle.sync') }}" class="block px-6 py-3 bg-primary text-white rounded hover:bg-light-primary text-center">
                        Sync Products from Oracle
                    </a>

                    <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
                        <h4 class="font-semibold text-gray-900 mb-2">Configuration Details:</h4>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Host: 192.168.0.10</li>
                            <li>Port: 1521</li>
                            <li>Database: ORCL</li>
                            <li>Connection: oracle</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection