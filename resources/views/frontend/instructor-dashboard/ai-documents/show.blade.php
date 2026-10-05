@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $statusMeta = [
            'pending' => ['label' => __('Pending'), 'class' => 'badge-soft-warning'],
            'processing' => ['label' => __('Processing'), 'class' => 'badge-soft-info'],
            'processed' => ['label' => __('Processed'), 'class' => 'badge-soft-success'],
            'failed' => ['label' => __('Failed'), 'class' => 'badge-soft-danger'],
            'requires_ocr' => ['label' => __('Needs OCR'), 'class' => 'badge-soft-dark'],
        ][$document->status] ?? ['label' => ucfirst(str_replace('_', ' ', $document->status)), 'class' => 'badge-soft-secondary'];

        $fileExtension = strtolower((string) ($document->file_extension ?: pathinfo($document->original_path, PATHINFO_EXTENSION)));
        $fileTypeMeta = [
            'pdf' => ['label' => 'PDF', 'icon' => 'fa-file-pdf'],
            'doc' => ['label' => 'DOC', 'icon' => 'fa-file-word'],
            'docx' => ['label' => 'DOCX', 'icon' => 'fa-file-word'],
        ][$fileExtension] ?? ['label' => strtoupper($fileExtension ?: __('File')), 'icon' => 'fa-file'];
    @endphp

    <div class="ai-document-detail">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <p class="text-uppercase text-muted fw-bold mb-1" style="letter-spacing: .08em;">{{ __('AI Document') }}</p>
                <h2 class="mb-2">{{ $document->source_name }}</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                    <span class="file-type-badge file-type-badge--{{ $fileExtension ?: 'file' }}">
                        <span class="file-type-badge__icon">
                            <i class="fas {{ $fileTypeMeta['icon'] ?? 'fa-file' }}"></i>
                        </span>
                        <span>{{ $fileTypeMeta['label'] }}</span>
                    </span>
                    <span class="badge badge-soft-secondary">{{ $document->source_type }}</span>
                    <span class="text-muted">{{ formatDate($document->created_at, 'd M, Y H:i') }}</span>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('instructor.ai-documents.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
                @if (in_array($document->status, ['failed', 'requires_ocr', 'pending'], true))
                    <form action="{{ route('instructor.ai-documents.reprocess', $document->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success">{{ __('Retry') }}</button>
                    </form>
                @endif
                <form action="{{ route('instructor.ai-documents.destroy', $document->id) }}" method="POST" onsubmit="return confirm('{{ __('Delete this document?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">{{ __('Processing Summary') }}</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="ai-summary-box">
                                    <span>{{ __('Pages') }}</span>
                                    <strong>{{ $document->page_count ? number_format($document->page_count) : '—' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ai-summary-box">
                                    <span>{{ __('Characters') }}</span>
                                    <strong>{{ $document->character_count ? number_format($document->character_count) : '—' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ai-summary-box">
                                    <span>{{ __('Chunks') }}</span>
                                    <strong>{{ number_format($document->chunks->count()) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="ai-summary-box">
                                    <span>{{ __('Failed At') }}</span>
                                    <strong>{{ $document->failed_at ? formatDate($document->failed_at, 'd M, Y H:i') : '—' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="mb-2">{{ __('Text Excerpt') }}</h6>
                            <div class="ai-previewer">
                                {{ $document->extracted_text_excerpt ?: __('No excerpt available yet.') }}
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="mb-2">{{ __('Metadata') }}</h6>
                            <pre class="ai-code-block mb-0">{{ json_encode($document->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="mb-3">{{ __('Chunks') }}</h5>
                        @forelse ($document->chunks as $chunk)
                            <div class="ai-chunk-card">
                                <div class="d-flex justify-content-between gap-3">
                                    <strong>{{ __('Chunk :index', ['index' => $chunk->chunk_index + 1]) }}</strong>
                                    <span class="text-muted">{{ __('Pages') }}: {{ $chunk->page_start && $chunk->page_end ? $chunk->page_start . ' - ' . $chunk->page_end : '—' }}</span>
                                </div>
                                <p class="mt-2 mb-0">{{ \Illuminate\Support\Str::limit($chunk->content, 420) }}</p>
                            </div>
                        @empty
                            <div class="text-muted">{{ __('No chunks have been created yet.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">{{ __('Source Details') }}</h5>
                        <dl class="ai-detail-list">
                            <div>
                                <dt>{{ __('Original Path') }}</dt>
                                <dd>{{ $document->original_path }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Bunny Folder') }}</dt>
                                <dd>{{ $document->bunny_folder_path ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Mime Type') }}</dt>
                                <dd>{{ $document->mime_type ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('File Type') }}</dt>
                                <dd>{{ strtoupper($document->file_extension ?: pathinfo($document->original_path, PATHINFO_EXTENSION) ?: '—') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('File Hash') }}</dt>
                                <dd class="text-break">{{ $document->file_hash }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Failure Reason') }}</dt>
                                <dd>{{ $document->failure_reason ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="mb-3">{{ __('Processing Log') }}</h5>
                        <div class="ai-log-list">
                            @forelse ($document->processingLogs as $log)
                                <div class="ai-log-item">
                                    <strong>{{ $log->stage }}</strong>
                                    <small>{{ formatDate($log->created_at, 'd M, Y H:i') }}</small>
                                    <p class="mb-0">{{ $log->message }}</p>
                                </div>
                            @empty
                                <div class="text-muted">{{ __('No logs yet.') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .ai-summary-box,
        .ai-previewer,
        .ai-code-block,
        .ai-chunk-card,
        .ai-log-item,
        .ai-detail-list dd {
            border: 1px solid rgba(98, 117, 157, 0.14);
            border-radius: 14px;
            background: #fff;
        }

        .ai-summary-box {
            padding: 14px;
            min-height: 90px;
        }

        .ai-summary-box span {
            display: block;
            color: #73809b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .ai-summary-box strong {
            display: block;
            margin-top: 8px;
            font-size: 22px;
            color: #16213f;
        }

        .ai-previewer {
            padding: 16px;
            color: #384462;
            white-space: pre-wrap;
        }

        .ai-code-block {
            padding: 16px;
            color: #384462;
            background: #f8fafc;
        }

        .ai-chunk-card {
            padding: 14px;
            margin-bottom: 12px;
        }

        .ai-detail-list dt {
            color: #73809b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .ai-detail-list dd {
            padding: 10px 12px;
            color: #16213f;
            margin-bottom: 12px;
        }

        .ai-log-item {
            padding: 12px;
            margin-bottom: 12px;
        }

        .ai-log-item strong {
            display: block;
            color: #16213f;
            text-transform: capitalize;
        }

        .ai-log-item small {
            display: block;
            color: #73809b;
            margin: 3px 0 8px;
        }
    </style>
@endpush
