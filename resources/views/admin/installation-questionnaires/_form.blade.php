@csrf

@php
    $equipmentOptions = [
        'Under the sink Reverse Osmosis/Water Filter',
        'Counter Alkaline Water Machine/Water Purifier',
        'Water Softener',
    ];
    $selectedEquipment = old('existing_equipment', $questionnaire->existing_equipment ?? []);
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label for="first_name" class="block text-sm font-semibold text-slate-700">First Name</label>
        <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $questionnaire->first_name) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('first_name')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="last_name" class="block text-sm font-semibold text-slate-700">Last Name</label>
        <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $questionnaire->last_name) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('last_name')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $questionnaire->email) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('email')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-semibold text-slate-700">Phone</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $questionnaire->phone) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('phone')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label for="seller_id" class="block text-sm font-semibold text-slate-700">Seller</label>
        <select id="seller_id" name="seller_id" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">Select a seller</option>
            @foreach (($consultants ?? []) as $consultant)
                <option @selected((string) old('seller_id', $questionnaire->seller_id) === (string) $consultant->id) value="{{ $consultant->id }}">
                    {{ $consultant->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">The consultant or member who sold this installation.</p>
        @error('seller_id')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label for="street_address" class="block text-sm font-semibold text-slate-700">Street Address</label>
        <input id="street_address" name="street_address" type="text" value="{{ old('street_address', $questionnaire->street_address) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('street_address')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label for="street_address_2" class="block text-sm font-semibold text-slate-700">Street Address Line 2</label>
        <input id="street_address_2" name="street_address_2" type="text" value="{{ old('street_address_2', $questionnaire->street_address_2) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('street_address_2')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="city" class="block text-sm font-semibold text-slate-700">City</label>
        <input id="city" name="city" type="text" value="{{ old('city', $questionnaire->city) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('city')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="state" class="block text-sm font-semibold text-slate-700">State / Province</label>
        <input id="state" name="state" type="text" value="{{ old('state', $questionnaire->state) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('state')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="postal_code" class="block text-sm font-semibold text-slate-700">Postal / Zip Code</label>
        <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $questionnaire->postal_code) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('postal_code')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="country" class="block text-sm font-semibold text-slate-700">Country</label>
        <input id="country" name="country" type="text" value="{{ old('country', $questionnaire->country) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('country')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="property_type" class="block text-sm font-semibold text-slate-700">Property Type</label>
        <select id="property_type" name="property_type" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            @foreach (['Single Family Home', 'Condo', 'Townhouse', 'Apartment'] as $type)
                <option @selected(old('property_type', $questionnaire->property_type) === $type) value="{{ $type }}">{{ $type }}</option>
            @endforeach
        </select>
        @error('property_type')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="ownership" class="block text-sm font-semibold text-slate-700">Own or Rent</label>
        <select id="ownership" name="ownership" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">Not provided</option>
            <option @selected(old('ownership', $questionnaire->ownership) === 'own') value="own">Yes I own</option>
            <option @selected(old('ownership', $questionnaire->ownership) === 'rent') value="rent">Yes I rent</option>
        </select>
        @error('ownership')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <p class="block text-sm font-semibold text-slate-700">Existing Equipment</p>
        <div class="mt-2 grid gap-2">
            @foreach ($equipmentOptions as $option)
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input
                        @checked(in_array($option, $selectedEquipment, true))
                        class="rounded border-teal-200 text-teal-600 focus:ring-teal-500"
                        name="existing_equipment[]"
                        type="checkbox"
                        value="{{ $option }}"
                    >
                    <span>{{ $option }}</span>
                </label>
            @endforeach
        </div>
        @error('existing_equipment')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="water_source" class="block text-sm font-semibold text-slate-700">Water Source</label>
        <select id="water_source" name="water_source" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            @foreach (['Municipal (connected to the city)', 'Well', 'Rainwater', 'None', 'Other'] as $source)
                <option @selected(old('water_source', $questionnaire->water_source) === $source) value="{{ $source }}">{{ $source }}</option>
            @endforeach
        </select>
        @error('water_source')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="water_source_other" class="block text-sm font-semibold text-slate-700">Other Water Source</label>
        <input id="water_source_other" name="water_source_other" type="text" value="{{ old('water_source_other', $questionnaire->water_source_other) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('water_source_other')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label for="special_requirements" class="block text-sm font-semibold text-slate-700">Special Requirements</label>
        <textarea id="special_requirements" name="special_requirements" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('special_requirements', $questionnaire->special_requirements) }}</textarea>
        @error('special_requirements')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label for="additional_notes" class="block text-sm font-semibold text-slate-700">Additional Notes</label>
        <textarea id="additional_notes" name="additional_notes" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('additional_notes', $questionnaire->additional_notes) }}</textarea>
        @error('additional_notes')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('admin.installation-questionnaires.show', $questionnaire) }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
        {{ $submitLabel }}
    </button>
</div>
