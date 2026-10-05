<?php

declare(strict_types=1);

namespace Tracefern\ImageCheck;

if (! defined('ABSPATH')) {
    exit;
}

use WP_Post;

/**
 * Shows the stored result: a list-mode column in the Media Library and a
 * row in the attachment details (Edit Media and the media modals), as
 * measured in notes/m2-where-to-show.md. WordPress inserts that row
 * unescaped; Display escapes everything.
 */
final class MediaScreens
{
    public const string COLUMN = 'tracefern';

    public function register(): void
    {
        add_filter('manage_media_columns', $this->addColumn(...));
        add_action('manage_media_custom_column', $this->renderColumn(...), 10, 2);
        add_filter('attachment_fields_to_edit', $this->addDetails(...), 10, 2);
        add_action('admin_enqueue_scripts', $this->enqueueStyle(...));
    }

    /**
     * The badges and rows (SPEC-002 amendment 1). Small, so on every admin
     * screen: the media modal can open anywhere.
     */
    public function enqueueStyle(): void
    {
        wp_enqueue_style(
            'tracefern-image-check-for-c2pa',
            plugins_url('assets/admin.css', dirname(__DIR__).'/tracefern-image-check-for-c2pa.php'),
            ['dashicons'],
            (string) filemtime(dirname(__DIR__).'/assets/admin.css'),
        );
    }

    /**
     * @param  array<string, string>  $columns
     * @return array<string, string>
     */
    public function addColumn(array $columns): array
    {
        $columns[self::COLUMN] = esc_html__('Content Credentials', 'tracefern-image-check-for-c2pa');

        return $columns;
    }

    public function renderColumn(string $column, int $attachmentId): void
    {
        // Only the formats the plugin checks get a verdict or a label (SPEC-015).
        if ($column === self::COLUMN && self::isChecked($attachmentId)) {
            $entry = self::entryOf($attachmentId);
            echo wp_kses_post(Display::headline($entry, self::pendingSince($attachmentId), time(), Display::changed($entry, self::currentOriginal($attachmentId))));
        }
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function addDetails(array $fields, WP_Post $post): array
    {
        if (! self::isChecked($post->ID)) {
            return $fields;
        }
        $entry = self::entryOf($post->ID);
        $fields[self::COLUMN] = [
            'label' => esc_html__('Content Credentials', 'tracefern-image-check-for-c2pa'),
            'input' => 'html',
            'html' => wp_kses_post(self::coverLine($post->ID).Display::details($entry, self::pendingSince($post->ID), time(), Display::changed($entry, self::currentOriginal($post->ID)))),
        ];

        return $fields;
    }

    /**
     * For an image WordPress made from audio cover art, which audio it is the
     * cover of (SPEC-034 AC7), so that its verdict is not read as the audio's.
     * WordPress marks such an image with `_cover_hash` and points each audio
     * at it with `_thumbnail_id`; one cover can serve several files. Titles
     * are escaped; empty for any other attachment.
     */
    private static function coverLine(int $attachmentId): string
    {
        if (get_post_meta($attachmentId, '_cover_hash', true) === '') {
            return '';
        }
        $audio = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'audio',
            'meta_key' => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- only for a cover image, on its details screen
            'meta_value' => (string) $attachmentId, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- as above
            'posts_per_page' => 4,
            'no_found_rows' => true,
            'orderby' => 'ID',
            'order' => 'ASC',
        ]);
        if ($audio === []) {
            return '';
        }
        $titles = array_map(
            /* translators: %s: an audio file's title, quoted as the language quotes it */
            static fn (WP_Post|int $post): string => sprintf(esc_html_x('“%s”', 'a quoted audio title', 'tracefern-image-check-for-c2pa'), esc_html(get_post_field('post_title', $post))),
            array_slice($audio, 0, 3),
        );
        $list = count($audio) > 3
            /* translators: %s: the titles of three audio files */
            ? sprintf(esc_html__('%s and more', 'tracefern-image-check-for-c2pa'), implode(', ', $titles))
            : implode(', ', $titles);

        /* translators: %s: the title(s) of the audio file(s) this image is the cover art of */
        return '<p class="tracefern-cover">'.sprintf(esc_html__('Cover of %s', 'tracefern-image-check-for-c2pa'), $list).'</p>';
    }

    /**
     * Whether the plugin checks this attachment's format at all.
     */
    private static function isChecked(int $attachmentId): bool
    {
        return in_array(get_post_mime_type($attachmentId), UploadHook::MIME_TYPES, true);
    }

    /**
     * The attachment's current original, relative to the uploads folder,
     * with its size and modification time (SPEC-014); null when unknown.
     *
     * @return array{path: string, size: int|false, modified: int|false}|null
     */
    public static function currentOriginal(int $attachmentId): ?array
    {
        $original = UploadHook::fileToCheck($attachmentId);
        if ($original === '') {
            return null;
        }
        $exists = is_file($original);

        return [
            'path' => UploadHook::relativeToUploads($original),
            'size' => $exists ? filesize($original) : false,
            'modified' => $exists ? filemtime($original) : false,
        ];
    }

    /**
     * When the attachment's background check was scheduled (SPEC-013), or null.
     */
    public static function pendingSince(int $attachmentId): ?int
    {
        $since = get_post_meta($attachmentId, UploadHook::PENDING_KEY, true);
        if (! is_numeric($since)) {
            return null;
        }

        // While the queue is scheduled, every marker is a check to come,
        // however long it has waited (SPEC-017).
        return UploadHook::queueIsScheduled() ? time() : (int) $since;
    }

    /**
     * The stored value, or null when the attachment has none.
     */
    public static function entryOf(int $attachmentId): mixed
    {
        return metadata_exists('post', $attachmentId, UploadHook::META_KEY)
            ? get_post_meta($attachmentId, UploadHook::META_KEY, true)
            : null;
    }
}
