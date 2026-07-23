<?php

namespace Database\Seeders;

use App\Models\ProviderDirectoryEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProviderDirectorySeeder extends Seeder
{
    private array $providers = [
        // Lagos
        ['name' => 'Lagos University Teaching Hospital (LUTH)', 'specialty' => 'General Medicine', 'city' => 'Surulere', 'state' => 'Lagos', 'is_verified' => true],
        ['name' => 'Reddington Hospital', 'specialty' => 'Cardiology', 'city' => 'Ikeja', 'state' => 'Lagos', 'is_verified' => true],
        ['name' => 'First Cardiology Consultants', 'specialty' => 'Cardiology', 'city' => 'Ikoyi', 'state' => 'Lagos', 'is_verified' => true],
        ['name' => 'Marcelle Ruth Cancer Centre', 'specialty' => 'Oncology', 'city' => 'Victoria Island', 'state' => 'Lagos', 'is_verified' => true],
        ['name' => 'St. Nicholas Hospital', 'specialty' => 'General Medicine', 'city' => 'Lagos Island', 'state' => 'Lagos', 'is_verified' => true],
        ['name' => 'Eko Hospital', 'specialty' => 'General Medicine', 'city' => 'Surulere', 'state' => 'Lagos', 'is_verified' => false],
        ['name' => 'Lagoon Hospitals', 'specialty' => 'Paediatrics', 'city' => 'Apapa', 'state' => 'Lagos', 'is_verified' => true],
        ['name' => 'Nordica Fertility Centre', 'specialty' => 'Obstetrics & Gynaecology', 'city' => 'Victoria Island', 'state' => 'Lagos', 'is_verified' => true],

        // Abuja (FCT)
        ['name' => 'National Hospital Abuja', 'specialty' => 'General Medicine', 'city' => 'Central Area', 'state' => 'FCT', 'is_verified' => true],
        ['name' => 'Cedarcrest Hospitals', 'specialty' => 'Orthopaedics', 'city' => 'Jabi', 'state' => 'FCT', 'is_verified' => true],
        ['name' => 'Nisa Premier Hospital', 'specialty' => 'Obstetrics & Gynaecology', 'city' => 'Jabi', 'state' => 'FCT', 'is_verified' => true],
        ['name' => 'Kelina Hospital', 'specialty' => 'Urology', 'city' => 'Gwarinpa', 'state' => 'FCT', 'is_verified' => true],

        // Port Harcourt
        ['name' => 'University of Port Harcourt Teaching Hospital', 'specialty' => 'General Medicine', 'city' => 'Port Harcourt', 'state' => 'Rivers', 'is_verified' => true],
        ['name' => 'Kelsea Memorial Hospital', 'specialty' => 'Endocrinology', 'city' => 'Port Harcourt', 'state' => 'Rivers', 'is_verified' => false],
        ['name' => 'Meridian Hospitals', 'specialty' => 'Cardiology', 'city' => 'Port Harcourt', 'state' => 'Rivers', 'is_verified' => true],

        // Kano
        ['name' => 'Aminu Kano Teaching Hospital', 'specialty' => 'General Medicine', 'city' => 'Kano', 'state' => 'Kano', 'is_verified' => true],
        ['name' => 'Primecare Healthcare', 'specialty' => 'Paediatrics', 'city' => 'Kano', 'state' => 'Kano', 'is_verified' => false],

        // Ibadan
        ['name' => 'University College Hospital (UCH) Ibadan', 'specialty' => 'General Medicine', 'city' => 'Ibadan', 'state' => 'Oyo', 'is_verified' => true],
        ['name' => 'Oluyoro Catholic Hospital', 'specialty' => 'Obstetrics & Gynaecology', 'city' => 'Ibadan', 'state' => 'Oyo', 'is_verified' => false],

        // Enugu
        ['name' => 'University of Nigeria Teaching Hospital (UNTH)', 'specialty' => 'General Medicine', 'city' => 'Enugu', 'state' => 'Enugu', 'is_verified' => true],
        ['name' => 'Memfys Hospital for Neurosurgery', 'specialty' => 'Neurology', 'city' => 'Enugu', 'state' => 'Enugu', 'is_verified' => true],

        // Benin
        ['name' => 'University of Benin Teaching Hospital', 'specialty' => 'General Medicine', 'city' => 'Benin City', 'state' => 'Edo', 'is_verified' => true],
        ['name' => 'Benin Medical Centre', 'specialty' => 'Dermatology', 'city' => 'Benin City', 'state' => 'Edo', 'is_verified' => false],

        // Jos
        ['name' => 'Jos University Teaching Hospital', 'specialty' => 'General Medicine', 'city' => 'Jos', 'state' => 'Plateau', 'is_verified' => true],

        // Kaduna
        ['name' => 'Barau Dikko Teaching Hospital', 'specialty' => 'General Medicine', 'city' => 'Kaduna', 'state' => 'Kaduna', 'is_verified' => true],

        // Abuja specialists
        ['name' => 'Eye Foundation Hospital', 'specialty' => 'Ophthalmology', 'city' => 'Garki', 'state' => 'FCT', 'is_verified' => true],
        ['name' => 'Wellington Neurosurgery', 'specialty' => 'Neurology', 'city' => 'Maitama', 'state' => 'FCT', 'is_verified' => true],

        // Lagos specialists
        ['name' => 'Eye Doctors Lagos', 'specialty' => 'Ophthalmology', 'city' => 'Lekki', 'state' => 'Lagos', 'is_verified' => true],
        ['name' => 'Skin101 Clinics', 'specialty' => 'Dermatology', 'city' => 'Lekki', 'state' => 'Lagos', 'is_verified' => true],
        ['name' => 'The Bridge Clinic', 'specialty' => 'Obstetrics & Gynaecology', 'city' => 'Ikoyi', 'state' => 'Lagos', 'is_verified' => true],
    ];

    public function run(): void
    {
        foreach ($this->providers as $data) {
            ProviderDirectoryEntry::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                array_merge($data, [
                    'bio' => 'A healthcare provider located in ' . ($data['city'] ?? 'Nigeria') . '.',
                    'address' => $data['city'] . ', ' . $data['state'],
                    'country' => 'Nigeria',
                    'is_active' => true,
                ]),
            );
        }
    }
}
