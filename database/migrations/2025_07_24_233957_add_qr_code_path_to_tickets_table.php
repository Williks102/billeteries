use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQrCodePathToTicketsTable extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            // QR Code et validation
            $table->string('qr_code_path')->nullable()->after('status');
            $table->timestamp('used_at')->nullable()->after('qr_code_path');
            $table->json('validation_data')->nullable()->after('used_at');
            
            // Tracking
            $table->ipAddress('created_ip')->nullable()->after('holder_phone');
            $table->timestamp('sent_at')->nullable()->after('created_ip');
            $table->timestamp('downloaded_at')->nullable()->after('sent_at');
            $table->integer('download_count')->default(0)->after('downloaded_at');
            
            // Index pour performances
            $table->index(['used_at']);
            $table->index(['status', 'used_at']);
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['used_at']);
            $table->dropIndex(['status', 'used_at']);
            
            $table->dropColumn([
                'qr_code_path',
                'used_at', 
                'validation_data',
                'created_ip',
                'sent_at',
                'downloaded_at',
                'download_count'
            ]);
        });
    }
}