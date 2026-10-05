@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $statusStyles = [
            'pending' => ['label' => __('Pending'), 'class' => 'badge-soft-warning'],
            'processing' => ['label' => __('Processing'), 'class' => 'badge-soft-info'],
            'processed' => ['label' => __('Processed'), 'class' => 'badge-soft-success'],
            'failed' => ['label' => __('Failed'), 'class' => 'badge-soft-danger'],
            'requires_ocr' => ['label' => __('Needs OCR'), 'class' => 'badge-soft-dark'],
        ];

        $sourceStyles = [
            'product_resource' => __('Product Resource'),
            'note_attachment' => __('Note Attachment'),
            'manual_upload' => __('Manual Upload'),
        ];

        $fileTypeStyles = [
            'pdf' => ['label' => 'PDF', 'icon' => 'fa-file-pdf'],
            'doc' => ['label' => 'DOC', 'icon' => 'fa-file-word'],
            'docx' => ['label' => 'DOCX', 'icon' => 'fa-file-word'],
        ];
    @endphp

    <div class="ai-documents-page">
        <div class="ai-documents-hero">
            <div>
                <p class="ai-documents-hero__eyebrow">{{ __('Instructor AI Source Library') }}</p>
                <h2 class="ai-documents-hero__title">{{ __('PDF and Word documents, extraction status, and retry controls') }}</h2>
                <p class="ai-documents-hero__text">
                    {{ __('Upload PDFs, DOC files, or DOCX files from your instructor workspace, track extraction progress, and retry failed files without leaving the dashboard.') }}
                </p>
            </div>

            <div class="ai-documents-hero__actions">
                <form action="{{ route('instructor.ai-documents.upload') }}" method="POST" enctype="multipart/form-data" class="ai-upload-card">
                    @csrf
                    <strong>{{ __('Upload PDF or Word') }}</strong>
                    <input type="file" name="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="form-control" required>
                    <input type="text" name="source_name" class="form-control mt-2" placeholder="{{ __('Optional label') }}">
                    <select name="source_type" class="form-select mt-2">
                        <option value="manual_upload">{{ __('Manual Upload') }}</option>
                        <option value="product_resource">{{ __('Product Resource') }}</option>
                        <option value="note_attachment">{{ __('Note Attachment') }}</option>
                    </select>
                    <button type="submit" class="btn btn-primary w-100 mt-3">{{ __('Upload and Process') }}</button>
                </form>
            </div>
        </div>

        <div class="ai-documents-stats">
            <article><span>{{ __('Total') }}</span><strong>{{ number_format($stats['total']) }}</strong></article>
            <article><span>{{ __('Pending') }}</span><strong>{{ number_format($stats['pending']) }}</strong></article>
            <article><span>{{ __('Processing') }}</span><strong>{{ number_format($stats['processing']) }}</strong></article>
            <article><span>{{ __('Processed') }}</span><strong>{{ number_format($stats['processed']) }}</strong></article>
            <article><span>{{ __('Failed') }}</span><strong>{{ number_format($stats['failed']) }}</strong></article>
            <article><span>{{ __('Needs OCR') }}</span><strong>{{ number_format($stats['requires_ocr']) }}</strong></article>
        </div>

        <form method="GET" class="ai-documents-filters">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statusStyles as $key => $meta)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Source Type') }}</label>
                    <select name="source_type" class="form-select">
                        <option value="">{{ __('All sources') }}</option>
                        @foreach ($sourceStyles as $key => $label)
                            <option value="{{ $key }}" @selected(request('source_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-dark">{{ __('Filter') }}</button>
                    <a href="{{ route('instructor.ai-documents.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </div>
        </form>

        <div class="ai-documents-table card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Document') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Source') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Chunks') }}</th>
                                <th>{{ __('Pages') }}</th>
                                <th>{{ __('Uploaded') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                @php
                                    $statusMeta = $statusStyles[$document->status] ?? ['label' => ucfirst(str_replace('_', ' ', $document->status)), 'class' => 'badge-soft-secondary'];
                                @endphp
                                @php
                                    $fileExtension = strtolower((string) ($document->file_extension ?: pathinfo($document->original_path, PATHINFO_EXTENSION)));
                                    $fileTypeMeta = $fileTypeStyles[$fileExtension] ?? ['label' => strtoupper($fileExtension ?: __('File')), 'icon' => 'fa-file'];
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong>{{ $document->source_name }}</strong>
                                            <small class="text-muted">{{ $document->original_path }}</small>
                                            @if ($document->product)
                                                <small class="text-muted">{{ __('Product') }}: {{ $document->product->title }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="file-type-badge file-type-badge--{{ $fileExtension ?: 'file' }}">
                                            <span class="file-type-badge__icon">
                                                <i class="fas {{ $fileTypeMeta['icon'] ?? 'fa-file' }}"></i>
                                            </span>
                                            <span>{{ $fileTypeMeta['label'] }}</span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-secondary">{{ $sourceStyles[$document->source_type] ?? ucwords(str_replace('_', ' ', $document->source_type)) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                                    </td>
                                    <td>{{ number_format($document->chunks_count) }}</td>
                                    <td>{{ $document->page_count ? number_format($document->page_count) : '—' }}</td>
                                    <td>{{ formatDate($document->created_at, 'd M, Y H:i') }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="{{ route('instructor.ai-documents.show', $document->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                            @if (in_array($document->status, ['failed', 'requires_ocr', 'pending'], true))
                                                <form action="{{ route('instructor.ai-documents.reprocess', $document->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success">{{ __('Retry') }}</button>
                                                </form>
                                            @endif
                                            <form action="{{ route('instructor.ai-documents.destroy', $document->id) }}" method="POST" onsubmit="return confirm('{{ __('Delete this document?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">{{ __('No AI documents found yet.') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {{ $documents->withQueryString()->links() }}
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .ai-documents-page {
            display: grid;
            gap: 18px;
        }

        .ai-documents-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 18px;
            align-items: start;
        }

        .ai-documents-hero__eyebrow {
            margin: 0 0 8px;
            color: #73809b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .ai-documents-hero__title {
            margin: 0;
            color: #16213f;
            font-size: clamp(24px, 3vw, 32px);
            line-height: 1.08;
        }

        .ai-documents-hero__text {
            max-width: 760px;
            margin: 10px 0 0;
            color: #73809b;
        }

        .ai-upload-card {
            padding: 18px;
            border: 1px solid rgba(98, 117, 157, 0.14);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 16px 35px rgba(20, 33, 61, 0.05);
        }

        .ai-upload-card strong {
            display: block;
            margin-bottom: 12px;
            color: #16213f;
            font-size: 16px;
        }

        .ai-documents-stats {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
        }

        .ai-documents-stats article {
            padding: 16px;
            border: 1px solid rgba(98, 117, 157, 0.14);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 12px 28px rgba(20, 33, 61, 0.04);
        }

        .ai-documents-stats span {
            display: block;
            color: #73809b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .ai-documents-stats strong {
            display: block;
            margin-top: 8px;
            color: #16213f;
            font-size: 24px;
        }

        .ai-documents-filters,
        .ai-documents-table {
            border: 1px solid rgba(98, 117, 157, 0.14);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 16px 35px rgba(20, 33, 61, 0.05);
        }

        .ai-documents-filters {
            padding: 18px;
        }

        .badge-soft-warning,
        .badge-soft-info,
        .badge-soft-success,
        .badge-soft-danger,
        .badge-soft-dark,
        .badge-soft-secondary {
            padding: 7px 11px;
            border-radius: 999px;
            font-weight: 700;
        }

        .badge-soft-warning { background: rgba(246, 185, 59, 0.16); color: #b7791f; }
        .badge-soft-info { background: rgba(91, 141, 239, 0.14); color: #246bff; }
        .badge-soft-success { background: rgba(53, 199, 138, 0.14); color: #168c5c; }
        .badge-soft-danger { background: rgba(237, 109, 141, 0.14); color: #c81e4d; }
        .badge-soft-dark { background: rgba(30, 41, 59, 0.12); color: #1e293b; }
        .badge-soft-secondary { background: rgba(115, 128, 155, 0.12); color: #4b5563; }

        .table > :not(caption) > * > * {
            padding: 16px 18px;
            border-color: rgba(226, 232, 243, 0.8);
        }

        .table thead th {
            color: #73809b;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        @media (max-width: 1199.98px) {
            .ai-documents-hero,
            .ai-documents-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .ai-documents-hero,
            .ai-documents-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
