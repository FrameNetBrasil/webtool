<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Assuming you already have these tables:
        // - lexicon (idLexicon, form, idUDPOS)
        // - udpos (idUDPOS, POS)
        // - udrelation (idUDRelation, info)

        // Store MWE patterns - each MWE in lexicon can have multiple pattern variants
        Schema::create('lexicon_pattern', function (Blueprint $table) {
            $table->integerIncrements('idLexiconPattern');
            $table->unsignedInteger('idLexicon');
            $table->string('patternType')->default('canonical'); // canonical, variant, etc.
            $table->timestamps();

            $table->foreign('idLexicon')->references('idLexicon')->on('lexicon')->onDelete('cascade');
            $table->unique(['idLexicon', 'patternType']);
        });

        // Store nodes in the dependency tree for each pattern
        // Each node represents a token in the MWE structure
        Schema::create('lexicon_pattern_node', function (Blueprint $table) {
            $table->integerIncrements('idLexiconPatternNode');
            $table->unsignedInteger('idLexiconPattern');
            $table->integer('position'); // position in the pattern (0, 1, 2, ...)
            $table->unsignedInteger('idLexicon')->nullable(); // lemma for this node
            $table->unsignedInteger('idUDPOS')->nullable(); // POS for this node
            $table->boolean('isRoot')->default(false); // is this the root node?
            $table->boolean('isRequired')->default(true); // must match or optional?
            $table->timestamps();

            $table->foreign('idLexiconPattern')->references('idLexiconPattern')->on('lexicon_pattern')->onDelete('cascade');
            $table->foreign('idLexicon')->references('idLexicon')->on('lexicon')->onDelete('set null');
            $table->foreign('idUDPOS')->references('idUDPOS')->on('udpos')->onDelete('set null');

            $table->unique(['idLexiconPattern', 'position']);
            $table->index(['idLexiconPattern', 'isRoot']);
        });

        // Store ALL dependency edges in the pattern tree
        // This captures the complete dependency structure, not just root → children
        Schema::create('lexicon_pattern_edge', function (Blueprint $table) {
            $table->integerIncrements('idLexiconPatternEdge');
            $table->unsignedInteger('idLexiconPattern');
            $table->unsignedInteger('idNodeHead'); // head node
            $table->unsignedInteger('idNodeDependent'); // dependent node
            $table->unsignedInteger('idUDRelation'); // dependency relation
            $table->timestamps();

            $table->foreign('idLexiconPattern')->references('idLexiconPattern')->on('lexicon_pattern')->onDelete('cascade');
            $table->foreign('idNodeHead')->references('idLexiconPatternNode')->on('lexicon_pattern_node')->onDelete('cascade');
            $table->foreign('idNodeDependent')->references('idLexiconPatternNode')->on('lexicon_pattern_node')->onDelete('cascade');
            $table->foreign('idUDRelation')->references('idUDRelation')->on('udrelation')->onDelete('cascade');

            $table->unique(['idLexiconPattern', 'idNodeHead', 'idNodeDependent'], 'idx_lexicon_pattern_edge_head_dep');
            $table->index(['idLexiconPattern', 'idUDRelation']);
        });

        // Store additional constraints for patterns (optional, for fine-tuning)
        Schema::create('lexicon_pattern_constraint', function (Blueprint $table) {
            $table->integerIncrements('idLexiconPatternConstraint');
            $table->unsignedInteger('idLexiconPattern');
            $table->string('constraintType'); // word_order, max_distance, inflection, etc.
            $table->text('constraintValue');
            $table->timestamps();

            $table->foreign('idLexiconPattern')->references('idLexiconPattern')->on('lexicon_pattern')->onDelete('cascade');
            $table->index(['idLexiconPattern', 'constraintType']);
        });

        // Store detected MWE/SWE occurrences in sentences
        //        Schema::create('lexicon_occurrences', function (Blueprint $table) {
        //            $table->id('idOccurrence');
        //            $table->string('sentence_id'); // external sentence identifier
        //            $table->unsignedInteger('idLexicon');
        //            $table->unsignedInteger('idLexiconPattern')->nullable(); // which pattern matched (null for SWE)
        //            $table->json('token_indices'); // JSON array of token positions in sentence
        //            $table->json('matched_nodes')->nullable(); // JSON mapping pattern nodes to sentence tokens
        //            $table->decimal('confidence', 3, 2)->default(1.0);
        //            $table->timestamps();
        //
        //            $table->foreign('idLexicon')->references('idLexicon')->on('lexicon')->onDelete('cascade');
        //            $table->foreign('idLexiconPattern')->references('idLexiconPattern')->on('mwe_patterns')->onDelete('set null');
        //
        //            $table->index(['sentence_id']);
        //            $table->index(['idLexicon']);
        //            $table->index(['confidence']);
        //        });

        // Add a frequency column to lexicon if not exists
        // This tracks how many times each lexicon entry has been found
        if (! Schema::hasColumn('lexicon', 'frequency')) {
            Schema::table('lexicon', function (Blueprint $table) {
                $table->integer('frequency')->default(0)->after('idUDPOS');
            });
        }

        // Add a lemma_type column to lexicon if not exists
        // This distinguishes between SWE and MWE
        //        if (!Schema::hasColumn('lexicon', 'lemma_type')) {
        //            Schema::table('lexicon', function (Blueprint $table) {
        //                $table->enum('lemma_type', ['SWE', 'MWE'])->default('SWE')->after('idUDPOS');
        //                $table->index(['lemma_type']);
        //            });
        //        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('lexicon_occurrences');
        Schema::dropIfExists('lexicon_pattern_constraint');
        Schema::dropIfExists('lexicon_pattern_edge');
        Schema::dropIfExists('lexicon_pattern_node');
        Schema::dropIfExists('lexicon_pattern');

        // Remove added columns from lexicon
        if (Schema::hasColumn('lexicon', 'frequency')) {
            Schema::table('lexicon', function (Blueprint $table) {
                $table->dropColumn('frequency');
            });
        }

        //        if (Schema::hasColumn('lexicon', 'lemma_type')) {
        //            Schema::table('lexicon', function (Blueprint $table) {
        //                $table->dropColumn('lemma_type');
        //            });
        //        }
    }
};
