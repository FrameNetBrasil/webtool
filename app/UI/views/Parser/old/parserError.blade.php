<div class="parser-error-container">
    <div class="ui negative message">
        <div class="header">Parse Error</div>
        <p>{{ $error }}</p>
    </div>

    <div class="ui message">
        <p><strong>Sentence:</strong> {{ $sentence }}</p>
    </div>

    <button class="ui button" onclick="document.getElementById('parserForm').reset(); document.getElementById('parseResults').innerHTML = '';">
        <i class="redo icon"></i>
        Try Again
    </button>
</div>

<style>
    .parser-error-container {
        margin-top: 2rem;
    }
</style>
