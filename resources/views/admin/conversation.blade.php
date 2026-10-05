<div class="bg-white rounded shadow-sm p-4 mb-3">

    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
        <div class="d-flex align-items-center">
            <span class="me-2">{!! $user1->avatarThumbHtml(40, 'bg-primary') !!}</span>
            <div>
                <h5 class="mb-0 text-dark">{{ $user1->name }}</h5>
                <small class="text-muted">{{ $user1->email }}</small>
            </div>
        </div>

        <div class="text-muted small px-3">
            <i class="icon-refresh"></i> Interaction
        </div>

        <div class="d-flex align-items-center text-end">
            <div>
                <h5 class="mb-0 text-dark">{{ $user2->name }}</h5>
                <small class="text-muted">{{ $user2->email }}</small>
            </div>
            <span class="ms-2">{!! $user2->avatarThumbHtml(40, 'bg-success') !!}</span>
        </div>
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
                    <div class="d-flex flex-column align-items-{{ $alignment }}" style="max-width: 70%;">

                        <small class="text-muted mb-1" style="font-size: 0.75rem;">
                            {{ $senderName }} • {{ $msg->created_at->format('M d, H:i') }}
                        </small>

                        <div class="p-3 rounded shadow-sm {{ $bgColor }} {{ $textColor }}" style="position: relative;">

                            @if($msg->content)
                                <p class="mb-1" style="white-space: pre-wrap;">{{ $msg->content }}</p>
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
