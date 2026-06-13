<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imaging_files', function (Blueprint $table) {
            if (! Schema::hasColumn('imaging_files', 'image_type')) {
                $table->string('image_type', 50)->nullable()->after('modality');
            }

            if (! Schema::hasColumn('imaging_files', 'eye')) {
                $table->string('eye', 5)->nullable()->after('image_type')
                    ->comment('Eye laterality: OD (right), OS (left), OU (both)');
            }

            if (! Schema::hasColumn('imaging_files', 'region')) {
                $table->string('region', 100)->nullable()->after('eye');
            }

            if (! Schema::hasColumn('imaging_files', 'image_label')) {
                $table->string('image_label', 255)->nullable()->after('region');
            }

            if (! Schema::hasColumn('imaging_files', 'thumbnail_path')) {
                $table->string('thumbnail_path', 500)->nullable()->after('file_path');
            }
        });

        Schema::table('imaging_files', function (Blueprint $table) {
            foreach (['image_type', 'eye', 'region'] as $column) {
                $indexName = "idx_imaging_files_{$column}";

                if (Schema::hasColumn('imaging_files', $column)
                    && ! $this->indexExists('imaging_files', $indexName)) {
                    $table->index($column, $indexName);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('imaging_files', function (Blueprint $table) {
            foreach (['image_type', 'eye', 'region'] as $column) {
                $indexName = "idx_imaging_files_{$column}";

                if ($this->indexExists('imaging_files', $indexName)) {
                    $table->dropIndex($indexName);
                }
            }

            foreach (['image_type', 'eye', 'region', 'image_label', 'thumbnail_path'] as $column) {
                if (Schema::hasColumn('imaging_files', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'SELECT COUNT(1) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return $result && (int) $result->aggregate > 0;
    }
};
