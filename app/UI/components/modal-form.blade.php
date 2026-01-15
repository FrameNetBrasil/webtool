@props([
    'id' => '',
    'class' => '',
    'url' => '',
    'label' => '',
    'size' => 'medium'
])

{{-- Button that triggers the modal --}}
<button id="btn-{{ $id }}" {{ $attributes->merge(['class' => $class ?: 'ui medium button']) }}>
    {{ $label }}
</button>

{{-- Modal structure --}}
<div id="modal-{{ $id }}" class="ui {{ $size !== 'medium' ? $size : '' }} modal"
     x-data="{ loaded: false }"
     x-init="
        const alpineContext = this;

        // Initialize Fomantic-UI modal
        $('#modal-{{ $id }}').modal({
            onShow: function() {
                // Load content only if not already loaded
                if (!alpineContext.loaded) {
                    htmx.ajax('GET', '{{ $url }}', '#modalContent-{{ $id }}');
                    alpineContext.loaded = true;
                }
            },
            closable: true,
            onApprove: function() {
                // Prevent default approve behavior
                return false;
            }
        });

        // Wire button click to show modal
        $('#btn-{{ $id }}').on('click', function() {
            $('#modal-{{ $id }}').modal('show');
        });

        // Listen for success event to auto-close modal
        document.body.addEventListener('htmx:afterSwap', function(e) {
            if (e.detail.target.id === 'modalContent-{{ $id }}') {
                // Check if server sent closeModal trigger
                const trigger = e.detail.xhr.getResponseHeader('HX-Trigger');
                if (trigger === 'closeModal') {
                    $('#modal-{{ $id }}').modal('hide');
                }
            }
        });
    ">
    <i class="close icon"></i>
    <div id="modalContent-{{ $id }}" class="content" x-ref="modalContent">
        {{-- Loading indicator shown until content is loaded --}}
        <div class="ui active inverted dimmer">
            <div class="ui text loader">Loading...</div>
        </div>
    </div>
</div>

{{-- Fix for Fomantic-UI page dimmer blocking clicks when not active --}}
<style>
    .ui.dimmer.modals.page:not(.active) {
        display: none !important;
    }
</style>
