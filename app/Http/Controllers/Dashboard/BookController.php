<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\BookContent;
use App\Enums\BookSize;
use App\Enums\MemoryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookPageRequest;
use App\Http\Requests\BookRequest;
use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\Memorial;
use App\Models\MemorialImage;
use App\Models\MemoryImage;
use App\Services\Book\BookComposer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function __construct(private readonly BookComposer $composer) {}

    public function index(Request $request): View|RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial();
        if (! $memorial) {
            return redirect()->route('dashboard.index');
        }

        $book = $memorial->book ?? new Book(['memorial_id' => $memorial->id]);

        // The form can preview options the owner has not saved yet.
        $content = $this->contentFrom($request, $book);
        $size = $this->sizeFrom($request, $book);

        return view('dashboard.book', [
            'memorial' => $memorial,
            'book' => $book,
            'content' => $content,
            'size' => $size,
            'pages' => $this->composer->compose($memorial, $content, $size, $book->overrides ?? [], $book->excluded()),
            'counts' => $this->counts($memorial),
            'openAt' => max(1, (int) $request->query('page', 1)),
            'removed' => $this->removedPhotos($memorial, $book),
        ]);
    }

    /** The preview pane on its own, so changing an option does not reload the page. */
    public function preview(Request $request): View|RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial();
        if (! $memorial) {
            return redirect()->route('dashboard.index');
        }

        $book = $memorial->book ?? new Book;
        $size = $this->sizeFrom($request, $book);

        return view('dashboard.partials._book_preview', [
            'size' => $size,
            'pages' => $this->composer->compose($memorial, $this->contentFrom($request, $book), $size, $book->overrides ?? [], $book->excluded()),
            'openAt' => max(1, (int) $request->query('page', 1)),
        ]);
    }

    public function update(BookRequest $request): RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $content = BookContent::from($request->string('content')->toString());
        $size = BookSize::from($request->string('size')->toString());
        $book = $this->bookFor($memorial);

        $book->fill([
            'content' => $content,
            'size' => $size,
            'copies' => $request->integer('copies'),
            'page_count' => count($this->composer->compose($memorial, $content, $size, $book->overrides ?? [], $book->excluded())),
        ])->save();

        ActivityLog::record('book.saved', $memorial);

        return redirect()
            ->route('dashboard.book', ['content' => $content->value, 'size' => $size->value])
            ->with('status', "הספר נשמר — {$book->page_count} עמודים, {$book->copies} עותקים. נעדכן אתכם כשההזמנה תיפתח.");
    }

    /** Rewrite the text on one page of the book. */
    public function updatePage(BookPageRequest $request): RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $book = $this->bookFor($memorial);
        $content = $this->contentFrom($request, $book);
        $size = $this->sizeFrom($request, $book);
        $key = $request->string('key')->toString();

        // Compose without the stored edits so we can tell an edit from the original.
        $original = collect($this->composer->compose($memorial, $content, $size, [], $book->excluded()))
            ->firstWhere('key', $key) ?? abort(404);

        $book->overridePage($key, $request->fields(), [
            'eyebrow' => $original->eyebrow,
            'title' => $original->title,
            'body' => $original->body,
            'caption' => $original->caption,
        ]);

        return redirect()
            ->route('dashboard.book', [
                'content' => $content->value,
                'size' => $size->value,
                'page' => $request->integer('page') ?: 1,
            ])
            ->with('status', 'העמוד עודכן.');
    }

    /** Take photos out of the book, or put them back. */
    public function updatePhotos(Request $request): RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $validated = $request->validate([
            'offered' => ['required', 'array', 'max:200'],
            'offered.*' => ['string', 'regex:/^(gallery|memory):\d+$/'],
            'remove' => ['nullable', 'array'],
            'remove.*' => ['string', 'regex:/^(gallery|memory):\d+$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'content' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
        ]);

        $book = $this->bookFor($memorial);
        $book->setPhotoExclusions($validated['offered'], $validated['remove'] ?? []);

        return redirect()
            ->route('dashboard.book', [
                'content' => $this->contentFrom($request, $book)->value,
                'size' => $this->sizeFrom($request, $book)->value,
                'page' => $validated['page'] ?? 1,
            ])
            ->with('status', 'התמונות בעמוד עודכנו.');
    }

    /** Undo every edit and let the book follow the memorial again. */
    public function resetPages(Request $request): RedirectResponse
    {
        $memorial = $request->user()->primaryMemorial() ?? abort(404);
        $this->authorize('update', $memorial);

        $this->bookFor($memorial)->forceFill(['overrides' => null, 'excluded_images' => null])->save();

        return redirect()->route('dashboard.book')->with('status', 'כל העריכות בוטלו והספר חזר לתוכן של העמוד.');
    }

    /**
     * The photos taken out of the book, so they can be put back.
     *
     * @return array<int,array{key:string,url:string}>
     */
    private function removedPhotos(Memorial $memorial, Book $book): array
    {
        $keys = $book->excluded();
        if ($keys === []) {
            return [];
        }

        $ids = fn (string $kind) => collect($keys)
            ->filter(fn ($k) => str_starts_with($k, "{$kind}:"))
            ->map(fn ($k) => (int) substr($k, strlen($kind) + 1))
            ->all();

        $photos = MemorialImage::whereIn('id', $ids('gallery'))
            ->where('memorial_id', $memorial->id)
            ->get()
            ->map(fn ($i) => ['key' => "gallery:{$i->id}", 'url' => $i->thumb_url]);

        $fromMemories = MemoryImage::whereIn('memory_images.id', $ids('memory'))
            ->whereHas('memory', fn ($q) => $q->where('memorial_id', $memorial->id))
            ->get()
            ->map(fn ($i) => ['key' => "memory:{$i->id}", 'url' => $i->thumb_url]);

        return $photos->concat($fromMemories)->values()->all();
    }

    private function bookFor(Memorial $memorial): Book
    {
        return $memorial->book()->firstOrCreate(['memorial_id' => $memorial->id]);
    }

    private function contentFrom(Request $request, Book $book): BookContent
    {
        return BookContent::tryFrom((string) $request->input('content')) ?? $book->content;
    }

    private function sizeFrom(Request $request, Book $book): BookSize
    {
        return BookSize::tryFrom((string) $request->input('size')) ?? $book->size;
    }

    /** @return array{memories:int,photos:int} */
    private function counts(Memorial $memorial): array
    {
        return [
            'memories' => $memorial->memories()->where('status', MemoryStatus::Approved->value)->count(),
            'photos' => $memorial->images()->count(),
        ];
    }
}
