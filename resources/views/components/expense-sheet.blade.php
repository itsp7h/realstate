{{-- ═══════════════════════ RECORD EXPENSE (bottom sheet) ═══════════════════════
     Shared across every mobile screen that shows the gold FAB (Home, Properties, ...).
     Reuses the app-wide .modal-overlay/.modal-box bottom-sheet system so it slides
     up from the bottom on mobile and centers on desktop, same as every other modal. --}}
@php
    $expenseCategories = \App\Models\Expense::CATEGORIES;
    $expenseCategoryIcons = [
        'repairs_maintenance' => 'fa-solid fa-screwdriver-wrench',
        'utilities'           => 'fa-solid fa-bolt',
        'insurance'           => 'fa-solid fa-shield-halved',
        'municipality_fees'   => 'fa-solid fa-landmark',
        'cleaning'            => 'fa-solid fa-broom',
        'security'            => 'fa-solid fa-user-shield',
        'management_fees'     => 'fa-solid fa-briefcase',
        'other'               => 'fa-solid fa-receipt',
    ];
    $expenseBuildings = \App\Models\Building::orderBy('property_name')->get(['id', 'property_name']);
@endphp

{{-- Self-contained base modal CSS: unlike the app's other .modal-overlay
     instances, this component gets included on pages that may not already
     define their own base positioning for that class, so it can't rely on
     one existing. Scoped to #expenseModal so it never conflicts with
     whatever base a host page (like buildings/index) already has. --}}
<style>
    /* Base positioning only — deliberately omits align-items/padding, which
       the shared mobile bottom-sheet override (plain .modal-overlay class,
       in admin.blade.php) controls. Since this rule is ID-scoped it has
       higher specificity and would otherwise always beat that override
       regardless of viewport. */
    #expenseModal.modal-overlay {
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(11, 17, 32, 0.55);
        display: flex; justify-content: center;
        opacity: 0; pointer-events: none;
        transition: opacity 0.25s ease;
    }
    #expenseModal.modal-overlay.open { opacity: 1; pointer-events: all; }
    @media (min-width: 769px) {
        #expenseModal.modal-overlay { align-items: center; padding: 20px; }
    }
    #expenseModal .modal-box {
        background: var(--card-bg); border: 1px solid var(--card-border);
        border-radius: 16px; box-shadow: 0 24px 60px rgba(0,0,0,0.18);
        width: 100%; max-height: 90vh; display: flex; flex-direction: column;
        transform: translateY(20px) scale(0.98);
        transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
        overflow: hidden;
    }
    #expenseModal.modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
</style>

<div class="modal-overlay" id="expenseModal" role="dialog" aria-modal="true" aria-labelledby="expenseModalTitle">
    <div class="modal-box" style="max-width:480px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:20px 22px 0;">
            <div>
                <div id="expenseModalTitle" style="font-family:'Outfit',sans-serif;font-weight:700;font-size:19px;color:var(--text-primary);">Record expense</div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">Log a cost against one of your properties</div>
            </div>
            <button type="button" onclick="closeExpenseSheet()" style="flex:none;width:32px;height:32px;border-radius:8px;border:1px solid var(--input-border);background:var(--card-bg);color:var(--text-secondary);font-size:13px;cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('expenses.store') }}" style="padding:18px 22px;overflow-y:auto;">
            @csrf
            <input type="hidden" name="expense_date" value="{{ now()->toDateString() }}">

            <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.6px;color:var(--text-muted);margin-bottom:8px;">PROPERTY</label>
            <select name="building_id" required style="width:100%;box-sizing:border-box;border:1.5px solid var(--input-border);border-radius:8px;padding:11px 12px;font-size:13.5px;color:var(--text-primary);outline:none;margin-bottom:18px;background:var(--input-bg);">
                @unless(isset($presetBuildingId))
                    <option value="" disabled selected>Select a property&hellip;</option>
                @endunless
                @foreach($expenseBuildings as $b)
                    <option value="{{ $b->id }}" {{ (isset($presetBuildingId) && (int) $presetBuildingId === $b->id) ? 'selected' : '' }}>{{ $b->property_name }}</option>
                @endforeach
            </select>

            <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.6px;color:var(--text-muted);margin-bottom:8px;">CATEGORY</label>
            <div style="display:flex;flex-wrap:wrap;gap:7px;margin-bottom:18px;">
                @foreach($expenseCategories as $key => $label)
                    <label style="display:flex;align-items:center;gap:7px;padding:9px 12px;border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;border:1px solid var(--input-border);color:var(--text-secondary);">
                        <input type="radio" name="category" value="{{ $key }}" required style="accent-color:var(--accent);margin:0;" {{ $loop->first ? 'checked' : '' }}>
                        <i class="{{ $expenseCategoryIcons[$key] ?? 'fa-solid fa-receipt' }}"></i> {{ $label }}
                    </label>
                @endforeach
            </div>

            <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.6px;color:var(--text-muted);margin-bottom:8px;">AMOUNT (BHD)</label>
            <div style="display:flex;align-items:center;gap:10px;background:var(--input-bg);border:1.5px solid var(--input-border);border-radius:8px;padding:12px 14px;margin-bottom:18px;">
                <span style="font-family:'Outfit',sans-serif;font-weight:700;font-size:15px;color:var(--text-muted);">BHD</span>
                <input type="number" step="0.001" min="0.001" name="amount" placeholder="0.000" required
                       style="flex:1;border:0;outline:none;background:transparent;font-family:'Outfit',sans-serif;font-weight:700;font-size:20px;color:var(--text-primary);">
            </div>

            <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.6px;color:var(--text-muted);margin-bottom:8px;">NOTE</label>
            <input type="text" name="description" maxlength="500" placeholder="e.g. AC servicing, unit 302"
                   style="width:100%;box-sizing:border-box;border:1.5px solid var(--input-border);border-radius:8px;padding:12px 14px;font-size:13.5px;color:var(--text-primary);outline:none;margin-bottom:20px;background:var(--input-bg);">

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Save expense</button>
        </form>
    </div>
</div>

<script>
function openExpenseSheet() {
    document.getElementById('expenseModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeExpenseSheet() {
    document.getElementById('expenseModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('expenseModal').addEventListener('click', function (e) {
    if (e.target === this) closeExpenseSheet();
});
</script>
