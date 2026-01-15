<form class="ui form">
    <div class="field">
        <label>Phrasal CE Label</label>
        <input type="text" name="phrasalCE" value="{{ $construction->phrasalCE }}" placeholder="e.g., Head, Modifier">
        <small>Construction element label at phrasal level</small>
    </div>
    <div class="field">
        <label>Clausal CE Label</label>
        <input type="text" name="clausalCE" value="{{ $construction->clausalCE }}" placeholder="e.g., Subject, Predicate">
        <small>Construction element label at clausal level</small>
    </div>
    <div class="field">
        <label>Sentential CE Label</label>
        <input type="text" name="sententialCE" value="{{ $construction->sententialCE }}" placeholder="e.g., MainClause">
        <small>Construction element label at sentential level</small>
    </div>
    <div class="ui info message">
        <div class="header">Existing Labels in Grammar</div>
        <div class="content">
            <strong>Phrasal:</strong> {{ implode(', ', $uniqueLabels->phrasal) ?: 'None' }}<br>
            <strong>Clausal:</strong> {{ implode(', ', $uniqueLabels->clausal) ?: 'None' }}<br>
            <strong>Sentential:</strong> {{ implode(', ', $uniqueLabels->sentential) ?: 'None' }}
        </div>
    </div>
</form>
