@php
    use Orchid\Screen\Actions\Button;
    use Orchid\Screen\Actions\ModalToggle;
@endphp

{{-- Shown by admin-user-list.js when at least one user is ticked. "{count}"
     in data-confirm-template is replaced with the live selection count. --}}
<div class="ul-bulkbar" data-bulk-bar hidden>
    <div class="ul-bulk-count">
        <span data-bulk-count>0</span><span class="ul-bulk-word">&nbsp;selected</span>
    </div>

    <div class="ul-bulk-actions">
        <span data-confirm-template="Show {count} user(s) in the app?">
            {!! Button::make('Show in app')->icon('bs.eye')->method('bulkSetVisibility', ['visible' => 1])->confirm('Show the selected users in the app?')->class('btn btn-sm ul-bulk-btn')->render() !!}
        </span>
        <span data-confirm-template="Hide {count} user(s) from the app? They will no longer appear in networking.">
            {!! Button::make('Hide from app')->icon('bs.eye-slash')->method('bulkSetVisibility', ['visible' => 0])->confirm('Hide the selected users from the app?')->class('btn btn-sm ul-bulk-btn')->render() !!}
        </span>
        {!! ModalToggle::make('Change role')->icon('bs.person-badge')->modal('bulkAssignRoleModal')->method('bulkAssignRole')->class('btn btn-sm ul-bulk-btn')->render() !!}
        {!! ModalToggle::make('Assign company')->icon('bs.building')->modal('bulkAssignCompanyModal')->method('bulkAssignCompany')->class('btn btn-sm ul-bulk-btn')->render() !!}
        {!! ModalToggle::make('Email')->icon('bs.envelope')->modal('bulkEmailModal')->method('bulkSendEmail')->class('btn btn-sm ul-bulk-btn')->render() !!}
        <span data-confirm-template="Delete {count} user(s)? This cannot be undone.">
            {!! Button::make('Delete')->icon('bs.trash3')->method('bulkDelete')->confirm('Delete the selected users? This cannot be undone.')->class('btn btn-sm ul-bulk-btn ul-bulk-danger')->render() !!}
        </span>
    </div>

    <button type="button" class="ul-bulk-clear" data-bulk-clear title="Clear selection">
        <x-orchid-icon path="bs.x-lg"/>
    </button>
</div>
