<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Add Bike</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea,
        select {
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="file"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    @include('partials.sidebar')

    <!-- Main Content Wrapper -->
    <div class="lg:pl-[280px] min-h-screen w-full">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8 lg:py-10">

            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl md:text-4xl font-black text-gray-900">Add New Bike</h1>
                <p class="text-gray-500 mt-2 text-sm md:text-base">Enter the details of your new bike below.</p>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4 flex items-start gap-2">
                <span class="material-symbols-outlined text-red-600 text-xl flex-shrink-0">error</span>
                <div>
                    <p class="font-bold">Please fix the following errors:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Add Bike Form -->
            <form action="{{ route('owner.bikes.store') }}" method="POST" enctype="multipart/form-data"
                  class="bg-white border border-gray-200 rounded-xl p-6 md:p-8 shadow-sm">
                @csrf

                <!-- Form Sections -->
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600">info</span>
                            Basic Information
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Bike Name <span class="text-red-500">*</span></label>
                                <input name="bike_name" value="{{ old('bike_name') }}" required
                                       class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                       placeholder="e.g., Honda CG 125" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Brand <span class="text-red-500">*</span></label>
                                    <select name="brand" id="brand" required
                                            class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5 bg-white">
                                        <option value="">Select Brand</option>
                                        <option value="Honda" @selected(old('brand') === 'Honda')>Honda</option>
                                        <option value="Yamaha" @selected(old('brand') === 'Yamaha')>Yamaha</option>
                                        <option value="Suzuki" @selected(old('brand') === 'Suzuki')>Suzuki</option>
                                        <option value="Kawasaki" @selected(old('brand') === 'Kawasaki')>Kawasaki</option>
                                        <option value="United" @selected(old('brand') === 'United')>United</option>
                                        <option value="Road Prince" @selected(old('brand') === 'Road Prince')>Road Prince</option>
                                        <option value="Super Power" @selected(old('brand') === 'Super Power')>Super Power</option>
                                        <option value="Unique" @selected(old('brand') === 'Unique')>Unique</option>
                                        <option value="Other" @selected(old('brand') === 'Other')>Other</option>
                                    </select>
                                    <input type="text" name="brand_other" id="brand_other" value="{{ old('brand_other') }}"
                                           placeholder="Please specify brand"
                                           class="hidden w-full mt-2 border border-gray-300 rounded-lg px-4 py-2.5" />
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Model <span class="text-red-500">*</span></label>
                                    <input name="model" value="{{ old('model') }}" required
                                           class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5"
                                           placeholder="e.g., CG 125" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bike Specifications -->
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600">build</span>
                            Specifications
                        </h2>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Type <span class="text-red-500">*</span></label>
                                    <select name="bike_type" required
                                            class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5 bg-white">
                                        <option value="">Select Type</option>
                                        <option value="Standard" @selected(old('bike_type') === 'Standard')>Standard</option>
                                        <option value="Sport" @selected(old('bike_type') === 'Sport')>Sport</option>
                                        <option value="Cruiser" @selected(old('bike_type') === 'Cruiser')>Cruiser</option>
                                        <option value="Scooter" @selected(old('bike_type') === 'Scooter')>Scooter</option>
                                        <option value="Dirt Bike" @selected(old('bike_type') === 'Dirt Bike')>Dirt Bike</option>
                                        <option value="Touring" @selected(old('bike_type') === 'Touring')>Touring</option>
                                        <option value="Electric" @selected(old('bike_type') === 'Electric')>Electric</option>
                                        <option value="Moped" @selected(old('bike_type') === 'Moped')>Moped</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Registration Number <span class="text-red-500">*</span></label>
                                    <input name="registration_number" value="{{ old('registration_number') }}" required
                                           class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5"
                                           placeholder="e.g., ABC-1234" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Color <span class="text-red-500">*</span></label>
                                    <select name="color" id="color" required
                                            class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5 bg-white">
                                        <option value="">Select Color</option>
                                        <option value="Black" @selected(old('color') === 'Black')>Black</option>
                                        <option value="White" @selected(old('color') === 'White')>White</option>
                                        <option value="Red" @selected(old('color') === 'Red')>Red</option>
                                        <option value="Blue" @selected(old('color') === 'Blue')>Blue</option>
                                        <option value="Green" @selected(old('color') === 'Green')>Green</option>
                                        <option value="Silver" @selected(old('color') === 'Silver')>Silver</option>
                                        <option value="Grey" @selected(old('color') === 'Grey')>Grey</option>
                                        <option value="Yellow" @selected(old('color') === 'Yellow')>Yellow</option>
                                        <option value="Orange" @selected(old('color') === 'Orange')>Orange</option>
                                        <option value="Brown" @selected(old('color') === 'Brown')>Brown</option>
                                        <option value="Other" @selected(old('color') === 'Other')>Other</option>
                                    </select>
                                    <input type="text" name="color_other" id="color_other" value="{{ old('color_other') }}"
                                           placeholder="Please specify color"
                                           class="hidden w-full mt-2 border border-gray-300 rounded-lg px-4 py-2.5" />
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">City <span class="text-red-500">*</span></label>
                                    <select name="city" id="city" required
                                            class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5 bg-white">
                                        <option value="">Select City</option>
                                        <option value="Karachi" @selected(old('city') === 'Karachi')>Karachi</option>
                                        <option value="Lahore" @selected(old('city') === 'Lahore')>Lahore</option>
                                        <option value="Islamabad" @selected(old('city') === 'Islamabad')>Islamabad</option>
                                        <option value="Rawalpindi" @selected(old('city') === 'Rawalpindi')>Rawalpindi</option>
                                        <option value="Faisalabad" @selected(old('city') === 'Faisalabad')>Faisalabad</option>
                                        <option value="Multan" @selected(old('city') === 'Multan')>Multan</option>
                                        <option value="Peshawar" @selected(old('city') === 'Peshawar')>Peshawar</option>
                                        <option value="Quetta" @selected(old('city') === 'Quetta')>Quetta</option>
                                        <option value="Hyderabad" @selected(old('city') === 'Hyderabad')>Hyderabad</option>
                                        <option value="Sialkot" @selected(old('city') === 'Sialkot')>Sialkot</option>
                                        <option value="Gujranwala" @selected(old('city') === 'Gujranwala')>Gujranwala</option>
                                        <option value="Sargodha" @selected(old('city') === 'Sargodha')>Sargodha</option>
                                        <option value="Bahawalpur" @selected(old('city') === 'Bahawalpur')>Bahawalpur</option>
                                        <option value="Abbottabad" @selected(old('city') === 'Abbottabad')>Abbottabad</option>
                                        <option value="Other" @selected(old('city') === 'Other')>Other</option>
                                    </select>
                                    <input type="text" name="city_other" id="city_other" value="{{ old('city_other') }}"
                                           placeholder="Please specify city"
                                           class="hidden w-full mt-2 border border-gray-300 rounded-lg px-4 py-2.5" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Engine CC <span class="text-red-500">*</span></label>
                                    <select name="engine_cc" required
                                            class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5 bg-white">
                                        <option value="">Select CC</option>
                                        <option value="70cc" @selected(old('engine_cc') === '70cc')>70cc</option>
                                        <option value="100cc" @selected(old('engine_cc') === '100cc')>100cc</option>
                                        <option value="125cc" @selected(old('engine_cc') === '125cc')>125cc</option>
                                        <option value="150cc" @selected(old('engine_cc') === '150cc')>150cc</option>
                                        <option value="200cc" @selected(old('engine_cc') === '200cc')>200cc</option>
                                        <option value="250cc" @selected(old('engine_cc') === '250cc')>250cc</option>
                                        <option value="300cc" @selected(old('engine_cc') === '300cc')>300cc</option>
                                        <option value="500cc" @selected(old('engine_cc') === '500cc')>500cc</option>
                                        <option value="650cc" @selected(old('engine_cc') === '650cc')>650cc</option>
                                        <option value="1000cc+" @selected(old('engine_cc') === '1000cc+')>1000cc+</option>
                                        <option value="Electric" @selected(old('engine_cc') === 'Electric')>Electric</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Fuel Type <span class="text-red-500">*</span></label>
                                    <select name="fuel_type" required
                                            class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5 bg-white">
                                        <option value="">Select Fuel</option>
                                        <option value="Petrol" @selected(old('fuel_type') === 'Petrol')>Petrol</option>
                                        <option value="Diesel" @selected(old('fuel_type') === 'Diesel')>Diesel</option>
                                        <option value="Electric" @selected(old('fuel_type') === 'Electric')>Electric</option>
                                        <option value="Hybrid" @selected(old('fuel_type') === 'Hybrid')>Hybrid</option>
                                        <option value="CNG" @selected(old('fuel_type') === 'CNG')>CNG</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Transmission <span class="text-red-500">*</span></label>
                                    <select name="transmission" required
                                            class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5 bg-white">
                                        <option value="">Select Transmission</option>
                                        <option value="Manual" @selected(old('transmission') === 'Manual')>Manual</option>
                                        <option value="Automatic" @selected(old('transmission') === 'Automatic')>Automatic</option>
                                        <option value="Semi-Automatic" @selected(old('transmission') === 'Semi-Automatic')>Semi-Automatic</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600">payments</span>
                            Pricing
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Price/Hour (Rs.) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="price_per_hour" value="{{ old('price_per_hour', 5) }}" required
                                       class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5"
                                       placeholder="0.00" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Price/Day (Rs.) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="price_per_day" value="{{ old('price_per_day', 18) }}" required
                                       class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5"
                                       placeholder="0.00" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Price/Month (Rs.)</label>
                                <input type="number" step="0.01" name="price_per_month" value="{{ old('price_per_month', 500) }}"
                                       class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5"
                                       placeholder="0.00 (auto-calculated if left blank)" />
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600">description</span>
                            Description
                        </h2>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Bike Description</label>
                            <textarea name="description" rows="4"
                                      class="w-full mt-1 border border-gray-300 rounded-lg px-4 py-2.5 resize-none"
                                      placeholder="Describe your bike's condition, features, and any special notes...">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600">image</span>
                            Bike Image <span class="text-red-500">*</span>
                        </h2>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Upload Photo <span class="text-red-500">*</span></label>

                            <!-- Image Preview -->
                            <div id="image-preview-container" class="hidden mb-4">
                                <div class="relative rounded-lg overflow-hidden border border-gray-200 shadow-sm">
                                    <img id="image-preview" src="#" alt="Bike image preview"
                                         class="w-full h-64 object-cover" />
                                    <button type="button" id="remove-image"
                                            class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white rounded-full p-2 shadow-md transition-colors"
                                            title="Remove image">
                                        <span class="material-symbols-outlined text-lg">close</span>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                    Image selected — ready to upload
                                </p>
                            </div>

                            <div id="upload-dropzone" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-emerald-500 transition-colors">
                                <div class="space-y-1 text-center">
                                    <span class="material-symbols-outlined text-4xl text-gray-400">cloud_upload</span>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-emerald-600 hover:text-emerald-500">
                                            <span>Upload a file</span>
                                            <input id="file-upload" name="image" type="file" accept="image/*" required class="sr-only" />
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-8">
                    <button type="submit"
                            class="w-full bg-emerald-700 hover:bg-emerald-800 text-white py-3.5 rounded-lg font-bold transition-colors duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-xl">add_circle</span>
                        <span>Add Bike</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        const fileInput = document.getElementById('file-upload');
        const imagePreviewContainer = document.getElementById('image-preview-container');
        const imagePreview = document.getElementById('image-preview');
        const uploadDropzone = document.getElementById('upload-dropzone');
        const removeImageBtn = document.getElementById('remove-image');

        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.classList.remove('hidden');
                    uploadDropzone.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        removeImageBtn.addEventListener('click', function () {
            fileInput.value = '';
            imagePreview.src = '#';
            imagePreviewContainer.classList.add('hidden');
            uploadDropzone.classList.remove('hidden');
        });

        // --- "Other" dropdown handling ---
        // When a dropdown's "Other" option is selected, show the custom text
        // input and make it required. When any other option is chosen, hide
        // the input and clear its value.
        const otherFields = [
            { select: 'brand', input: 'brand_other' },
            { select: 'color', input: 'color_other' },
            { select: 'city', input: 'city_other' },
        ];

        otherFields.forEach(function (pair) {
            const select = document.getElementById(pair.select);
            const input = document.getElementById(pair.input);
            if (!select || !input) return;

            function sync() {
                const isOther = select.value === 'Other';
                input.classList.toggle('hidden', !isOther);
                input.required = isOther;
                if (!isOther) {
                    input.value = '';
                }
            }

            select.addEventListener('change', sync);
            sync(); // run once on load to restore state after validation errors
        });
    </script>
</body>
