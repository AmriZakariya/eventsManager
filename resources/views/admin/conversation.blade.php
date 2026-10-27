<style>
.chat-participant { border-radius:10px; padding:4px 8px; margin:-4px -8px; transition:background .15s; }
.chat-participant:hover { background:#f4f6f8; }
.chat-participant:hover h5 { color:#5444b4 !important; text-decoration:underline; }
.chat-participant:focus-visible { outline:3px solid #9a8fe8; outline-offset:2px; }
</style>
<div class="bg-white rounded shadow-sm p-4 mb-3">

    <div class="chat-thread-header d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
        <a href="{{ route('platform.systems.users.view', $user1->id) }}" class="chat-participant d-flex align-items-center text-decoration-none" style="min-width:0;" title="Open {{ $user1->name }}'s profile">
            <span class="me-2">{!! $user1->avatarThumbHtml(40, 'bg-primary') !!}</span>
            <div style="min-width:0;">
                <h5 class="mb-0 text-dark text-break">{{ $user1->name }} {!! $user1->bannedBadgeHtml() !!}</h5>
                <small class="text-muted text-break">{{ $user1->email }}</small>
            </div>
        </a>

        <div class="text-muted small px-3 d-none d-md-block">
            <i class="icon-refresh"></i> Interaction
        </div>

        <a href="{{ route('platform.systems.users.view', $user2->id) }}" class="chat-participant chat-participant-right d-flex align-items-center text-end text-decoration-none" style="min-width:0;" title="Open {{ $user2->name }}'s profile">
            <div style="min-width:0;">
                <h5 class="mb-0 text-dark text-break">{!! $user2->bannedBadgeHtml() !!} {{ $user2->name }}</h5>
                <small class="text-muted text-break">{{ $user2->email }}</small>
            </div>
            <span class="ms-2">{!! $user2->avatarThumbHtml(40, 'bg-success') !!}</span>
        </a>
    </div>

    <div class="chat-history" style="max-height: 600px; overflow-y: auto; background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
        @if($messages->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="icon-bubble display-4 mb-3 d-block"></i>
                No messages exchanged between these users yet.
            </div>
        @else
            @foreach($messages as $msg)
                @php
                    $isUser1 = $msg->sender_id == $user1->id;
                    $alignment = $isUser1 ? 'start' : 'end';
                    $bgColor = $isUser1 ? 'bg-white border' : 'bg-primary text-white';
                    $textColor = $isUser1 ? 'text-dark' : 'text-white';
                    $metaColor = $isUser1 ? 'text-muted' : 'text-white-50';
                    $senderName = $isUser1 ? $user1->name : $user2->name;
                @endphp

                <div class="d-flex justify-content-{{ $alignment }} mb-3">
                    <div class="chat-bubble-wrap d-flex flex-column align-items-{{ $alignment }}" style="max-width: 70%;">

                        <small class="text-muted mb-1" style="font-size: 0.75rem;">
                            {{ $senderName }} • {{ $msg->created_at->format('M d, H:i') }}
                        </small>

                        <div class="p-3 rounded shadow-sm {{ $bgColor }} {{ $textColor }}" style="position: relative;">

                            @if($msg->content)
                                <p class="mb-1" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $msg->content }}</p>
                            @endif

                            @if($msg->attachment_url)
                                <div class="mt-2 pt-2 border-top border-light">
                                    <a href="{{ $msg->attachment_url }}" target="_blank" class="{{ $isUser1 ? 'text-primary' : 'text-white' }} text-decoration-none d-flex align-items-center">
                                        <i class="icon-paper-clip me-1"></i> View Attachment
                                    </a>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
