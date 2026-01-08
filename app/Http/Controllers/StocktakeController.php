<?php

namespace App\Http\Controllers;

use App\Stocktake;
use App\StocktakeItem;
use App\Transaction;
use App\BusinessLocation;
use App\Product;
use App\ProductVariation;
use App\PurchaseLine;
use App\Variation;
use App\StockHistory;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class StocktakeController extends Controller
{
    protected $productUtil;
    protected $transactionUtil;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    // ==================== MAIN CRUD METHODS ====================

    public function index(Request $request)
    {
        $this->authorize('stocktake.view');

        $businessId = auth()->user()->business_id;
        
        $query = Stocktake::with([
                'location', 
                'createdBy',
                'items' => function($q) {
                    $q->with([
                        'product:id,name,sku', 
                        'variation:id,name,sub_sku,sell_price_inc_tax,default_sell_price,default_purchase_price'
                    ]);
                }
            ])
            ->where('stocktakes.business_id', $businessId)
            ->select('stocktakes.*');

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('location_id', $permitted_locations);
        }

        if ($request->has('location_id') && !empty($request->location_id)) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->ajax()) {
            $priceBasis = $request->get('price_basis', 'selling');
            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">';
                    
                    if (auth()->user()->can('stocktake.view')) {
                        $html .= '<a href="'.route('stocktakes.show', $row->id).'" class="btn btn-xs btn-primary">
                            <i class="fa fa-eye"></i></a>';
                    }
                    
                    if ($row->status !== 'completed' && auth()->user()->can('stocktake.update')) {
                        $html .= '<a href="'.route('stocktakes.edit', $row->id).'" class="btn btn-xs btn-info">
                            <i class="fa fa-edit"></i></a>';
                    }
                    
                    if (auth()->user()->can('stocktake.complete') && $row->status === 'in_progress') {
                        $html .= '<button data-href="'.route('stocktakes.complete', $row->id).'" 
                            class="btn btn-xs btn-success complete-stocktake"
                            title="'.__('stocktake.complete').'">
                            <i class="fa fa-check"></i></button>';
                    }
                    
                    if (auth()->user()->can('stocktake.delete')) {
                        $html .= '<button data-href="'.route('stocktakes.destroy', $row->id).'" 
                            class="btn btn-xs btn-danger delete-stocktake"
                            data-id="'.$row->id.'"
                            data-status="'.$row->status.'"
                            title="'.__('messages.delete').'"
                            '.($row->status === 'completed' ? 'disabled' : '').'>
                            <i class="fa fa-trash"></i></button>';
                    }
                    
                    $html .= '</div>';
                    return $html;
                })
                ->editColumn('status', function($row) {
                    $statuses = [
                        'in_progress' => '<span class="badge badge-warning">' . __('stocktake.in_progress') . '</span>',
                        'completed' => '<span class="badge badge-success">' . __('stocktake.completed') . '</span>',
                        'cancelled' => '<span class="badge badge-danger">' . __('stocktake.cancelled') . '</span>'
                    ];
                    return $statuses[$row->status] ?? $row->status;
                })
                ->addColumn('product_count', function($row) {
                    return $row->items->count();
                })
                ->editColumn('reference_no', function($row) {
                    return '<a href="'.route('stocktakes.show', $row->id).'">'.$row->reference_no.'</a>';
                })
                ->editColumn('transaction_date', function($row) {
                    return !empty($row->started_at) ? \Carbon\Carbon::parse($row->started_at)->format('Y-m-d H:i') : '-';
                })
                ->editColumn('completed_at', function($row) {
                    return !empty($row->completed_at) ? \Carbon\Carbon::parse($row->completed_at)->format('Y-m-d H:i') : '-';
                })
                ->addColumn('adjustment_ref', function($row) {
                    if ($row->status === 'completed' && $row->adjustment_transaction_id) {
                        try {
                            $transaction = Transaction::find($row->adjustment_transaction_id);
                            if ($transaction) {
                                return '<a href="'.route('stock-adjustment.show', $transaction->id).'">'.$transaction->ref_no.'</a>';
                            }
                        } catch (\Exception $e) {
                            Log::error('Error loading adjustment transaction', [
                                'stocktake_id' => $row->id,
                                'transaction_id' => $row->adjustment_transaction_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    return '-';
                })
                // Add DataTable columns needed by the Blade template
                ->addColumn('show_url', function($row) {
                    return route('stocktakes.show', $row->id);
                })
                ->addColumn('edit_url', function($row) {
                    return route('stocktakes.edit', $row->id);
                })
                ->addColumn('delete_url', function($row) {
                    return route('stocktakes.destroy', $row->id);
                })
                ->addColumn('complete_url', function($row) {
                    return route('stocktakes.complete', $row->id);
                })
                ->addColumn('adjustment_url', function($row) {
                    if ($row->status === 'completed' && $row->adjustment_transaction_id) {
                        return route('stock-adjustment.show', $row->adjustment_transaction_id);
                    }
                    return '#';
                })
                ->addColumn('can_edit', function($row) {
                    return $row->status !== 'completed' && auth()->user()->can('stocktake.update');
                })
                ->addColumn('can_delete', function($row) {
                    return auth()->user()->can('stocktake.delete') && $row->status !== 'completed';
                })
                ->addColumn('can_complete', function($row) {
                    return $row->status === 'in_progress' && auth()->user()->can('stocktake.complete');
                })
                ->addColumn('variance_amount_raw', function($row) use ($priceBasis) {
                    try {
                        $total = 0.0;
                        foreach ($row->items as $item) {
                            $unit_price = (float) ($item->effective_price ?? 0.0);
                            $counted_qty = (float) ($item->counted_quantity ?? 0.0);
                            $total += $counted_qty * $unit_price;
                        }
                        return (float) $total;
                    } catch (\Exception $e) {
                        Log::warning('Failed to compute total value for stocktake index row', ['stocktake_id' => $row->id, 'error' => $e->getMessage()]);
                        return 0.0;
                    }
                })
                ->addColumn('variance_amount', function($row) use ($priceBasis) {
                    $raw = 0.0;
                    try {
                        $raw = 0.0;
                        foreach ($row->items as $item) {
                            $unit_price = (float) ($item->effective_price ?? 0.0);
                            $counted_qty = (float) ($item->counted_quantity ?? 0.0);
                            $raw += $counted_qty * $unit_price;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to compute formatted total value for stocktake index row', ['stocktake_id' => $row->id, 'error' => $e->getMessage()]);
                    }
                    return $this->productUtil->num_f($raw);
                })
                ->addColumn('progress_percentage', function($row) {
                    // Calculate progress percentage if needed
                    return $row->status === 'in_progress' ? rand(30, 80) : 100;
                })
                ->rawColumns(['action', 'status', 'reference_no', 'adjustment_ref'])
                ->make(true);
        }

        $businessLocations = BusinessLocation::forDropdown($businessId, false);
        return view('stocktake.index', compact('businessLocations'));
    }

    public function create()
    {
        $this->authorize('stocktake.create');

        $business_id = auth()->user()->business_id;
        $locations = BusinessLocation::forDropdown($business_id);
        $default_location = optional(auth()->user()->getDefaultLocation())->id;

        return view('stocktake.create', compact('locations', 'default_location'));
    }

    public function store(Request $request)
    {
        $this->authorize('stocktake.create');

        $validated = $request->validate([
            'location_id' => 'required|exists:business_locations,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'required|exists:variations,id',
            'items.*.product_variation_id' => 'required|exists:product_variations,id',
            'items.*.counted_quantity' => 'required|numeric|min:0',
            'items.*.lot_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
            'additional_notes' => 'nullable|string'
        ], [
            'items.required' => __('stocktake.at_least_one_product_required'),
            'items.*.counted_quantity.min' => __('stocktake.quantity_cannot_be_negative'),
        ]);

        // Additional validation to ensure counted quantity is positive
        foreach ($validated['items'] as $key => $item) {
            if ($item['counted_quantity'] < 0) {
                return back()->with('status', [
                    'success' => false,
                    'msg' => __('stocktake.quantity_cannot_be_negative_for_item', ['item' => $key + 1])
                ])->withInput();
            }
        }

        if (!auth()->user()->can_access_this_location($validated['location_id'])) {
            return back()->with('status', [
                'success' => false,
                'msg' => __('lang_v1.location_not_permitted')
            ]);
        }

        try {
            DB::beginTransaction();

            $business_id = auth()->user()->business_id;
            
            $stocktake = Stocktake::create([
                'business_id' => $business_id,
                'location_id' => $validated['location_id'],
                'reference_no' => $this->generateStocktakeReferenceNo($business_id),
                'status' => 'in_progress',
                'created_by' => auth()->user()->id,
                'additional_notes' => $validated['additional_notes'] ?? null,
                'started_at' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                // Get expected quantity using the improved method
                $expectedQty = $this->getStockByProductVariation(
                    $item['product_variation_id'],
                    $validated['location_id'],
                    $item['lot_number'] ?? null,
                    $item['expiry_date'] ?? null
                );

                StocktakeItem::create([
                    'stocktake_id' => $stocktake->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'],
                    'product_variation_id' => $item['product_variation_id'],
                    'lot_number' => $item['lot_number'] ?? null,
                    'expiry_date' => !empty($item['expiry_date']) 
                        ? $this->productUtil->uf_date($item['expiry_date']) 
                        : null,
                    'system_quantity' => $expectedQty,
                    'counted_quantity' => $item['counted_quantity'],
                    'variance' => $item['counted_quantity'] - $expectedQty,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('stocktakes.show', $stocktake->id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('stocktake.created_successfully')
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stocktake store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ])->withInput();
        }
    }

    public function show($id)
    {
        $this->authorize('stocktake.view');

        $stocktake = Stocktake::with([
            'items' => function($query) {
                $query->with([
                    'variation:id,name,product_id,sub_sku,sell_price_inc_tax',
                    'product:id,name,sku,image'
                ]);
            },
            'location',
            'createdBy',
            'completedBy'
        ])->findOrFail($id);

        // Load adjustment transaction separately
        $adjustmentTransaction = null;
        if ($stocktake->adjustment_transaction_id) {
            $adjustmentTransaction = Transaction::with(['stock_adjustment_lines'])
                ->find($stocktake->adjustment_transaction_id);
        }

        // Get stock history for this stocktake
        $stockHistory = [];
        if ($stocktake->status === 'completed') {
            $stockHistory = StockHistory::where('reference_no', $stocktake->reference_no)
                ->orWhere(function($query) use ($stocktake) {
                    if ($stocktake->adjustment_transaction_id) {
                        $query->where('transaction_id', $stocktake->adjustment_transaction_id);
                    }
                })
                ->with(['product', 'variation'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $lot_enabled = session('business.enable_lot_number');
        $expiry_enabled = session('business.enable_product_expiry');

        // Calculate summary counts for completed stocktakes
        $exactCount = 0;
        $overageCount = 0;
        $shortageCount = 0;
        $totalValueVariance = 0;
        $totalValue = 0;

        if ($stocktake->status === 'completed') {
            foreach ($stocktake->items as $item) {
                if ($item->variance == 0) {
                    $exactCount++;
                } elseif ($item->variance > 0) {
                    $overageCount++;
                } else {
                    $shortageCount++;
                }

                $unit_price = optional($item->variation)->sell_price_inc_tax ?? 0;
                $totalValueVariance += $item->variance * $unit_price;
                $totalValue += ($item->counted_quantity ?? 0) * $unit_price;
            }
        }

        return view('stocktake.show', compact(
            'stocktake',
            'adjustmentTransaction',
            'stockHistory',
            'lot_enabled',
            'expiry_enabled',
            'exactCount',
            'overageCount',
            'shortageCount',
            'totalValueVariance',
            'totalValue'
        ));
    }

    public function edit($id)
    {
        $this->authorize('stocktake.update');

        $stocktake = Stocktake::with([
            'items' => function($query) {
                $query->with([
                    'variation:id,name,product_id,sub_sku,default_sell_price',
                    'product:id,name,sku'
                ]);
            },
            'location'
        ])->findOrFail($id);

        if ($stocktake->status === 'completed') {
            abort(403, __('stocktake.cannot_edit_completed'));
        }

        $locations = BusinessLocation::forDropdown(auth()->user()->business_id);

        return view('stocktake.edit', compact('stocktake', 'locations'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize('stocktake.update');

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:stocktake_items,id',
            'items.*.counted_quantity' => 'required|numeric|min:0',
            'additional_notes' => 'nullable|string'
        ]);

        // Additional validation to ensure counted quantity is positive
        foreach ($validated['items'] as $key => $itemData) {
            if ($itemData['counted_quantity'] < 0) {
                return back()->with('status', [
                    'success' => false,
                    'msg' => __('stocktake.quantity_cannot_be_negative_for_item', ['item' => $key + 1])
                ])->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $stocktake = Stocktake::findOrFail($id);

            if ($stocktake->status === 'completed') {
                throw new \Exception(__('stocktake.cannot_update_completed'));
            }

            foreach ($validated['items'] as $itemData) {
                $item = StocktakeItem::findOrFail($itemData['id']);
                
                $item->update([
                    'counted_quantity' => $itemData['counted_quantity'],
                    'variance' => $itemData['counted_quantity'] - $item->system_quantity,
                ]);
            }

            $stocktake->update([
                'additional_notes' => $validated['additional_notes'] ?? $stocktake->additional_notes,
            ]);

            DB::commit();

            return redirect()
                ->route('stocktakes.show', $id)
                ->with('status', [
                    'success' => true, 
                    'msg' => __('stocktake.updated_successfully')
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stocktake update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stocktake_id' => $id
            ]);
            
            return back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ])->withInput();
        }
    }

    public function destroy($id)
    {
        $this->authorize('stocktake.delete');

        try {
            DB::beginTransaction();

            $stocktake = Stocktake::with(['items'])->findOrFail($id);
            
            if ($stocktake->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'msg' => __('stocktake.cannot_delete_completed')
                ], 403);
            }

            StocktakeItem::where('stocktake_id', $id)->delete();
            $stocktake->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('stocktake.deleted_success')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stocktake deletion failed', [
                'error' => $e->getMessage(),
                'stocktake_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    // ==================== STOCKTAKE ACTIONS ====================

    public function complete($id)
    {
        $this->authorize('stocktake.complete');

        try {
            DB::beginTransaction();

            $stocktake = Stocktake::with(['items', 'location'])->findOrFail($id);

            if ($stocktake->status === 'completed') {
                throw new \Exception(__('stocktake.already_completed'));
            }

            // Validate data before processing
            $validationErrors = $this->productUtil->validateStocktakeData($stocktake->items, $stocktake->location_id);
            if (!empty($validationErrors)) {
                throw new \Exception(implode(', ', $validationErrors));
            }

            Log::info('Starting stocktake completion with proper adjustments', [
                'stocktake_id' => $stocktake->id,
                'item_count' => $stocktake->items->count(),
                'location_id' => $stocktake->location_id
            ]);

            // Create a stock adjustment transaction
            $transaction_data = [
                'type' => 'stock_adjustment',
                'business_id' => $stocktake->business_id,
                'location_id' => $stocktake->location_id,
                'transaction_date' => now(),
                'status' => 'received',
                'payment_status' => 'paid',
                'adjustment_type' => 'stocktake',
                'final_total' => 0,
                'ref_no' => $this->generateStockAdjustmentRef($stocktake->business_id),
                'created_by' => auth()->id(),
                'additional_notes' => 'Stocktake adjustment - ' . $stocktake->reference_no,
            ];

            $transaction = Transaction::create($transaction_data);

            $total_amount = 0;
            $processed_items = 0;
            $failed_items = 0;

            foreach ($stocktake->items as $item) {
                try {
                    // Get current system quantity before adjustment
                    $current_system_qty = $this->getStockByProductVariation(
                        $item->product_variation_id,
                        $stocktake->location_id,
                        $item->lot_number,
                        $item->expiry_date
                    );

                    $counted_qty = $item->counted_quantity;
                    
                    // Ensure counted quantity is positive
                    if ($counted_qty < 0) {
                        Log::warning('Negative counted quantity detected, setting to 0', [
                            'product_id' => $item->product_id,
                            'counted_qty' => $counted_qty
                        ]);
                        $counted_qty = 0;
                        $item->update(['counted_quantity' => 0]);
                    }

                    $adjustment_amount = $counted_qty - $current_system_qty;

                    Log::info('Processing stocktake adjustment', [
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                        'current_system_qty' => $current_system_qty,
                        'counted_qty' => $counted_qty,
                        'adjustment_amount' => $adjustment_amount,
                        'adjustment_type' => $adjustment_amount > 0 ? 'INCREASE' : ($adjustment_amount < 0 ? 'DECREASE' : 'NO_CHANGE')
                    ]);

                    // Only process if there's a difference
                    if ($adjustment_amount != 0) {
                        // Record stock history FIRST
                        $history_success = $this->productUtil->addStockHistory(
                            $item->product_id,
                            $item->variation_id,
                            $stocktake->location_id,
                            $counted_qty,
                            'stocktake',
                            $transaction->id,
                            $item->lot_number,
                            $item->expiry_date,
                            $stocktake->reference_no,
                            $current_system_qty
                        );

                        if (!$history_success) {
                            throw new \Exception('Failed to record stock history');
                        }

                        // Update stock using the dedicated stocktake method
                        $update_success = $this->productUtil->updateProductQuantityForStocktake(
                            $stocktake->location_id,
                            $item->product_id,
                            $item->variation_id,
                            $counted_qty,
                            $item->lot_number,
                            $item->expiry_date
                        );

                        if (!$update_success) {
                            throw new \Exception('Failed to update product quantity');
                        }

                        $processed_items++;
                        
                        // Calculate adjustment value for transaction
                        $variation = Variation::find($item->variation_id);
                        $unit_price = $variation->default_purchase_price ?? $variation->default_sell_price ?? 0;
                        $adjustment_value = abs($adjustment_amount) * $unit_price;
                        $total_amount += $adjustment_value;

                        // FIX: For stock adjustment lines, use the SIGNED adjustment amount
                        // This allows the system to properly track increases and decreases
                        // Positive = Increase, Negative = Decrease
                        DB::table('stock_adjustment_lines')->insert([
                            'transaction_id' => $transaction->id,
                            'product_id' => $item->product_id,
                            'variation_id' => $item->variation_id,
                            'quantity' => $adjustment_amount, // Use SIGNED value for proper tracking
                            'unit_price' => $unit_price,
                            'removed_purchase_line' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Log::info('Stocktake item processed successfully', [
                            'product_id' => $item->product_id,
                            'variation_id' => $item->variation_id,
                            'adjustment_amount' => $adjustment_amount,
                            'adjustment_value' => $adjustment_value,
                            'adjustment_type' => $adjustment_amount > 0 ? 'INCREASE' : 'DECREASE'
                        ]);
                    } else {
                        Log::info('No adjustment needed for stocktake item', [
                            'product_id' => $item->product_id,
                            'variation_id' => $item->variation_id
                        ]);
                        $processed_items++; // Count as processed since no action needed
                    }

                } catch (\Exception $e) {
                    $failed_items++;
                    Log::error('Error processing stocktake item', [
                        'error' => $e->getMessage(),
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Update transaction with final total
            $transaction->update([
                'final_total' => abs($total_amount),
                'total_before_tax' => abs($total_amount),
            ]);

            // Update stocktake with transaction reference
            $stocktake->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'adjustment_transaction_id' => $transaction->id,
            ]);

            DB::commit();

            Log::info('Stocktake completion process finished', [
                'stocktake_id' => $stocktake->id,
                'transaction_id' => $transaction->id,
                'processed_items' => $processed_items,
                'failed_items' => $failed_items,
                'total_adjustment_value' => $total_amount
            ]);

            if ($failed_items > 0) {
                return response()->json([
                    'success' => true,
                    'msg' => __('stocktake.completed_with_errors', ['processed' => $processed_items, 'failed' => $failed_items]),
                    'redirect' => route('stocktakes.show', $stocktake->id),
                ]);
            }

            return response()->json([
                'success' => true,
                'msg' => __('stocktake.completed_success'),
                'redirect' => route('stocktakes.show', $stocktake->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stocktake completion failed', [
                'error' => $e->getMessage(),
                'stocktake_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel($id)
    {
        $this->authorize('stocktake.update');

        try {
            DB::beginTransaction();

            $stocktake = Stocktake::findOrFail($id);

            if ($stocktake->status === 'completed') {
                throw new \Exception(__('stocktake.cannot_cancel_completed'));
            }

            $stocktake->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('stocktake.cancelled_success')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stocktake cancellation failed', [
                'error' => $e->getMessage(),
                'stocktake_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== PRODUCT MANAGEMENT ====================

    public function getProductsForStocktake(Request $request)
    {
        $this->authorize('stocktake.create');

        $request->validate([
            'location_id' => 'required|exists:business_locations,id',
        ]);

        if (!auth()->user()->can_access_this_location($request->location_id)) {
            return response()->json([
                'success' => false,
                'msg' => __('lang_v1.location_not_permitted')
            ], 403);
        }

        try {
            $products = $this->getProductsForSelection(
                $request->location_id,
                auth()->user()->business_id,
                $request->input('term')
            );

            return response()->json([
                'success' => true,
                'products' => $products,
                'enable_zero_stock_items' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Stocktake product search error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function searchProducts(Request $request)
    {
        $term = $request->input('term');
        $location_id = $request->input('location_id');

        if (strlen($term) < 2) {
            return response()->json(['products' => []]);
        }

        try {
            $products = $this->getProductsForSelection(
                $location_id,
                auth()->user()->business_id,
                $term
            );

            return response()->json([
                'success' => true,
                'products' => $products
            ]);

        } catch (\Exception $e) {
            Log::error('Product search error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'term' => $term,
                'location_id' => $location_id
            ]);
            
            return response()->json([
                'success' => false,
                'msg' => 'Search failed: ' . $e->getMessage(),
                'products' => []
            ], 500);
        }
    }

    // ==================== HISTORY & REPORTS ====================

    public function history(Request $request)
    {
        $this->authorize('stocktake.view');

        $businessId = auth()->user()->business_id;
        
        $filters = [
            'location_id' => $request->get('location_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $historyQuery = StockHistory::completedStocktakes()
            ->whereHas('product', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->with([
                'product:id,name,sku',
                'variation:id,name,sub_sku',
                'location:id,name',
                'createdByUser:id,username'
            ]);

        // Apply location filter
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $historyQuery->whereIn('stock_histories.location_id', $permitted_locations);
        }

        if (!empty($filters['location_id'])) {
            $historyQuery->where('stock_histories.location_id', $filters['location_id']);
        }

        if (!empty($filters['date_from'])) {
            $historyQuery->whereDate('stock_histories.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $historyQuery->whereDate('stock_histories.created_at', '<=', $filters['date_to']);
        }

        if ($request->ajax()) {
            return DataTables::of($historyQuery)
                ->editColumn('created_at', function($row) {
                    return $row->created_at->format('Y-m-d H:i');
                })
                ->addColumn('reference_no', function($row) {
                    return $row->reference_no;
                })
                ->addColumn('product_name_full', function($row) {
                    return $row->product->name . 
                           ($row->variation->name != 'DUMMY' ? ' - ' . $row->variation->name : '');
                })
                ->addColumn('sku', function($row) {
                    return $row->variation->sub_sku ?: $row->product->sku;
                })
                ->addColumn('location_name', function($row) {
                    return $row->location->name ?? '-';
                })
                ->editColumn('old_quantity', function($row) {
                    return $this->productUtil->num_f($row->old_quantity);
                })
                ->editColumn('new_quantity', function($row) {
                    return $this->productUtil->num_f($row->new_quantity);
                })
                ->editColumn('actual_adjustment', function($row) {
            // Compute variance as new_quantity - old_quantity and return numeric value.
            $oldQty = isset($row->old_quantity) ? (float) $row->old_quantity : 0.0;
            $newQty = isset($row->new_quantity) ? (float) $row->new_quantity : 0.0;
            $variance = $newQty - $oldQty;

            return (float) $variance;
        })
                ->addColumn('variance_percentage', function($row) {
                    // Use model accessor which now returns float or null
                    return $row->variance_percentage;
                })
                ->addColumn('adjusted_by', function($row) {
                    return $row->createdByUser->username ?? '-';
                })
                ->rawColumns([])
                ->make(true);
        }

        // Calculate summary for non-AJAX requests
        $summary = $this->calculateStocktakeSummary($filters, $businessId);
        $locations = $this->getFilterLocations();

        return view('stocktake.history', compact('summary', 'locations', 'filters'));
    }

    public function getHistoryData(Request $request)
{
    $this->authorize('stocktake.view');

    $businessId = auth()->user()->business_id;
    
    $query = StockHistory::forHistoryPage()
        ->whereHas('product', function($q) use ($businessId) {
            $q->where('business_id', $businessId);
        });

    // Apply location filter
    $permitted_locations = auth()->user()->permitted_locations();
    if ($permitted_locations != 'all') {
        $query->whereIn('stock_histories.location_id', $permitted_locations);
    }

    if ($request->filled('location_id')) {
        $query->where('stock_histories.location_id', $request->location_id);
    }

    if ($request->filled('date_from')) {
        $query->whereDate('stock_histories.created_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->whereDate('stock_histories.created_at', '<=', $request->date_to);
    }

    return DataTables::of($query)
        ->editColumn('created_at', function($row) {
            return $row->created_at->format('Y-m-d H:i');
        })
        ->addColumn('product_name_full', function($row) {
            return $row->product_name_full;
        })
        ->addColumn('sku', function($row) {
            return $row->sku;
        })
        ->addColumn('location_name', function($row) {
            return $row->location_name;
        })
        ->editColumn('old_quantity', function($row) {
            return $this->productUtil->num_f($row->old_quantity);
        })
        ->editColumn('new_quantity', function($row) {
            return $this->productUtil->num_f($row->new_quantity);
        })
        ->editColumn('actual_adjustment', function($row) {
            // Compute variance as new_quantity - old_quantity and return numeric
            $oldQty = isset($row->old_quantity) ? (float) $row->old_quantity : 0.0;
            $newQty = isset($row->new_quantity) ? (float) $row->new_quantity : 0.0;
            $variance = $newQty - $oldQty;

            return (float) $variance;
        })
        ->addColumn('variance_percentage', function($row) {
            // Return raw numeric value (float) or null so the client can format it.
            // Use strict null check because 0 is a valid percentage and should not be treated as missing.
            if (is_null($row->variance_percentage)) {
                return null;
            }

            return (float) $row->variance_percentage;
        })
        ->addColumn('adjusted_by', function($row) {
            return $row->adjusted_by;
        })
    ->addColumn('actions', function($row) {
        // Add action buttons if needed
        return '<a href="'.route('stocktakes.product_history', ['productId' => $row->product_id, 'variation_id' => $row->variation_id]).'" 
            class="btn btn-sm btn-outline-info" 
            data-bs-toggle="tooltip" 
            title="View Product History">
            <i class="fas fa-history"></i>
        </a>';
    })
    // Keep only HTML columns here (actions). actual_adjustment now numeric so do not mark raw.
    ->rawColumns(['actions'])
        ->make(true);
}

    public function varianceReport(Request $request)
    {
        $this->authorize('stocktake.view');

        $businessId = auth()->user()->business_id;
    $filters = $request->only(['location_id', 'date_from', 'date_to', 'price_basis']);

        // Get worst performing products
        $worstPerformers = StockHistory::getWorstPerformingProducts($businessId, 10, 30);
        // Compute monetary variance amount for worst performers based on selected price basis
        $priceBasis = $filters['price_basis'] ?? 'selling';
        foreach ($worstPerformers as $wpk => $wp) {
            try {
                $variation = null;
                if (!empty($wp->variation_id)) {
                    $variation = Variation::find($wp->variation_id);
                }

                $sellingPrice = 0.0;
                if (!empty($variation)) {
                    $sellingPrice = (float) ($variation->sell_price_inc_tax ?? $variation->default_sell_price ?? 0.0);
                } else {
                    // try to get product-level default price
                    $product = Product::find($wp->product_id);
                    $sellingPrice = $product ? (float) ($product->default_sell_price ?? 0.0) : 0.0;
                }

                // last purchase price (best-effort)
                $purchasePrice = (float) DB::table('purchase_lines')
                    ->where('variation_id', $wp->variation_id)
                    ->orderBy('id', 'desc')
                    ->value('purchase_price_inc_tax');
                if (empty($purchasePrice)) {
                    $purchasePrice = (float) DB::table('purchase_lines')
                        ->where('product_id', $wp->product_id)
                        ->orderBy('id', 'desc')
                        ->value('purchase_price_inc_tax');
                }

                $unitPrice = $priceBasis === 'purchase' ? $purchasePrice : $sellingPrice;

                $totalVarianceQty = isset($wp->total_variance) ? (float) $wp->total_variance : 0.0;
                $varianceAmount = $totalVarianceQty * $unitPrice;

                $worstPerformers[$wpk]->variance_amount_raw = (float) $varianceAmount;
                $worstPerformers[$wpk]->variance_amount = $this->productUtil->num_f($varianceAmount);
            } catch (\Exception $e) {
                Log::warning('Failed to compute variance amount for worst performer', ['product_id' => $wp->product_id, 'error' => $e->getMessage()]);
                $worstPerformers[$wpk]->variance_amount_raw = 0.0;
                $worstPerformers[$wpk]->variance_amount = $this->productUtil->num_f(0);
            }
        }
        
        // Get location performance
        $locationPerformance = StockHistory::getLocationStocktakePerformance(
            $filters['location_id'] ?? null, 
            30
        );
        
        // Get recent stocktake timeline
        $timeline = StockHistory::getStocktakeTimeline($businessId, 5);

        // Add monetary variance (value amount) per stocktake in timeline based on selected price basis
        $priceBasisForTimeline = $filters['price_basis'] ?? 'selling';
        foreach ($timeline as $tkey => $stocktakeItem) {
            try {
                $ref = $stocktakeItem->reference_no;
                if (empty($ref)) {
                    $timeline[$tkey]->variance_amount_raw = 0.0;
                    $timeline[$tkey]->variance_amount = $this->productUtil->num_f(0);
                    continue;
                }

                $rows = StockHistory::where('reference_no', $ref)
                    ->with(['variation'])
                    ->get();

                $totalAmount = 0.0;
                foreach ($rows as $r) {
                    $oldQty = isset($r->old_quantity) ? (float) $r->old_quantity : 0.0;
                    $newQty = isset($r->new_quantity) ? (float) $r->new_quantity : 0.0;
                    $varianceRaw = $newQty - $oldQty;

                    // determine selling price from variation if present
                    $sellingPrice = 0.0;
                    if (!empty($r->variation)) {
                        $sellingPrice = (float) ($r->variation->sell_price_inc_tax ?? $r->variation->default_sell_price ?? 0.0);
                    }

                    // get latest purchase price for this variation or product (best-effort)
                    $purchasePrice = (float) DB::table('purchase_lines')->where('variation_id', $r->variation_id)->orderBy('id', 'desc')->value('purchase_price_inc_tax');
                    if (empty($purchasePrice)) {
                        $purchasePrice = (float) DB::table('purchase_lines')->where('product_id', $r->product_id)->orderBy('id', 'desc')->value('purchase_price_inc_tax');
                    }

                    $unitPrice = $priceBasisForTimeline === 'purchase' ? $purchasePrice : $sellingPrice;
                    $totalAmount += $varianceRaw * $unitPrice;
                }

                $timeline[$tkey]->variance_amount_raw = (float) $totalAmount;
                $timeline[$tkey]->variance_amount = $this->productUtil->num_f($totalAmount);
            } catch (\Exception $e) {
                Log::warning('Failed to compute variance amount for timeline item', ['reference' => $stocktakeItem->reference_no, 'error' => $e->getMessage()]);
                $timeline[$tkey]->variance_amount_raw = 0.0;
                $timeline[$tkey]->variance_amount = $this->productUtil->num_f(0);
            }
        }
        
        // Calculate average variance rate
        $averageVarianceRate = $locationPerformance->avg('avg_variance_per_item') ?? 0;

        $locations = BusinessLocation::forDropdown($businessId, false);

        return view('stocktake.variance_report', compact(
            'worstPerformers', 
            'locationPerformance', 
            'timeline', 
            'locations', 
            'filters',
            'averageVarianceRate'
        ));
    }

    public function productHistory($productId, Request $request)
    {
        $this->authorize('stocktake.view');

        $variationId = $request->get('variation_id');
        $history = StockHistory::getProductVarianceHistory($productId, $variationId, 20);

        $product = Product::findOrFail($productId);
        $variation = $variationId ? Variation::find($variationId) : null;

        return view('stocktake.product_history', compact('history', 'product', 'variation'));
    }

    // ==================== EXPORT METHODS ====================

    public function exportVarianceReport(Request $request)
    {
        $this->authorize('stocktake.view');

        $businessId = auth()->user()->business_id;
    $filters = $request->only(['location_id', 'date_from', 'date_to', 'format', 'price_basis']);

        try {
            $format = $filters['format'] ?? 'excel';
            $fileName = 'variance-report-' . now()->format('Y-m-d-H-i-s');

            // Get variance data
            $varianceData = $this->getVarianceReportData($businessId, $filters);

            if ($format === 'csv') {
                return $this->exportVarianceReportAsCsv($varianceData, $fileName);
            } else {
                return $this->exportVarianceReportAsExcel($varianceData, $fileName);
            }

        } catch (\Exception $e) {
            Log::error('Error exporting variance report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage()
                ], 500);
            }

            return back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    public function exportVarianceReportData(Request $request)
    {
        return $this->exportVarianceReport($request);
    }

    public function quickExportVarianceReport(Request $request)
    {
        $this->authorize('stocktake.view');

        $filters = [
            'location_id' => $request->get('location_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'format' => $request->get('format', 'excel'),
            'price_basis' => $request->get('price_basis', 'selling')
        ];

        return $this->exportVarianceReport(new Request($filters));
    }

    public function exportHistory(Request $request)
    {
        $this->authorize('stocktake.view');

        $businessId = auth()->user()->business_id;
        $filters = $request->only(['location_id', 'date_from', 'date_to', 'format']);

        try {
            $data = StockHistory::getExportData($filters);
            $format = $filters['format'] ?? 'excel';
            $fileName = 'stocktake-history-' . now()->format('Y-m-d-H-i-s');

            if ($format === 'csv') {
                return $this->exportHistoryAsCsv($data, $fileName);
            } else {
                return $this->exportHistoryAsExcel($data, $fileName);
            }

        } catch (\Exception $e) {
            Log::error('Error exporting stocktake history', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong')
                ], 500);
            }

            return back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    // ==================== DASHBOARD & STATS ====================

    public function getLocationStats(Request $request)
    {
        $this->authorize('stocktake.view');

        $businessId = auth()->user()->business_id;
        
        try {
            $stats = [
                'total_stocktakes' => Stocktake::where('business_id', $businessId)->count(),
                'in_progress' => Stocktake::where('business_id', $businessId)->where('status', 'in_progress')->count(),
                'completed' => Stocktake::where('business_id', $businessId)->where('status', 'completed')->count(),
                'cancelled' => Stocktake::where('business_id', $businessId)->where('status', 'cancelled')->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting location stats', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'stats' => []]);
        }
    }

    public function getDashboardStats(Request $request)
    {
        $this->authorize('stocktake.view');

        $businessId = auth()->user()->business_id;
        $days = $request->get('days', 30);

        try {
            $stats = [
                'recent_stocktakes' => Stocktake::where('business_id', $businessId)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count(),
                
                'completed_stocktakes' => Stocktake::where('business_id', $businessId)
                    ->where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count(),
                
                'total_items_counted' => StocktakeItem::whereHas('stocktake', function($q) use ($businessId, $days) {
                    $q->where('business_id', $businessId)
                      ->where('created_at', '>=', now()->subDays($days));
                })->count(),
                
                'accuracy_rate' => $this->calculateRecentAccuracyRate($businessId, $days)
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting dashboard stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'stats' => []
            ]);
        }
    }

    public function getPerformanceMetrics(Request $request)
    {
        $this->authorize('stocktake.view');

        $businessId = auth()->user()->business_id;
        $filters = $request->only(['location_id', 'date_from', 'date_to']);

        try {
            $summary = $this->calculateStocktakeSummary($filters, $businessId);
            $accuracyRate = $this->calculateAccuracyRate($summary);
            
            $metrics = [
                'stocktake_count' => $summary->stocktake_count ?? 0,
                'total_items' => $summary->total_items ?? 0,
                'overage_count' => $summary->overage_count ?? 0,
                'shortage_count' => $summary->shortage_count ?? 0,
                'exact_count' => $summary->exact_count ?? 0,
                'total_variance_quantity' => $summary->total_variance_quantity ?? 0,
                'accuracy_rate' => round($accuracyRate, 2),
                'average_variance' => $summary->average_variance ?? 0
            ];

            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting performance metrics', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'Failed to load performance metrics'
            ], 500);
        }
    }

    // ==================== BULK ACTIONS ====================

    public function completeMultiple(Request $request)
    {
        $this->authorize('stocktake.complete');

        $validated = $request->validate([
            'stocktake_ids' => 'required|array',
            'stocktake_ids.*' => 'exists:stocktakes,id'
        ]);

        $results = [
            'successful' => [],
            'failed' => []
        ];

        foreach ($validated['stocktake_ids'] as $stocktake_id) {
            try {
                // Use the existing complete method logic
                $response = $this->complete($stocktake_id);
                
                if ($response->getData()->success) {
                    $results['successful'][] = $stocktake_id;
                } else {
                    $results['failed'][] = [
                        'id' => $stocktake_id,
                        'error' => $response->getData()->msg
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $stocktake_id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'msg' => __('Completed :successful out of :total stocktakes', [
                'successful' => count($results['successful']),
                'total' => count($validated['stocktake_ids'])
            ])
        ]);
    }

    // ==================== DEBUG & UTILITY METHODS ====================

    public function debugRoutesPermissions()
    {
        $this->authorize('stocktake.view');
        
        $user = auth()->user();
        $permissions = [
            'stocktake.view' => $user->can('stocktake.view'),
            'stocktake.create' => $user->can('stocktake.create'),
            'stocktake.update' => $user->can('stocktake.update'),
            'stocktake.delete' => $user->can('stocktake.delete'),
            'stocktake.complete' => $user->can('stocktake.complete'),
        ];
        
        $routes = [
            'stocktakes.history' => route('stocktakes.history'),
            'stocktakes.variance_report' => route('stocktakes.variance_report'),
            'stocktakes.quickExportVarianceReport' => route('stocktakes.quickExportVarianceReport'),
        ];
        
        return response()->json([
            'user' => $user->only(['id', 'username', 'email']),
            'permissions' => $permissions,
            'routes' => $routes,
            'permitted_locations' => $user->permitted_locations(),
        ]);
    }

    public function debugStockCalculation($stocktake_id)
    {
        $this->authorize('stocktake.view');

        $stocktake = Stocktake::with(['items', 'location'])->findOrFail($stocktake_id);
        
        $debug_info = [];
        
        foreach ($stocktake->items as $item) {
            $current_qty = $this->getStockByProductVariation(
                $item->product_variation_id,
                $stocktake->location_id,
                $item->lot_number,
                $item->expiry_date
            );

            // Get detailed breakdown for debugging
            $breakdown = $this->productUtil->getDetailedStockBreakdown($item->variation_id, $stocktake->location_id);
            
            $debug_info[] = [
                'product_id' => $item->product_id,
                'variation_id' => $item->variation_id,
                'product_variation_id' => $item->product_variation_id,
                'system_quantity_stored' => $item->system_quantity,
                'current_qty_available' => $current_qty,
                'counted_quantity' => $item->counted_quantity,
                'calculated_adjustment' => $item->counted_quantity - $current_qty,
                'variance_stored' => $item->variance,
                'lot_number' => $item->lot_number,
                'expiry_date' => $item->expiry_date,
                'stock_breakdown' => $breakdown
            ];
        }
        
        return response()->json([
            'success' => true,
            'debug_info' => $debug_info,
            'stocktake' => [
                'id' => $stocktake->id,
                'reference_no' => $stocktake->reference_no,
                'status' => $stocktake->status,
                'location_id' => $stocktake->location_id
            ]
        ]);
    }

    public function fixNegativeStockHistory()
    {
        $this->authorize('stocktake.update');
        
        try {
            $fixedCount = $this->productUtil->fixNegativeStockHistory();
            
            return response()->json([
                'success' => true,
                'msg' => __('Fixed :count negative stock history records', ['count' => $fixedCount]),
                'fixed_count' => $fixedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fix negative stock history', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'msg' => __('Failed to fix negative stock history: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    public function debugProductStock($product_id, $variation_id = null, $location_id = null)
    {
        $this->authorize('stocktake.view');

        try {
            $current_stock = $this->productUtil->getStockByVariation($variation_id, $location_id);
            
            // Get stock history summary
            $summary = $this->productUtil->getStockSummary($product_id, $variation_id, $location_id);
            
            // Get all stock history records
            $history = StockHistory::forProduct($product_id)
                ->forVariation($variation_id)
                ->forLocation($location_id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get detailed stock breakdown
            $breakdown = $variation_id ? $this->productUtil->getDetailedStockBreakdown($variation_id, $location_id) : null;

            return response()->json([
                'success' => true,
                'debug_info' => [
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'location_id' => $location_id,
                    'current_stock_calculated' => $current_stock,
                    'stock_summary' => $summary,
                    'stock_breakdown' => $breakdown,
                    'history_records' => $history->map(function($record) {
                        return [
                            'id' => $record->id,
                            'type' => $record->type,
                            'quantity' => $record->quantity,
                            'old_quantity' => $record->old_quantity,
                            'new_quantity' => $record->new_quantity,
                            'actual_adjustment' => $record->actual_adjustment,
                            'created_at' => $record->created_at,
                            'reference_no' => $record->reference_no
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in debugProductStock', [
                'error' => $e->getMessage(),
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStockSummary($product_id, $variation_id = null, $location_id = null)
    {
        $this->authorize('stocktake.view');

        try {
            $summary = $this->productUtil->getStockSummary($product_id, $variation_id, $location_id);
            
            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get stock summary', [
                'error' => $e->getMessage(),
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'location_id' => $location_id
            ]);
            
            return response()->json([
                'success' => false,
                'msg' => __('Failed to get stock summary: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Get products for stocktake selection - IMPROVED VERSION
     */
    private function getProductsForSelection($location_id, $business_id, $term = '')
    {
        try {
            $query = Product::join('variations', 'products.id', '=', 'variations.product_id')
                ->leftJoin('variation_location_details', function($join) use ($location_id) {
                    $join->on('variations.id', '=', 'variation_location_details.variation_id')
                         ->where('variation_location_details.location_id', $location_id);
                })
                ->where('products.business_id', $business_id)
                ->where('products.not_for_selling', 0)
                ->where('products.enable_stock', 1) // Only products with stock enabled
                ->select(
                    'products.id as product_id',
                    'products.name',
                    'products.sku',
                    'products.enable_stock',
                    'variations.id as variation_id',
                    'variations.name as variation_name',
                    'variations.sub_sku',
                    DB::raw('COALESCE(variation_location_details.qty_available, 0) as qty_available'),
                    'variations.default_sell_price',
                    'variations.product_variation_id'
                )
                ->groupBy('variations.id');

            if (!empty($term)) {
                $query->where(function($q) use ($term) {
                    $q->where('products.name', 'like', '%' . $term . '%')
                      ->orWhere('products.sku', 'like', '%' . $term . '%')
                      ->orWhere('variations.sub_sku', 'like', '%' . $term . '%')
                      ->orWhere('variations.name', 'like', '%' . $term . '%');
                });
            }

            // Add ordering for better user experience
            $query->orderBy('products.name')->orderBy('variations.name');

            $products = $query->get()->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'variation_id' => $item->variation_id,
                    'product_variation_id' => $item->product_variation_id,
                    'name' => $item->name . ($item->variation_name != 'DUMMY' ? ' - ' . $item->variation_name : ''),
                    'sku' => $item->sku,
                    'sub_sku' => $item->sub_sku,
                    'qty_available' => (float) $item->qty_available,
                    'formatted_qty_available' => $this->productUtil->num_f($item->qty_available),
                    'default_sell_price' => $item->default_sell_price,
                    'enable_stock' => $item->enable_stock
                ];
            })->toArray();

            return array_slice($products, 0, 50);

        } catch (\Exception $e) {
            Log::error('Error in getProductsForSelection', [
                'error' => $e->getMessage(),
                'location_id' => $location_id,
                'business_id' => $business_id,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get stock quantity by product_variation_id - IMPROVED VERSION
     */
    private function getStockByProductVariation($product_variation_id, $location_id, $lot_number = null, $expiry_date = null)
    {
        try {
            // Get variation_id from product_variation_id
            $variation = Variation::where('product_variation_id', $product_variation_id)->first();
            
            if (!$variation) {
                Log::warning('Variation not found for product_variation_id', [
                    'product_variation_id' => $product_variation_id
                ]);
                return 0;
            }

            return $this->productUtil->getStockByVariation(
                $variation->id,
                $location_id,
                $lot_number,
                $expiry_date
            );

        } catch (\Exception $e) {
            Log::error('Error in getStockByProductVariation', [
                'error' => $e->getMessage(),
                'product_variation_id' => $product_variation_id,
                'location_id' => $location_id,
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    /**
     * Generate stocktake reference no
     */
    private function generateStocktakeReferenceNo($businessId)
    {
        $prefix = 'ST-' . now()->format('Ymd') . '-';

        $lastRef = Stocktake::where('business_id', $businessId)
            ->where('reference_no', 'like', $prefix . '%')
            ->orderBy('reference_no', 'desc')
            ->value('reference_no');

        if ($lastRef) {
            $lastNumber = (int)substr($lastRef, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate stock adjustment reference number
     */
    private function generateStockAdjustmentRef($businessId)
    {
        $prefix = 'SA-' . now()->format('Ymd') . '-';

        $lastRef = Transaction::where('business_id', $businessId)
            ->where('type', 'stock_adjustment')
            ->where('ref_no', 'like', $prefix . '%')
            ->orderBy('ref_no', 'desc')
            ->value('ref_no');

        if ($lastRef) {
            $lastNumber = (int)substr($lastRef, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get locations for filter dropdown with permissions
     */
    private function getFilterLocations()
    {
        $businessId = auth()->user()->business_id;
        $permitted_locations = auth()->user()->permitted_locations();
        
        if ($permitted_locations == 'all') {
            return BusinessLocation::forDropdown($businessId, false);
        } else {
            return BusinessLocation::whereIn('id', $permitted_locations)
                ->pluck('name', 'id')
                ->toArray();
        }
    }

    /**
     * Calculate stocktake summary for the view
     */
    private function calculateStocktakeSummary($filters, $businessId)
    {
        $query = StockHistory::completedStocktakes()
            ->join('variations', 'stock_histories.variation_id', '=', 'variations.id')
            ->whereHas('product', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            });

        // Apply the same filters
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('stock_histories.location_id', $permitted_locations);
        }

        if (!empty($filters['location_id'])) {
            $query->where('stock_histories.location_id', $filters['location_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('stock_histories.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('stock_histories.created_at', '<=', $filters['date_to']);
        }

        return $query->selectRaw('
            COUNT(*) as total_items,
            SUM(ABS(stock_histories.actual_adjustment)) as total_variance_quantity,
            SUM(ABS(stock_histories.actual_adjustment) * COALESCE(variations.sell_price_inc_tax, variations.default_sell_price, 0)) as total_variance_amount,
            SUM(CASE WHEN stock_histories.actual_adjustment > 0 THEN 1 ELSE 0 END) as overage_count,
            SUM(CASE WHEN stock_histories.actual_adjustment < 0 THEN 1 ELSE 0 END) as shortage_count,
            SUM(CASE WHEN stock_histories.actual_adjustment = 0 THEN 1 ELSE 0 END) as exact_count,
            COUNT(DISTINCT stock_histories.reference_no) as stocktake_count
        ')->first();
    }

    /**
     * Calculate accuracy rate for summary
     */
    private function calculateAccuracyRate($summary)
    {
        if (!$summary || $summary->total_items == 0) {
            return 0;
        }

        $exact_count = $summary->exact_count ?? ($summary->total_items - $summary->overage_count - $summary->shortage_count);
        return ($exact_count / $summary->total_items) * 100;
    }

    /**
     * Calculate recent accuracy rate
     */
    private function calculateRecentAccuracyRate($businessId, $days)
    {
        $recentStocktakes = Stocktake::where('business_id', $businessId)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays($days))
            ->withCount(['items as exact_count' => function($query) {
                $query->where('variance', 0);
            }])
            ->withCount('items')
            ->get();

        if ($recentStocktakes->isEmpty()) {
            return 0;
        }

        $totalItems = $recentStocktakes->sum('items_count');
        $exactMatches = $recentStocktakes->sum('exact_count');

        return $totalItems > 0 ? ($exactMatches / $totalItems) * 100 : 0;
    }

    /**
     * Get variance report data
     */
    private function getVarianceReportData($businessId, $filters)
    {
        $query = StockHistory::completedStocktakes()
            ->whereHas('product', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->with([
                'product:id,name,sku',
                'variation:id,name,sub_sku',
                'location:id,name',
                'createdByUser:id,username'
            ]);

        // Apply location filter
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('stock_histories.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('stock_histories.created_at', '<=', $filters['date_to']);
        }

    $priceBasis = $filters['price_basis'] ?? 'selling';

    // Select the latest purchase price once per row via subqueries (variation-level, then product-level)
    $query->select('stock_histories.*', DB::raw("COALESCE((SELECT purchase_price_inc_tax FROM purchase_lines WHERE variation_id = stock_histories.variation_id ORDER BY id DESC LIMIT 1), (SELECT purchase_price_inc_tax FROM purchase_lines WHERE product_id = stock_histories.product_id ORDER BY id DESC LIMIT 1), 0) as last_purchased_price"));

    $data = $query->get()->map(function($row) use ($priceBasis) {
            // Use model accessor for variance percentage (returns float or null)
            $variancePercentage = $row->variance_percentage;

            // Compute variance as new - old to ensure consistency across UI and exports
            $oldQty = isset($row->old_quantity) ? (float) $row->old_quantity : 0.0;
            $newQty = isset($row->new_quantity) ? (float) $row->new_quantity : 0.0;
            $varianceRaw = $newQty - $oldQty;

            // Determine selling price (variation) and latest purchase price (from purchase_lines)
            $sellingPrice = 0;
            $variation = $row->variation;
            if (!empty($variation) && !empty($variation->sell_price_inc_tax)) {
                $sellingPrice = (float) $variation->sell_price_inc_tax;
            } elseif (!empty($variation) && !empty($variation->default_sell_price)) {
                $sellingPrice = (float) $variation->default_sell_price;
            }

            // Use the last_purchased_price selected via subquery to avoid N+1 queries
            $purchasePrice = isset($row->last_purchased_price) ? (float) $row->last_purchased_price : 0.0;

            $unitPrice = $priceBasis === 'purchase' ? $purchasePrice : $sellingPrice;

            // Compute variance amount using varianceRaw
            $varianceAmount = (float) $varianceRaw * $unitPrice;

            return [
                'date' => $row->created_at->format('Y-m-d H:i'),
                'product_name' => ($row->product->name ?? '') . 
                               (!empty($variation) && ($variation->name ?? '') != 'DUMMY' ? ' - ' . ($variation->name ?? '') : ''),
                'sku' => (!empty($variation) && !empty($variation->sub_sku)) ? $variation->sub_sku : ($row->product->sku ?? ''),
                'location' => $row->location->name,
                // Formatted fields for display
                'old_quantity' => $this->productUtil->num_f($row->old_quantity),
                'new_quantity' => $this->productUtil->num_f($row->new_quantity),
                'variance' => $this->productUtil->num_f($varianceRaw),
                // variance_percentage is returned raw (float) or null — formatting done in export/view
                'variance_percentage' => $variancePercentage,
                'adjusted_by' => $row->createdByUser->username ?? '',
                'reference_no' => $row->reference_no,
                // Pricing & amount info
                'selling_price_raw' => (float) $sellingPrice,
                'purchase_price_raw' => (float) $purchasePrice,
                'unit_price_raw' => (float) $unitPrice,
                'unit_price' => $this->productUtil->num_f($unitPrice),
                'variance_amount_raw' => (float) $varianceAmount,
                'variance_amount' => $this->productUtil->num_f($varianceAmount),
                // Raw numeric fields for totals and exports
                'old_quantity_raw' => (float) $row->old_quantity,
                'new_quantity_raw' => (float) $row->new_quantity,
                'variance_raw' => (float) $varianceRaw,
            ];
        });

        return $data;
    }

    /**
     * Export variance report as CSV
     */
    private function exportVarianceReportAsCsv($data, $fileName)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.csv"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Add CSV headers
            fputcsv($file, [
                __('stocktake.date'),
                __('product.product_name'),
                __('product.sku'),
                __('business.location'),
                __('stocktake.old_quantity'),
                __('stocktake.new_quantity'),
                __('stocktake.variance'),
                __('stocktake.variance_percentage'),
                __('stocktake.unit_price'),
                __('stocktake.variance_amount'),
                __('stocktake.adjusted_by'),
                __('stocktake.reference_no')
            ]);
            
            // Add data rows and compute totals
            $totals = [
                'old_quantity' => 0.0,
                'new_quantity' => 0.0,
                'variance' => 0.0,
                'variance_amount' => 0.0,
            ];

            foreach ($data as $row) {
                // accumulate raw numeric totals when available
                if (isset($row['old_quantity_raw'])) $totals['old_quantity'] += (float) $row['old_quantity_raw'];
                if (isset($row['new_quantity_raw'])) $totals['new_quantity'] += (float) $row['new_quantity_raw'];
                if (isset($row['variance_raw'])) $totals['variance'] += (float) $row['variance_raw'];
                if (isset($row['variance_amount_raw'])) $totals['variance_amount'] += (float) $row['variance_amount_raw'];

                fputcsv($file, [
                    $row['date'],
                    $row['product_name'],
                    $row['sku'],
                    $row['location'],
                    $row['old_quantity'],
                    $row['new_quantity'],
                    $row['variance'],
                    (is_null($row['variance_percentage']) ? 'N/A' : $this->productUtil->num_f($row['variance_percentage']) . '%'),
                    $row['unit_price'],
                    $row['variance_amount'],
                    $row['adjusted_by'],
                    $row['reference_no']
                ]);
            }

            // Append totals row
            fputcsv($file, [
                __('stocktake.totals') ?? 'Totals',
                '',
                '',
                '',
                $this->productUtil->num_f($totals['old_quantity']),
                $this->productUtil->num_f($totals['new_quantity']),
                $this->productUtil->num_f($totals['variance']),
                '',
                $this->productUtil->num_f($totals['variance_amount']),
                '',
                '',
                ''
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export variance report as Excel
     */
    private function exportVarianceReportAsExcel($data, $fileName)
    {
        // Check if Laravel Excel is available
        if (class_exists('Maatwebsite\Excel\Facades\Excel')) {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new class($data, $this->productUtil) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                    protected $data;
                    protected $productUtil;

                    public function __construct($data, $productUtil)
                    {
                        $this->data = $data;
                        $this->productUtil = $productUtil;
                    }

                    public function collection()
                    {
                        $rows = [];
                        $totals = [
                            'old_quantity' => 0.0,
                            'new_quantity' => 0.0,
                            'variance' => 0.0,
                            'variance_amount' => 0.0,
                        ];

                        foreach ($this->data as $row) {
                            if (isset($row['old_quantity_raw'])) $totals['old_quantity'] += (float) $row['old_quantity_raw'];
                            if (isset($row['new_quantity_raw'])) $totals['new_quantity'] += (float) $row['new_quantity_raw'];
                            if (isset($row['variance_raw'])) $totals['variance'] += (float) $row['variance_raw'];
                            if (isset($row['variance_amount_raw'])) $totals['variance_amount'] += (float) $row['variance_amount_raw'];

                            $rows[] = [
                                'date' => $row['date'],
                                'product_name' => $row['product_name'],
                                'sku' => $row['sku'],
                                'location' => $row['location'],
                                'old_quantity' => $row['old_quantity'],
                                'new_quantity' => $row['new_quantity'],
                                'variance' => $row['variance'],
                                'variance_percentage' => (is_null($row['variance_percentage']) ? 'N/A' : $this->productUtil->num_f($row['variance_percentage']) . '%'),
                                'unit_price' => $row['unit_price'],
                                'variance_amount' => $row['variance_amount'],
                                'adjusted_by' => $row['adjusted_by'],
                                'reference_no' => $row['reference_no']
                            ];
                        }

                        // Append totals row
                        $rows[] = [
                            'date' => __('stocktake.totals') ?? 'Totals',
                            'product_name' => '',
                            'sku' => '',
                            'location' => '',
                            'old_quantity' => $this->productUtil->num_f($totals['old_quantity']),
                            'new_quantity' => $this->productUtil->num_f($totals['new_quantity']),
                            'variance' => $this->productUtil->num_f($totals['variance']),
                            'variance_percentage' => '',
                            'unit_price' => '',
                            'variance_amount' => $this->productUtil->num_f($totals['variance_amount']),
                            'adjusted_by' => '',
                            'reference_no' => ''
                        ];

                        return collect($rows);
                    }

                    public function headings(): array
                    {
                        return [
                            __('stocktake.date'),
                            __('product.product_name'),
                            __('product.sku'),
                            __('business.location'),
                            __('stocktake.old_quantity'),
                            __('stocktake.new_quantity'),
                            __('stocktake.variance'),
                            __('stocktake.variance_percentage'),
                            __('stocktake.unit_price'),
                            __('stocktake.variance_amount'),
                            __('stocktake.adjusted_by'),
                            __('stocktake.reference_no')
                        ];
                    }
                },
                $fileName . '.xlsx'
            );
        } else {
            // Fallback to CSV if Excel is not available
            return $this->exportVarianceReportAsCsv($data, $fileName);
        }
    }

    /**
     * Export history as CSV
     */
    private function exportHistoryAsCsv($data, $fileName)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.csv"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Add CSV headers
            fputcsv($file, [
                __('stocktake.date'),
                __('stocktake.reference_no'),
                __('product.product_name'),
                __('product.sku'),
                __('business.location'),
                __('stocktake.old_quantity'),
                __('stocktake.new_quantity'),
                __('stocktake.variance'),
                __('stocktake.variance_percentage'),
                __('stocktake.adjusted_by')
            ]);
            
            // Add data rows
                foreach ($data as $row) {
                $oldQty = isset($row->old_quantity) ? (float) $row->old_quantity : 0.0;
                $newQty = isset($row->new_quantity) ? (float) $row->new_quantity : 0.0;
                $variance = $newQty - $oldQty;

                fputcsv($file, [
                    $row->created_at->format('Y-m-d H:i'),
                    $row->reference_no,
                    $row->product_name_full,
                    $row->sku,
                    $row->location_name,
                    $this->productUtil->num_f($row->old_quantity),
                    $this->productUtil->num_f($row->new_quantity),
                    $this->productUtil->num_f($variance),
                    is_null($row->variance_percentage) ? 'N/A' : $this->productUtil->num_f($row->variance_percentage) . '%',
                    $row->adjusted_by
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export history as Excel
     */
    private function exportHistoryAsExcel($data, $fileName)
    {
        // Check if Laravel Excel is available
        if (class_exists('Maatwebsite\Excel\Facades\Excel')) {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new class($data, $this->productUtil) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                    protected $data;
                    protected $productUtil;

                    public function __construct($data, $productUtil)
                    {
                        $this->data = $data;
                        $this->productUtil = $productUtil;
                    }

                    public function collection()
                    {
                        return $this->data->map(function($row) {
                            $oldQty = isset($row->old_quantity) ? (float) $row->old_quantity : 0.0;
                            $newQty = isset($row->new_quantity) ? (float) $row->new_quantity : 0.0;
                            $variance = $newQty - $oldQty;
                            return [
                                'date' => $row->created_at->format('Y-m-d H:i'),
                                'reference_no' => $row->reference_no,
                                'product_name' => $row->product_name_full,
                                'sku' => $row->sku,
                                'location' => $row->location_name,
                                'old_quantity' => $this->productUtil->num_f($row->old_quantity),
                                'new_quantity' => $this->productUtil->num_f($row->new_quantity),
                                'variance' => $this->productUtil->num_f($variance),
                                'variance_percentage' => is_null($row->variance_percentage) ? 'N/A' : $this->productUtil->num_f($row->variance_percentage) . '%',
                                'adjusted_by' => $row->adjusted_by
                            ];
                        });
                    }

                    public function headings(): array
                    {
                        return [
                            __('stocktake.date'),
                            __('stocktake.reference_no'),
                            __('product.product_name'),
                            __('product.sku'),
                            __('business.location'),
                            __('stocktake.old_quantity'),
                            __('stocktake.new_quantity'),
                            __('stocktake.variance'),
                            __('stocktake.variance_percentage'),
                            __('stocktake.adjusted_by')
                        ];
                    }
                },
                $fileName . '.xlsx'
            );
        } else {
            // Fallback to CSV if Excel is not available
            return $this->exportHistoryAsCsv($data, $fileName);
        }
    }

    /**
     * Enhanced stock calculation with better error handling
     */
    private function getEnhancedStockQuantity($variation_id, $location_id, $lot_number = null, $expiry_date = null)
    {
        try {
            $stock = $this->productUtil->getStockByVariation($variation_id, $location_id, $lot_number, $expiry_date);
            
            // Ensure stock is never negative
            return max(0, (float) $stock);
            
        } catch (\Exception $e) {
            Log::error('Enhanced stock calculation failed', [
                'variation_id' => $variation_id,
                'location_id' => $location_id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Validate stocktake access permissions for location
     */
    private function validateLocationAccess($location_id)
    {
        $permitted_locations = auth()->user()->permitted_locations();
        
        if ($permitted_locations != 'all' && !in_array($location_id, $permitted_locations)) {
            throw new \Exception(__('lang_v1.location_not_permitted'));
        }
        
        return true;
    }
}