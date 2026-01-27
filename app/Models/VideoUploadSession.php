<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VideoUploadSession Model
 *
 * Tracks chunked video upload sessions for resumable uploads.
 * This enables:
 * - Resumable uploads (continue from where left off)
 * - Parallel chunk uploads (faster speeds)
 * - Upload recovery after network failures
 *
 * @property int $id
 * @property int $user_id
 * @property string $session_id
 * @property string $file_name
 * @property string $file_path
 * @property int $file_size
 * @property string $mime_type
 * @property int $chunk_size
 * @property int $total_chunks
 * @property int $uploaded_chunks
 * @property string $status
 * @property array|null $uploaded_chunk_indices
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VideoUploadSession extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_UPLOADING = 'uploading';
    const STATUS_ASSEMBLING = 'assembling';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';

    const CHUNK_SIZE = 10 * 1024 * 1024; // 10MB per chunk

    protected $fillable = [
        'user_id',
        'session_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'chunk_size',
        'total_chunks',
        'uploaded_chunks',
        'uploaded_chunk_indices',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'chunk_size' => 'integer',
        'total_chunks' => 'integer',
        'uploaded_chunks' => 'integer',
        'uploaded_chunk_indices' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'chunk_size' => self::CHUNK_SIZE,
        'uploaded_chunks' => 0,
        'uploaded_chunk_indices' => '[]',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-expire old sessions
        static::creating(function ($session) {
            if (!$session->expires_at) {
                $session->expires_at = now()->addHours(24);
            }
        });
    }

    /**
     * Get the user who started the upload.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if upload is complete.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if upload is still active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_UPLOADING, self::STATUS_ASSEMBLING])
            && $this->expires_at->isFuture();
    }

    /**
     * Get completion percentage.
     *
     * @return float
     */
    public function getProgressAttribute(): float
    {
        if ($this->total_chunks === 0) {
            return 0;
        }
        return round(($this->uploaded_chunks / $this->total_chunks) * 100, 2);
    }

    /**
     * Mark a chunk as uploaded.
     *
     * @param int $chunkIndex
     * @return bool
     */
    public function markChunkUploaded(int $chunkIndex): bool
    {
        $indices = $this->uploaded_chunk_indices ?? [];

        if (!in_array($chunkIndex, $indices)) {
            $indices[] = $chunkIndex;
            $this->uploaded_chunk_indices = $indices;
            $this->uploaded_chunks = count($indices);
            $this->status = self::STATUS_UPLOADING;
            $this->save();
        }

        return true;
    }

    /**
     * Check if a chunk has been uploaded.
     *
     * @param int $chunkIndex
     * @return bool
     */
    public function isChunkUploaded(int $chunkIndex): bool
    {
        return in_array($chunkIndex, $this->uploaded_chunk_indices ?? []);
    }

    /**
     * Get missing chunks.
     *
     * @return array
     */
    public function getMissingChunks(): array
    {
        $uploaded = $this->uploaded_chunk_indices ?? [];
        $missing = [];

        for ($i = 0; $i < $this->total_chunks; $i++) {
            if (!in_array($i, $uploaded)) {
                $missing[] = $i;
            }
        }

        return $missing;
    }

    /**
     * Get next chunk to upload.
     *
     * @return int|null
     */
    public function getNextChunk(): ?int
    {
        $missing = $this->getMissingChunks();
        return !empty($missing) ? $missing[0] : null;
    }

    /**
     * Check if all chunks are uploaded.
     *
     * @return bool
     */
    public function allChunksUploaded(): bool
    {
        return $this->uploaded_chunks >= $this->total_chunks;
    }

    /**
     * Mark as assembling (final processing).
     *
     * @return void
     */
    public function markAssembling(): void
    {
        $this->status = self::STATUS_ASSEMBLING;
        $this->save();
    }

    /**
     * Mark as completed.
     *
     * @return void
     */
    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    /**
     * Mark as failed.
     *
     * @param string $reason
     * @return void
     */
    public function markFailed(string $reason = 'Upload failed'): void
    {
        $this->status = self::STATUS_FAILED;
        $this->save();
    }

    /**
     * Get the full temporary file path for assembling chunks.
     *
     * @return string
     */
    public function getTempFilePath(): string
    {
        return storage_path('app/public/upload_sessions/' . $this->session_id . '.tmp');
    }

    /**
     * Clean up temporary files.
     *
     * @return void
     */
    public function cleanup(): void
    {
        $tempPath = $this->getTempFilePath();

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }

        // Clean up individual chunk files
        for ($i = 0; $i < $this->total_chunks; $i++) {
            $chunkPath = storage_path('app/public/upload_chunks/' . $this->session_id . '_' . $i . '.chunk');
            if (file_exists($chunkPath)) {
                unlink($chunkPath);
            }
        }
    }

    /**
     * Scope for active sessions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_UPLOADING, self::STATUS_ASSEMBLING])
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for user's sessions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for expired sessions.
     *
     * @param \Illuminate\Database\Eloquent.Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->orWhere('status', self::STATUS_FAILED);
    }
}


