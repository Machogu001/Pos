<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Utils\TransactionUtil;
use App\Transaction;

class RenderInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:render-invoice {transaction_id} {--out=}' ;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Render the invoice HTML for a given transaction id and save to a file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $transaction_id = $this->argument('transaction_id');

        $transaction = Transaction::find($transaction_id);
        if (empty($transaction)) {
            $this->error("Transaction {$transaction_id} not found.");
            return 1;
        }

        $tu = new TransactionUtil();

        $this->info('Building receipt contents...');
        $contents = $tu->getPdfContentsForGivenTransaction($transaction->business_id, $transaction->id);

        $receipt_details = $contents['receipt_details'];
        $location_details = $contents['location_details'];

        $is_email_attachment = false;

        $this->info('Rendering Blade view to HTML...');
        $body = view('sale_pos.receipts.download_pdf')
                    ->with(compact('receipt_details', 'location_details', 'is_email_attachment'))
                    ->render();

        $out = $this->option('out') ?: "/tmp/invoice_{$transaction_id}.html";

        file_put_contents($out, $body);

        $this->info("Saved rendered invoice to: {$out}");

        return 0;
    }
}
