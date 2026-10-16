<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? Carbon::createFromFormat('Y-m-d', $request->query('month').'-01') : now();
        $from = $month->copy()->startOfMonth()->toDateString();
        $to = $month->copy()->endOfMonth()->toDateString();

        $expenses = Expense::with('user')->whereBetween('spent_on', [$from, $to])->latest('spent_on')->latest('id')->get();
        $income = (int) Payment::whereBetween('paid_on', [$from, $to])->sum('amount');

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'month' => $month,
            'income' => $income,
            'spent' => (int) $expenses->sum('amount'),
            'byCategory' => $expenses->groupBy('category')->map->sum('amount')->sortDesc(),
            'expense' => $request->integer('edit') ? Expense::find($request->integer('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $expense = Expense::create($this->validated($request) + ['user_id' => $request->user()->id]);

        return redirect()->route('admin.expenses.index', ['month' => $expense->spent_on->format('Y-m')])->with('ok', 'খরচ যোগ হয়েছে।');
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));

        return redirect()->route('admin.expenses.index', ['month' => $expense->spent_on->format('Y-m')])->with('ok', 'খরচ হালনাগাদ হয়েছে।');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with('ok', 'খরচ মুছে ফেলা হয়েছে।');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'spent_on' => ['required', 'date'],
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['nullable', Rule::in(Payment::METHODS)],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['spent_on' => 'তারিখ', 'category' => 'খাত', 'amount' => 'পরিমাণ', 'note' => 'বিবরণ']);
    }
}
