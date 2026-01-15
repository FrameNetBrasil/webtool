<form class="ui form">
    <div class="field">
        <label>Constraints (JSON)</label>
        <textarea name="constraints" rows="10" style="font-family: 'Courier New', monospace;">{{ json_encode($constraints, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
        <small>JSON object defining constraints for pattern matching</small>
    </div>
    <div class="field">
        <label>Aggregate As</label>
        <input type="text" name="aggregateAs" value="{{ $construction->aggregateAs }}" placeholder="e.g., NP, VP">
    </div>
    <div class="field">
        <label>Semantic Type</label>
        <input type="text" name="semanticType" value="{{ $construction->semanticType }}" placeholder="e.g., Physical_Object">
    </div>
    <div class="ui info message">
        <div class="header">Example Constraints</div>
        <pre>{
  "agreement": ["number", "gender"],
  "required_features": {
    "pos": "NOUN"
  }
}</pre>
    </div>
</form>
