<form class="ui form">
    <div class="ui message info">
        <div class="header">MWE-Specific Settings</div>
        <p>These settings apply only to Multi-Word Expression (MWE) constructions.</p>
    </div>

    <div class="field">
        <div class="ui toggle checkbox">
            <input type="checkbox" name="lookaheadEnabled" {{ $construction->lookaheadEnabled ? 'checked' : '' }}>
            <label>Enable Lookahead</label>
        </div>
        <small>Allow matching across non-adjacent tokens</small>
    </div>

    <div class="field">
        <label>Lookahead Max Distance</label>
        <input type="number" name="lookaheadMaxDistance" value="{{ $construction->lookaheadMaxDistance }}" min="0" max="10">
        <small>Maximum token distance for lookahead (0-10)</small>
    </div>

    <div class="field">
        <label>Invalidation Patterns (JSON)</label>
        <textarea name="invalidationPatterns" rows="5" style="font-family: 'Courier New', monospace;">{{ json_encode($invalidationPatterns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
        <small>Patterns that invalidate this MWE match</small>
    </div>

    <div class="field">
        <label>Confirmation Patterns (JSON)</label>
        <textarea name="confirmationPatterns" rows="5" style="font-family: 'Courier New', monospace;">{{ json_encode($confirmationPatterns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
        <small>Patterns that confirm this MWE match</small>
    </div>
</form>
