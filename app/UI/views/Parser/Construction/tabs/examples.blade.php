<form class="ui form">
    <div class="field">
        <label>Example Sentences (JSON Array)</label>
        <textarea name="examples" rows="10" style="font-family: 'Courier New', monospace;">{{ json_encode($examples, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
        <small>JSON array of example sentences demonstrating this construction</small>
    </div>
    <div class="ui info message">
        <div class="header">Example Format</div>
        <pre>[
  {"sentence": "O café está quente", "match": "O café"},
  {"sentence": "Vi o menino correndo", "match": "o menino"}
]</pre>
    </div>
</form>
