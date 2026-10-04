<?php

namespace Database\Seeders\Support;

/**
 * Tenant identities for the showcase dataset.
 *
 * Three properties need something like ninety distinct tenants between them,
 * which is more than is worth writing out by hand, so names are composed from
 * curated per-nationality pools. Pairing is deterministic — a given name is
 * matched to a family name from the same pool by a fixed stride — so the same
 * index always produces the same person and screenshots stay stable.
 *
 * Everything here is invented. Emails are on example.com (reserved by RFC 2606)
 * and no company name belongs to a real business: this data lands in a database
 * that also holds live records, and none of it may be mistakable for a real
 * contactable party.
 */
final class ShowcaseNames
{
    /**
     * Given and family names are kept in matching pools per nationality, so a
     * name is only ever composed from within one pool.
     *
     * @var array<string, array{given: list<string>, family: list<string>}>
     */
    private const POOLS = [
        'Bahraini' => [
            'given' => [
                'Hussain', 'Fatima', 'Noora', 'Omar', 'Layla', 'Khalifa', 'Reem', 'Mohammed',
                'Sara', 'Ali', 'Maryam', 'Yousif', 'Hamad', 'Aisha', 'Salman', 'Zainab',
                'Abdulla', 'Huda', 'Jassim', 'Amal',
            ],
            'family' => [
                'Al Mannai', 'Al Dosari', 'Al Binali', 'Al Zayani', 'Al Qassab', 'Al Ansari',
                'Al Shaikh', 'Al Rumaihi', 'Al Aali', 'Al Khaja', 'Al Sayegh', 'Al Alawi',
                'Buhazza', 'Al Jishi', 'Al Noaimi', 'Al Kooheji', 'Fakhro', 'Al Musallam',
                'Al Bastaki', 'Al Mahmood',
            ],
        ],
        'Indian' => [
            'given' => [
                'Rajesh', 'Suresh', 'Vikram', 'Priya', 'Anil', 'Deepa', 'Sanjay', 'Meena',
                'Arun', 'Kavita', 'Ramesh', 'Lakshmi', 'Vinod', 'Anita', 'Manoj', 'Shalini',
            ],
            'family' => [
                'Nair', 'Menon', 'Chauhan', 'Raman', 'Kumar', 'Pillai', 'Sharma', 'Iyer',
                'Reddy', 'Joshi', 'Varghese', 'Desai', 'Bhat', 'Rao', 'Kulkarni', 'Thomas',
            ],
        ],
        'Filipino' => [
            'given' => [
                'Maria', 'Jose', 'Ana', 'Ramon', 'Grace', 'Carlos', 'Rosalie', 'Eduardo',
                'Cristina', 'Manuel',
            ],
            'family' => [
                'Santos', 'Reyes', 'Cruz', 'Bautista', 'Villanueva', 'Mendoza',
                'Aquino', 'Del Rosario', 'Navarro', 'Castillo',
            ],
        ],
        'Egyptian' => [
            'given'  => ['Ahmed', 'Mona', 'Sayed', 'Nadia', 'Tarek', 'Heba', 'Mostafa', 'Yasmin'],
            'family' => ['Kamal', 'Hassan', 'Abdelrahman', 'Fouad', 'Mahmoud', 'Zaki', 'Shokry', 'Nour'],
        ],
        'Pakistani' => [
            'given'  => ['Imran', 'Ayesha', 'Tariq', 'Saima', 'Bilal', 'Nasreen', 'Faisal', 'Rabia'],
            'family' => ['Sheikh', 'Qureshi', 'Malik', 'Chaudhry', 'Abbasi', 'Raza', 'Butt', 'Jamil'],
        ],
        'British' => [
            'given'  => ['James', 'Emma', 'Oliver', 'Charlotte', 'Thomas', 'Sophie', 'Daniel', 'Alice'],
            'family' => ['Whitfield', 'Harrington', 'Brennan', 'Ashcroft', 'Fairbanks', 'Lockwood', 'Pemberton', 'Sinclair'],
        ],
        'Jordanian' => [
            'given'  => ['Yousef', 'Rana', 'Nabil', 'Dima', 'Bashar', 'Lina'],
            'family' => ['Haddad', 'Khoury', 'Masri', 'Zoubi', 'Barakat', 'Tarawneh'],
        ],
        'Kenyan' => [
            'given'  => ['Grace', 'Daniel', 'Wanjiru', 'Kamau', 'Achieng', 'Otieno'],
            'family' => ['Mwangi', 'Kariuki', 'Wekesa', 'Njoroge', 'Odhiambo', 'Chepkwony'],
        ],
        'Sri Lankan' => [
            'given'  => ['Nimal', 'Sunethra', 'Kamal', 'Dilani', 'Ranjan', 'Chandima'],
            'family' => ['Perera', 'Fernando', 'Silva', 'Jayawardena', 'Bandara', 'Rajapaksa'],
        ],
    ];

    /**
     * Company names are assembled from a qualifier, a trade and a Bahraini
     * legal form, so they read like a CR register without naming anyone real.
     *
     * @var list<string>
     */
    private const COMPANY_QUALIFIER = [
        'Gulf Horizon', 'Pearl Coast', 'Delmon', 'Al Waha', 'Northern Gulf', 'Seef',
        'Manama', 'Arabian Wellness', 'Bright Path', 'Ittihad', 'Silver Sands',
        'Dilmun', 'Riffa', 'Muharraq', 'Golden Dune', 'Blue Reef', 'Capital',
        'Oasis Line', 'Marsa', 'Zallaq',
    ];

    /** @var list<string> */
    private const COMPANY_TRADE = [
        'Trading', 'Interiors', 'Digital Networks', 'Café & Roastery', 'Logistics',
        'Legal Consultancy', 'Pharmacy', 'Tutoring Centre', 'Engineering Services',
        'Dental Care', 'Travel & Tourism', 'Contracting', 'Marine Services',
        'Facilities Services', 'Auto Care', 'Print & Signage', 'Catering',
        'Fitness Studio', 'Optical Centre', 'IT Solutions',
    ];

    /** W.L.L. weighted highest because it is the commonest form on the register. */
    private const COMPANY_FORM = ['W.L.L.', 'S.P.C.', 'B.S.C.(c)', 'W.L.L.', 'W.L.L.'];

    /**
     * Builds $count tenant records. Roughly one in three is a company, and the
     * companies come first so a caller that lets its commercial units in unit
     * order gets a business in the shop rather than a family.
     *
     * $offset walks the pools forward, so two properties seeded in the same run
     * do not end up with the same tenants.
     *
     * @return list<array{name: string, tenant_type: string, company_name: ?string,
     *                    id_cr_number: string, phone: string, email: string,
     *                    nationality_country: string}>
     */
    public static function build(int $count, int $offset = 0): array
    {
        $companies = (int) max(1, round($count / 3));
        $people    = $count - $companies;

        $out  = [];
        $seen = [];

        for ($i = 0; $i < $companies; $i++) {
            $n = $offset + $i;

            $name = self::unique(
                self::COMPANY_QUALIFIER[$n % count(self::COMPANY_QUALIFIER)]
                . ' ' . self::COMPANY_TRADE[($n * 7 + 3) % count(self::COMPANY_TRADE)]
                . ' ' . self::COMPANY_FORM[$n % count(self::COMPANY_FORM)],
                $seen,
            );

            $out[] = [
                'name'                => $name,
                'tenant_type'         => 'company',
                'company_name'        => $name,
                'id_cr_number'        => (60000 + $n * 813) . '-' . (1 + $n % 3),
                'phone'               => '17' . str_pad((string) (400000 + $n * 1379 % 599999), 6, '0', STR_PAD_LEFT),
                'email'               => 'accounts+' . ($n + 1) . '@example.com',
                'nationality_country' => 'Bahrain',
            ];
        }

        $nationalities = array_keys(self::POOLS);

        for ($i = 0; $i < $people; $i++) {
            $n           = $offset + $i;
            $nationality = $nationalities[$n % count($nationalities)];

            $given  = self::POOLS[$nationality]['given'];
            $family = self::POOLS[$nationality]['family'];

            // Different strides on the two pools, so consecutive tenants do
            // not arrive in blocks sharing a surname.
            $name = self::unique(
                $given[($n * 3) % count($given)] . ' ' . $family[($n * 5 + 2) % count($family)],
                $seen,
            );

            $slug = strtolower(trim(preg_replace('/[^a-z]+/i', '.', $name), '.'));

            $out[] = [
                'name'                => $name,
                'tenant_type'         => 'individual',
                'company_name'        => null,
                // A Bahraini CPR is nine digits; expatriate residents are
                // recorded here by passport number instead.
                'id_cr_number'        => $nationality === 'Bahraini'
                    ? (string) (760000000 + ($n * 1234567) % 180000000)
                    : strtoupper(substr($nationality, 0, 2)) . (1000000 + $n * 7919),
                'phone'               => '3' . str_pad((string) (3000000 + ($n * 20411) % 6999999), 7, '0', STR_PAD_LEFT),
                'email'               => $slug . '.' . ($n + 1) . '@example.com',
                'nationality_country' => $nationality,
            ];
        }

        return $out;
    }

    /**
     * Keeps names distinct within one batch. The pools are large enough that
     * collisions are rare, but a repeated tenant name in a list reads as a
     * duplicate record rather than as a coincidence.
     *
     * @param  array<string, true>  $seen
     */
    private static function unique(string $name, array &$seen): string
    {
        $candidate = $name;
        $suffix    = 2;

        while (isset($seen[$candidate])) {
            $candidate = $name . ' ' . self::roman($suffix++);
        }

        $seen[$candidate] = true;

        return $candidate;
    }

    private static function roman(int $n): string
    {
        return match ($n) {
            2       => 'II',
            3       => 'III',
            4       => 'IV',
            default => (string) $n,
        };
    }
}
