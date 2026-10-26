<?php
// Veritabanına başlangıç verisi yazan sınıf (php artisan db:seed). Sadece bir test kullanıcısı ekler.
// DİKKAT: Test kullanıcısının şifresi UserFactory'deki sabit 'password' değeridir. Bu kullanıcı yönetici değildir (is_admin false); canlı ortamda çalıştırılmamalıdır.

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Ana seeder. 'db:seed' komutu çalışınca önce bu sınıfın run() metodu çalışır.
 * WithoutModelEvents: seed sırasında model olayları tetiklenmez.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Birden fazla örnek kullanıcı için bu satır açılabilir; şu an kapalı.
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
