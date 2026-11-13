<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Messages') }} <span class="ms-2 text-sm font-normal text-gray-500">{{ $unread }} unread</span></h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            @forelse ($messages as $message)
                <article @class(['rounded-lg bg-white p-5 shadow-sm', 'ring-2 ring-brand-200' => ! $message->read_at])>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $message->subject }}</h3>
                            <p class="text-sm text-gray-500">{{ $message->name }} · <a href="mailto:{{ $message->email }}" class="underline">{{ $message->email }}</a> · {{ $message->created_at->diffForHumans() }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.messages.read', $message) }}">
                            @csrf
                            @method('PATCH')
                            <x-secondary-button type="submit" class="!px-3 !py-1.5">{{ $message->read_at ? 'Mark unread' : 'Mark read' }}</x-secondary-button>
                        </form>
                    </div>
                    <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $message->message }}</p>
                </article>
            @empty
                <x-empty-state icon="inbox" title="No messages yet" text="Messages sent from the public Contact page appear here." />
            @endforelse
            {{ $messages->links() }}
        </div>
    </div>
</x-admin-layout>
