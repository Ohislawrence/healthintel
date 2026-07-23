<?php

namespace Database\Seeders;

use App\Models\ProviderDirectoryEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnhancedProviderSeeder extends Seeder
{
    private array $providers = [
        // Hospitals - Lagos
        ['name' => 'Lagos University Teaching Hospital (LUTH)', 'type' => 'hospital', 'spec' => 'General Medicine', 'city' => 'Surulere', 'state' => 'Lagos', 'lat' => 6.4674, 'lng' => 3.3536, 'partner' => 'affiliate', 'link' => 'https://luth.gov.ng'],
        ['name' => 'Reddington Hospital', 'type' => 'hospital', 'spec' => 'Cardiology', 'city' => 'Ikeja', 'state' => 'Lagos', 'lat' => 6.6018, 'lng' => 3.3515, 'partner' => 'none', 'link' => null],
        ['name' => 'First Cardiology Consultants', 'type' => 'specialist', 'spec' => 'Cardiology', 'city' => 'Ikoyi', 'state' => 'Lagos', 'lat' => 6.4531, 'lng' => 3.4355, 'partner' => 'affiliate', 'link' => 'https://firstcardiologyng.com'],
        ['name' => 'St. Nicholas Hospital', 'type' => 'hospital', 'spec' => 'General Medicine', 'city' => 'Lagos Island', 'state' => 'Lagos', 'lat' => 6.4550, 'lng' => 3.3941, 'partner' => 'none', 'link' => null],
        ['name' => 'Eko Hospital', 'type' => 'hospital', 'spec' => 'General Medicine', 'city' => 'Surulere', 'state' => 'Lagos', 'lat' => 6.4725, 'lng' => 3.3620, 'partner' => 'sponsored', 'link' => 'https://ekohospitals.com'],
        ['name' => 'Lagoon Hospitals', 'type' => 'hospital', 'spec' => 'Paediatrics', 'city' => 'Apapa', 'state' => 'Lagos', 'lat' => 6.4570, 'lng' => 3.3580, 'partner' => 'affiliate', 'link' => 'https://lagoonhospitals.com'],
        ['name' => 'Marcelle Ruth Cancer Centre', 'type' => 'specialist', 'spec' => 'Oncology', 'city' => 'Victoria Island', 'state' => 'Lagos', 'lat' => 6.4281, 'lng' => 3.4220, 'partner' => 'affiliate', 'link' => 'https://marcelleruth.com'],
        ['name' => 'Nordica Fertility Centre', 'type' => 'specialist', 'spec' => 'Obstetrics & Gynaecology', 'city' => 'Victoria Island', 'state' => 'Lagos', 'lat' => 6.4320, 'lng' => 3.4200, 'partner' => 'none', 'link' => null],
        ['name' => 'Eye Doctors Lagos', 'type' => 'clinic', 'spec' => 'Ophthalmology', 'city' => 'Lekki', 'state' => 'Lagos', 'lat' => 6.4410, 'lng' => 3.4760, 'partner' => 'affiliate', 'link' => 'https://eyedoctorslagos.com'],
        ['name' => 'Skin101 Clinics', 'type' => 'clinic', 'spec' => 'Dermatology', 'city' => 'Lekki', 'state' => 'Lagos', 'lat' => 6.4430, 'lng' => 3.4740, 'partner' => 'none', 'link' => null],
        ['name' => 'The Bridge Clinic', 'type' => 'clinic', 'spec' => 'Obstetrics & Gynaecology', 'city' => 'Ikoyi', 'state' => 'Lagos', 'lat' => 6.4550, 'lng' => 3.4400, 'partner' => 'affiliate', 'link' => 'https://thebridgeclinic.com'],

        // Abuja (FCT)
        ['name' => 'National Hospital Abuja', 'type' => 'hospital', 'spec' => 'General Medicine', 'city' => 'Central Area', 'state' => 'FCT', 'lat' => 9.0580, 'lng' => 7.4891, 'partner' => 'none', 'link' => null],
        ['name' => 'Cedarcrest Hospitals', 'type' => 'hospital', 'spec' => 'Orthopaedics', 'city' => 'Jabi', 'state' => 'FCT', 'lat' => 9.0667, 'lng' => 7.4314, 'partner' => 'affiliate', 'link' => 'https://cedarcresthospitals.com'],
        ['name' => 'Nisa Premier Hospital', 'type' => 'hospital', 'spec' => 'Obstetrics & Gynaecology', 'city' => 'Jabi', 'state' => 'FCT', 'lat' => 9.0680, 'lng' => 7.4300, 'partner' => 'none', 'link' => null],
        ['name' => 'Kelina Hospital', 'type' => 'hospital', 'spec' => 'Urology', 'city' => 'Gwarinpa', 'state' => 'FCT', 'lat' => 9.1000, 'lng' => 7.4100, 'partner' => 'affiliate', 'link' => 'https://kelinahospital.com'],
        ['name' => 'Eye Foundation Hospital', 'type' => 'specialist', 'spec' => 'Ophthalmology', 'city' => 'Garki', 'state' => 'FCT', 'lat' => 9.0320, 'lng' => 7.4850, 'partner' => 'none', 'link' => null],
        ['name' => 'Wellington Neurosurgery', 'type' => 'specialist', 'spec' => 'Neurology', 'city' => 'Maitama', 'state' => 'FCT', 'lat' => 9.0920, 'lng' => 7.4710, 'partner' => 'affiliate', 'link' => 'https://wellingtonneurosurgery.com'],

        // Port Harcourt
        ['name' => 'University of Port Harcourt Teaching Hospital', 'type' => 'hospital', 'spec' => 'General Medicine', 'city' => 'Port Harcourt', 'state' => 'Rivers', 'lat' => 4.8980, 'lng' => 6.9190, 'partner' => 'none', 'link' => null],
        ['name' => 'Meridian Hospitals', 'type' => 'hospital', 'spec' => 'Cardiology', 'city' => 'Port Harcourt', 'state' => 'Rivers', 'lat' => 4.8242, 'lng' => 7.0360, 'partner' => 'affiliate', 'link' => 'https://meridianhospitals.com'],

        // Ibadan
        ['name' => 'University College Hospital (UCH) Ibadan', 'type' => 'hospital', 'spec' => 'General Medicine', 'city' => 'Ibadan', 'state' => 'Oyo', 'lat' => 7.4025, 'lng' => 3.9030, 'partner' => 'none', 'link' => null],

        // Enugu
        ['name' => 'University of Nigeria Teaching Hospital (UNTH)', 'type' => 'hospital', 'spec' => 'General Medicine', 'city' => 'Enugu', 'state' => 'Enugu', 'lat' => 6.4531, 'lng' => 7.4850, 'partner' => 'none', 'link' => null],
        ['name' => 'Memfys Hospital for Neurosurgery', 'type' => 'specialist', 'spec' => 'Neurology', 'city' => 'Enugu', 'state' => 'Enugu', 'lat' => 6.4580, 'lng' => 7.5100, 'partner' => 'affiliate', 'link' => 'https://memfys.com'],

        // Pharmacies
        ['name' => 'HealthPlus Pharmacy', 'type' => 'pharmacy', 'spec' => 'Retail Pharmacy', 'city' => 'Lekki', 'state' => 'Lagos', 'lat' => 6.4400, 'lng' => 3.4720, 'partner' => 'affiliate', 'link' => 'https://healthplus.com.ng'],
        ['name' => 'MedPlus Pharmacy', 'type' => 'pharmacy', 'spec' => 'Retail Pharmacy', 'city' => 'Ikeja', 'state' => 'Lagos', 'lat' => 6.6050, 'lng' => 3.3500, 'partner' => 'none', 'link' => null],

        // Labs
        ['name' => 'Mecure Diagnostics', 'type' => 'lab', 'spec' => 'Diagnostic Laboratory', 'city' => 'Ikeja', 'state' => 'Lagos', 'lat' => 6.6000, 'lng' => 3.3450, 'partner' => 'affiliate', 'link' => 'https://mecure.com.ng'],
        ['name' => 'Synlab Nigeria', 'type' => 'lab', 'spec' => 'Diagnostic Laboratory', 'city' => 'Victoria Island', 'state' => 'Lagos', 'lat' => 6.4340, 'lng' => 3.4180, 'partner' => 'affiliate', 'link' => 'https://synlab.com.ng'],
    ];

    private array $hmos = [
        'Hygeia HMO' => ['Platinum', 'Gold', 'Silver'],
        'Reliance HMO' => ['Infinity', 'Titanium', 'Classic'],
        'Avon HMO' => ['Comprehensive', 'Standard'],
        'Redcare HMO' => ['Premium', 'Classic'],
        'Leadway Health' => ['Elite', 'Advantage', 'Basic'],
    ];

    public function run(): void
    {
        foreach ($this->providers as $data) {
            ProviderDirectoryEntry::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'specialty' => $data['spec'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'country' => 'Nigeria',
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'partner_status' => $data['partner'],
                    'referral_link' => $data['link'],
                    'is_verified' => true,
                    'is_active' => true,
                    'bio' => $data['name'] . ' is a ' . ($data['type'] === 'hospital' ? 'hospital' : $data['type']) . ' located in ' . $data['city'] . ', ' . $data['state'] . '.',
                    'address' => $data['name'] . ', ' . $data['city'] . ', ' . $data['state'] . ', Nigeria',
                ],
            );
        }

        // Create HMO providers
        foreach ($this->hmos as $hmoName => $plans) {

            $h = ProviderDirectoryEntry::updateOrCreate(
                ['slug' => Str::slug($hmoName)],
                [
                    'name' => $hmoName,
                    'type' => 'insurance',
                    'specialty' => 'Health Insurance',
                    'city' => 'Nigeria',
                    'state' => 'FCT',
                    'country' => 'Nigeria',
                    'is_verified' => true,
                    'is_active' => true,
                    'insurance_plans' => $plans,
                    'partner_status' => 'affiliate',
                    'referral_link' => 'https://' . Str::slug($hmoName) . '.com',
                    'bio' => $hmoName . ' offers ' . count($plans) . ' health insurance plan tiers: ' . implode(', ', $plans) . '.',
                ],
            );

            // Auto-generate access code for HMO partners
            if (!$h->access_code) {
                $h->update([
                    'access_code' => Str::random(40),
                    'access_code_generated_at' => now(),
                ]);
            }
        }
    }
}
