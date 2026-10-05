<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CatalogAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductDownloadController extends Controller
{
    /**
     * Return a short-lived link for a purchased product file.
     */
    public function create(string $productType, string $productId): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'UnAuthenticated'], 401);
        }

        $product = $this->findDownloadableProduct($productType, $this->normalizeProductId($productId));

        if (! $this->userCanDownload($product, $user)) {
            return response()->json(['status' => 'error', 'message' => 'You do not have access to this product.'], 403);
        }

        if ($this->source($product) === null) {
            return response()->json(['status' => 'error', 'message' => 'The product file is not available.'], 404);
        }

        $expiresAt = now()->addMinutes(10);

        return response()->json([
            'status' => 'success',
            'message' => 'Product download URL generated successfully.',
            'data' => [
                'download_url' => URL::temporarySignedRoute('api.products.download.file', $expiresAt, [
                    'product_type' => $product->type,
                    'product_id' => $product->id,
                ]),
                'filename' => $this->filename($product),
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }

    /**
     * Stream the file after Laravel has validated the signed URL.
     */
    public function file(string $productType, int $productId): BinaryFileResponse|RedirectResponse
    {
        $product = $this->findDownloadableProduct($productType, $productId);
        $source = $this->source($product);

        abort_unless($source !== null, 404);

        if ($source['kind'] === 'remote') {
            return redirect()->away($source['url']);
        }

        return response()->download($source['path'], $this->filename($product));
    }

    private function findDownloadableProduct(string $productType, int $productId): Product
    {
        return Product::active()
            ->where('type', $productType)
            ->whereKey($productId)
            ->firstOrFail();
    }

    /**
     * Keep compatibility with clients that encode an integer resource ID as a
     * decimal (such as "27.0"), without accepting fractional IDs.
     */
    private function normalizeProductId(string $productId): int
    {
        if (! preg_match('/^([0-9]+)(?:\\.0+)?$/', $productId, $matches)) {
            abort(404);
        }

        return (int) $matches[1];
    }

    private function userCanDownload(Product $product, $user): bool
    {
        return app(CatalogAccessService::class)->product($user, $product)['allowed'];
    }

    private function source(Product $product): ?array
    {
        $filePath = trim((string) $product->file_path);

        if ($filePath === '') {
            return null;
        }

        if (filter_var($filePath, FILTER_VALIDATE_URL)) {
            return ['kind' => 'remote', 'url' => $filePath];
        }

        // Product resources uploaded through Bunny are saved as relative paths
        // (for example, instructors/12/products/past_paper/27/source/file.pdf),
        // rather than as an absolute URL or a file beneath public/.
        $storageCdnUrl = rtrim((string) config('bunny.storage_cdn_url'), '/');
        if ($storageCdnUrl !== '' && Str::startsWith($filePath, ['instructors/', 'admins/'])) {
            return ['kind' => 'remote', 'url' => $storageCdnUrl . '/' . ltrim($filePath, '/')];
        }

        $publicDirectory = realpath(public_path());
        $absolutePath = realpath(public_path($filePath));

        if ($publicDirectory === false || $absolutePath === false
            || ! Str::startsWith($absolutePath, $publicDirectory . DIRECTORY_SEPARATOR)
            || ! is_file($absolutePath)) {
            return null;
        }

        return ['kind' => 'local', 'path' => $absolutePath];
    }

    private function filename(Product $product): string
    {
        $extension = strtolower((string) pathinfo((string) $product->file_path, PATHINFO_EXTENSION));
        $extension = $extension !== '' ? $extension : strtolower((string) $product->file_type);

        return Str::slug($product->title) . ($extension !== '' ? '.' . $extension : '');
    }
}
