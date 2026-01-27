<form class="ui form">
    <div class="field">
        <label>Pattern (BNF)</label>
        <textarea name="pattern" rows="5" style="font-family: 'Courier New', monospace;">{{ $construction->pattern }}</textarea>
        <small>Use {POS}, literals, [], (), |, *, + notation</small>
    </div>
    <div class="field">
        <label>Priority</label>
        <input type="number" name="priority" value="{{ $construction->priority }}" min="1" max="199">
    </div>
    <div class="field">
        <div class="ui toggle checkbox">
            <input type="checkbox" name="enabled" {{ $construction->enabled ? 'checked' : '' }}>
            <label>Enabled</label>
        </div>
    </div>
    @if($compiledPattern)
        <div class="ui message">
            <div class="header">Compiled Pattern</div>
            <pre>{{ json_encode($compiledPattern, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif
    <button type="button" class="ui button primary"
            hx-post="/parser/construction/{{ $construction->idConstruction }}/compile">
        Recompile Pattern
    </button>
</form>
