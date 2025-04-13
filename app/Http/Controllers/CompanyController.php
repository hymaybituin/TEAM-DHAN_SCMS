<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return Company::with(['creator', 'updater'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'contact_info' => 'required',
            'website_url' => 'required|url',
            'industry' => 'required',
            'address' => 'required',
            'city' => 'required',
            'country' => 'required',
            'zip_code' => 'required',
            'phone_number' => 'required',
            'email_address' => 'required|email',
            'primary_contact_name' => 'required',
            'primary_contact_phone' => 'required',
            'primary_contact_email' => 'required|email',
            'additional_info' => 'required',
            'created_by' => 'required|exists:users,id',
            'updated_by' => 'required|exists:users,id',
        ]);

        return Company::create($request->all());
    }

    public function show($id)
    {
        return Company::with(['creator', 'updater'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required',
            'contact_info' => 'sometimes|required',
            'website_url' => 'sometimes|required|url',
            'industry' => 'sometimes|required',
            'address' => 'sometimes|required',
            'city' => 'sometimes|required',
            'country' => 'sometimes|required',
            'zip_code' => 'sometimes|required',
            'phone_number' => 'sometimes|required',
            'email_address' => 'sometimes|required|email',
            'primary_contact_name' => 'sometimes|required',
            'primary_contact_phone' => 'sometimes|required',
            'primary_contact_email' => 'sometimes|required|email',
            'additional_info' => 'sometimes|required',
            'created_by' => 'sometimes|required|exists:users,id',
            'updated_by' => 'sometimes|required|exists:users,id',
        ]);

        $company->update($request->all());

        return $company;
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->noContent();
    }
}
