<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;

class DocsService extends Controller
{
    /**
     * Get the base path for documentation files
     */
    private static function getDocsPath(): string
    {
        return app_path('UI/views/Documentation');
    }

    /**
     * Parse YAML frontmatter from markdown file
     */
    private static function parseFrontmatter(string $filePath): array
    {
        if (! File::exists($filePath)) {
            return [];
        }

        try {
            $document = YamlFrontMatter::parseFile($filePath);

            return $document->matter();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get folder metadata from _folder.yml file
     */
    private static function getFolderMetadata(string $folderPath): array
    {
        $metaFile = $folderPath.'/_folder.yml';

        if (! File::exists($metaFile)) {
            return [];
        }

        try {
            $content = File::get($metaFile);

            return Yaml::parse($content) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Sort items by order field, then alphabetically
     */
    private static function sortByOrder(array $items): array
    {
        usort($items, function ($a, $b) {
            // Get order values, default to PHP_INT_MAX if not set
            $orderA = $a['order'] ?? PHP_INT_MAX;
            $orderB = $b['order'] ?? PHP_INT_MAX;

            // Sort by order first
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            // Fall back to alphabetical sorting by text
            return strcasecmp($a['text'], $b['text']);
        });

        return $items;
    }

    /**
     * Check if a folder has a _main.md file (indicates it's a parent page with children)
     */
    private static function hasMainPage(string $folderPath): bool
    {
        return File::exists($folderPath.'/_main.md');
    }

    /**
     * Get children pages from a parent folder (excluding _main.md)
     */
    private static function getChildrenPages(string $folderPath, string $relativePath): array
    {
        $children = [];
        $files = File::files($folderPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md' || $file->getFilename() === '_main.md') {
                continue;
            }

            $fileName = $file->getFilename();
            $childRelativePath = $relativePath.'/'.$fileName;
            $frontmatter = self::parseFrontmatter($file->getPathname());

            $children[] = [
                'id' => md5($childRelativePath),
                'type' => 'document',
                'text' => $frontmatter['title'] ?? self::formatName(Str::replace('.md', '', $fileName)),
                'path' => $childRelativePath,
                'leaf' => true,
                'order' => $frontmatter['order'] ?? PHP_INT_MAX,
            ];
        }

        return self::sortByOrder($children);
    }

    /**
     * Build hierarchical tree structure from documentation folder
     */
    public static function buildTree(?string $parentPath = null): array
    {
        $basePath = self::getDocsPath();
        $currentPath = $parentPath ? $basePath.'/'.$parentPath : $basePath;

        if (! File::exists($currentPath)) {
            return [];
        }

        $folders = [];
        $documents = [];
        $directories = File::directories($currentPath);
        $files = File::files($currentPath);

        // Process directories first
        foreach ($directories as $directory) {
            $dirName = basename($directory);

            // Skip metadata files
            if ($dirName === '_folder.yml') {
                continue;
            }

            $relativePath = $parentPath ? $parentPath.'/'.$dirName : $dirName;
            $metadata = self::getFolderMetadata($directory);
            $hasMain = self::hasMainPage($directory);

            if ($hasMain) {
                // This is a parent page with children
                $mainPath = $relativePath.'/_main.md';
                $mainFrontmatter = self::parseFrontmatter($directory.'/_main.md');
                $children = self::getChildrenPages($directory, $relativePath);

                $documents[] = [
                    'id' => md5($relativePath),
                    'type' => 'parent',
                    'text' => $mainFrontmatter['title'] ?? $metadata['title'] ?? self::formatName($dirName),
                    'path' => $relativePath,  // Path without _main.md
                    'mainPath' => $mainPath,  // Actual file path
                    'leaf' => false,
                    'state' => 'closed',
                    'children' => $children,
                    'order' => $mainFrontmatter['order'] ?? $metadata['order'] ?? PHP_INT_MAX,
                ];
            } else {
                // Regular folder without main page
                $folders[] = [
                    'id' => md5($relativePath),
                    'type' => 'folder',
                    'text' => $metadata['title'] ?? self::formatName($dirName),
                    'path' => $relativePath,
                    'leaf' => false,
                    'state' => 'closed',
                    'order' => $metadata['order'] ?? PHP_INT_MAX,
                ];
            }
        }

        // Process markdown files
        foreach ($files as $file) {
            if ($file->getExtension() !== 'md' || $file->getFilename() === '_main.md') {
                continue;
            }

            $fileName = $file->getFilename();
            $relativePath = $parentPath ? $parentPath.'/'.$fileName : $fileName;
            $frontmatter = self::parseFrontmatter($file->getPathname());

            $documents[] = [
                'id' => md5($relativePath),
                'type' => 'document',
                'text' => $frontmatter['title'] ?? self::formatName(Str::replace('.md', '', $fileName)),
                'path' => $relativePath,
                'leaf' => true,
                'order' => $frontmatter['order'] ?? PHP_INT_MAX,
            ];
        }

        // Merge folders and documents
        $items = array_merge($folders, $documents);

        // Sort all items together by order
        $items = self::sortByOrder($items);

        // Remove order field before returning (internal use only)
        $items = array_map(fn ($item) => array_diff_key($item, ['order' => '']), $items);

        return $items;
    }

    /**
     * Get document content and metadata
     */
    public static function getDocument(string $path): array
    {
        $basePath = self::getDocsPath();
        $filePath = $basePath.'/'.$path;

        // Check if path is a folder with _main.md
        if (File::isDirectory($filePath) && File::exists($filePath.'/_main.md')) {
            $filePath = $filePath.'/_main.md';
            $path = $path.'/_main.md';
        }

        // If path doesn't end with .md and it's not a directory, try adding .md
        if (! str_ends_with($path, '.md') && ! File::isDirectory($filePath)) {
            $filePath .= '.md';
            $path .= '.md';
        }

        if (! File::exists($filePath)) {
            return [
                'found' => false,
                'content' => null,
                'html' => null,
                'title' => null,
                'toc' => [],
                'breadcrumbs' => [],
            ];
        }

        $frontmatter = self::parseFrontmatter($filePath);
        $markdownContent = File::get($filePath);

        // Remove frontmatter from markdown content for rendering
        if (! empty($frontmatter)) {
            try {
                $document = YamlFrontMatter::parseFile($filePath);
                $markdownContent = $document->body();
            } catch (\Exception $e) {
                // Fall back to full content if parsing fails
            }
        }

        $html = Str::markdown($markdownContent, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        // Process Graphviz diagrams
        $html = self::renderGraphvizDiagrams($html);

        return [
            'found' => true,
            'content' => $markdownContent,
            'html' => $html,
            'title' => $frontmatter['title'] ?? self::extractTitle($markdownContent),
            'toc' => self::extractTableOfContents($markdownContent),
            'breadcrumbs' => self::getBreadcrumbs($path),
        ];
    }

    /**
     * Build a flat ordered list of all documentation pages for global navigation
     */
    public static function buildGlobalOrder(): array
    {
        $flatList = [];
        $tree = self::buildTree();

        self::flattenTree($tree, $flatList);

        return $flatList;
    }

    /**
     * Recursively flatten the tree into a flat list of pages
     */
    private static function flattenTree(array $tree, array &$flatList): void
    {
        foreach ($tree as $item) {
            if ($item['type'] === 'folder') {
                // Recursively process folder contents
                $children = self::buildTree($item['path']);
                self::flattenTree($children, $flatList);
            } elseif ($item['type'] === 'parent') {
                // Add parent page
                $flatList[] = [
                    'path' => $item['path'],
                    'title' => $item['text'],
                ];

                // Add children pages
                if (! empty($item['children'])) {
                    foreach ($item['children'] as $child) {
                        $flatList[] = [
                            'path' => Str::replace('.md', '', $child['path']),
                            'title' => $child['text'],
                        ];
                    }
                }
            } elseif ($item['type'] === 'document') {
                // Add regular document
                $flatList[] = [
                    'path' => Str::replace('.md', '', $item['path']),
                    'title' => $item['text'],
                ];
            }
        }
    }

    /**
     * Get the previous page in global navigation order
     */
    public static function getPreviousPage(string $currentPath): ?array
    {
        $pages = self::buildGlobalOrder();
        $normalizedPath = Str::replace('.md', '', Str::replace('/_main.md', '', $currentPath));

        foreach ($pages as $index => $page) {
            if ($page['path'] === $normalizedPath && $index > 0) {
                return $pages[$index - 1];
            }
        }

        return null;
    }

    /**
     * Get the next page in global navigation order
     */
    public static function getNextPage(string $currentPath): ?array
    {
        $pages = self::buildGlobalOrder();
        $normalizedPath = Str::replace('.md', '', Str::replace('/_main.md', '', $currentPath));

        foreach ($pages as $index => $page) {
            if ($page['path'] === $normalizedPath && $index < count($pages) - 1) {
                return $pages[$index + 1];
            }
        }

        return null;
    }

    /**
     * Get default (first) document
     */
    public static function getDefaultDocument(): ?string
    {
        $docsPath = self::getDocsPath();

        // Look for getting-started.md first
        if (File::exists($docsPath.'/getting-started.md')) {
            return 'getting-started.md';
        }

        // Otherwise, get first .md file
        $files = File::files($docsPath);
        foreach ($files as $file) {
            if ($file->getExtension() === 'md') {
                return $file->getFilename();
            }
        }

        return null;
    }

    /**
     * Search documentation files
     */
    public static function searchDocs(string $query): array
    {
        if (empty(trim($query))) {
            return [];
        }

        $results = [];
        $docsPath = self::getDocsPath();
        $allFiles = File::allFiles($docsPath);
        $query = strtolower($query);

        foreach ($allFiles as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $content = File::get($file->getPathname());
            $relativePath = Str::replace($docsPath.'/', '', $file->getPathname());

            // Search in filename and content
            $filename = strtolower($file->getFilename());
            $contentLower = strtolower($content);

            if (str_contains($filename, $query) || str_contains($contentLower, $query)) {
                // Extract context around match
                $context = self::extractSearchContext($content, $query);

                $results[] = [
                    'id' => md5($relativePath),
                    'type' => 'document',
                    'text' => self::formatName(Str::replace('.md', '', $file->getFilename())),
                    'path' => $relativePath,
                    'context' => $context,
                    'leaf' => true,
                ];
            }
        }

        return $results;
    }

    /**
     * Extract table of contents from Markdown headings
     */
    private static function extractTableOfContents(string $markdown): array
    {
        $toc = [];
        $lines = explode("\n", $markdown);

        foreach ($lines as $line) {
            // Match headings (## or ###, skip # for main title)
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]);
                $text = $matches[2];
                $slug = Str::slug($text);

                $toc[] = [
                    'level' => $level,
                    'text' => $text,
                    'slug' => $slug,
                ];
            }
        }

        return $toc;
    }

    /**
     * Extract title from Markdown (first # heading)
     */
    private static function extractTitle(string $markdown): ?string
    {
        $lines = explode("\n", $markdown);

        foreach ($lines as $line) {
            if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Generate breadcrumbs from file path
     */
    private static function getBreadcrumbs(string $path): array
    {
        $breadcrumbs = [
            ['text' => 'Documentation', 'path' => null],
        ];

        $parts = explode('/', $path);
        $currentPath = '';

        foreach ($parts as $index => $part) {
            $currentPath .= ($currentPath ? '/' : '').$part;
            $isLast = $index === count($parts) - 1;

            $text = self::formatName(Str::replace('.md', '', $part));

            $breadcrumbs[] = [
                'text' => $text,
                'path' => $isLast ? null : $currentPath,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Format folder/file name for display
     */
    private static function formatName(string $name): string
    {
        return Str::of($name)
            ->replace('-', ' ')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    /**
     * Extract context around search match
     */
    private static function extractSearchContext(string $content, string $query, int $contextLength = 150): string
    {
        $content = strip_tags(Str::markdown($content));
        $pos = stripos($content, $query);

        if ($pos === false) {
            return Str::limit($content, $contextLength);
        }

        $start = max(0, $pos - $contextLength / 2);
        $excerpt = substr($content, $start, $contextLength);

        if ($start > 0) {
            $excerpt = '...'.$excerpt;
        }

        if ($start + $contextLength < strlen($content)) {
            $excerpt .= '...';
        }

        return $excerpt;
    }

    /**
     * Process and render Graphviz diagrams in HTML
     */
    private static function renderGraphvizDiagrams(string $html): string
    {
        // Match code blocks with language-dot or language-graphviz classes
        $pattern = '/<pre><code class="language-(dot|graphviz)">(.*?)<\/code><\/pre>/is';

        return preg_replace_callback($pattern, function ($matches) {
            $dotCode = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5);

            try {
                // Use Graphviz command-line tool to render DOT to SVG
                $process = new Process(['dot', '-Tsvg']);
                $process->setInput($dotCode);
                $process->setTimeout(30);
                $process->run();

                if (! $process->isSuccessful()) {
                    throw new \RuntimeException($process->getErrorOutput());
                }

                $svg = $process->getOutput();

                // Wrap SVG in a container div for styling and interactivity
                return '<div class="graphviz-diagram-wrapper">'
                    .'<div class="graphviz-diagram">'.$svg.'</div>'
                    .'</div>';
            } catch (\Exception $e) {
                // If rendering fails, return error message with original code
                return '<div class="graphviz-error">'
                    .'<p><strong>Graphviz rendering error:</strong> '.htmlspecialchars($e->getMessage()).'</p>'
                    .'<pre><code class="language-dot">'.htmlspecialchars($dotCode).'</code></pre>'
                    .'</div>';
            }
        }, $html);
    }
}
