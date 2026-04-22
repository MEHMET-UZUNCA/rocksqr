@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Pending Waiter Calls</h2>

        @if($waiterCalls->isEmpty())
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                <p>No pending waiter calls</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($waiterCalls as $call)
                    <div class="bg-white rounded-lg shadow overflow-hidden border-l-4 border-red-500">
                        <div class="p-6 flex justify-between items-center">
                            <div>
                                <p class="text-2xl font-bold text-gray-900">Table {{ $call->table_no }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ $call->created_at->diffForHumans() }}</p>
                                @if($call->note)
                                    <p class="text-gray-700 mt-2"><strong>Note:</strong> {{ $call->note }}</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.waiter-calls.attended', $call) }}">
                                @csrf
                                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                    Mark as Attended
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $waiterCalls->links() }}
            </div>
        @endif
    </div>
</div>
@endsection