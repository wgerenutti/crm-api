<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')
                ->orWhere('company', 'like', '%' . $request->search . '%');
        }

        $leads = $query->orderBy('created_at', 'desc')->get();

        $summary = [];
        foreach (Lead::STATUSES as $status) {
            $summary[$status] = Lead::where('status', $status)->count();
        }

        return response()->json([
            'data' => $leads,
            'total' => $leads->count(),
            'summary' => $summary,
        ]);
    }

    public function show($id)
    {
        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json(['message' => 'Lead não encontrado'], 404);
        }

        return new LeadResource($lead);
    }

    public function store(StoreLeadRequest $request)
    {
        $lead = Lead::create($request->all());

        return response()->json($lead, 201);
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json(['error' => 'Lead not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:leads,email',
            'phone' => 'nullable|string',
            'company' => 'nullable|string',
            'source' => 'sometimes|string',
            'status' => 'sometimes|in:' . implode(',', Lead::STATUSES),
            'notes' => 'nullable|string',
        ]);

        // Leads ganhos ou perdidos não podem mais mudar de status
        if ($lead->status != $request->status && in_array($lead->status, ['WON', 'LOST'])) {
            return response()->json(['message' => 'Não é possível alterar o status de um lead finalizado'], 400);
        }

        $lead->update($request->all());

        return response()->json($lead);
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();

        return response()->json(['message' => 'Lead removido com sucesso']);
    }
}
