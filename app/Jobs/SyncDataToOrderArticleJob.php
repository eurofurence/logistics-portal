<?php

namespace App\Jobs;

use Exception;
use App\Models\OrderArticle;
use Illuminate\Bus\Batchable;
use App\Services\AsinDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\Middleware\ThrottlesExceptions;

class SyncDataToOrderArticleJob implements ShouldQueue
{
    use Dispatchable, Queueable, Batchable, SerializesModels;

    protected int $current_user_id;
    protected array $fields;
    protected int $order_article_id;
    protected OrderArticle $order_article;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(OrderArticle $order_article, int $current_user_id, array $fields)
    {
        try {
            $this->order_article_id = $order_article->id;
            $this->order_article = $order_article;
            $this->current_user_id = $current_user_id;
            $this->fields = $fields;
        } catch (Exception $e) {
            $this->fail($e);
        }
    }

    /*
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10);
    }
        */

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('SyncDataToOrderArticle'), (new WithoutOverlapping($this->order_article_id))->releaseAfter(15)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->batch()) {
                if ($this->batch()->cancelled()) {
                    // Determine if the batch has been cancelled...
                    return;
                }
            }

            Cache::put('SyncDataToOrderArticleJob_running', true, now()->addMinutes(20));

            $asin_data_service = new AsinDataService();

            if (empty($this->order_article)) {
                $this->logError("Order article not found", $this->order_article_id);
                Cache::forget('SyncDataToOrderArticleJob_running');
                $this->fail("Order article not found");
                return;
            }

            $url = $this->order_article->url;

            // Check whether the URL is a short Amazon link
            if (preg_match('#^https://amzn\.eu/.*#', $url)) {
                $dirty_url = $asin_data_service->resolveAmazonUrl($url);
                $url = $asin_data_service->cleanAmazonUrl($dirty_url);
            }

            if (empty($url)) {
                $this->logError("URL is empty", $this->order_article_id);
                Cache::forget('SyncDataToOrderArticleJob_running');
                $this->fail("URL is empty");
                return;
            }

            if (preg_match('/https?:\/\/(www\.)?amazon\.[a-z]{2,3}(\/.*)?$/', $url)) {
                // Credits check
                if ($asin_data_service->getCredits() <= 0) {
                    $this->logError("Not enough credits", $this->order_article_id);
                    Cache::forget('SyncDataToOrderArticleJob_running');
                    $this->fail("Not enough credits");
                    return;
                }

                // ASIN-Daten holen
                $asin = $asin_data_service->extractASIN($url);
                $product_data = $asin_data_service->getProductData($asin);

                if (!empty($this->fields)) {
                    // Check whether all required data is available
                    foreach ($this->fields as $key => $field) {
                        if (
                            ($field === 'name' && empty($product_data['product']['title'])) ||
                            ($field === 'price_gross' && empty($product_data['product']['buybox_winner']['price']['value'])) ||
                            ($field === 'picture' && empty($product_data['product']['main_image']['link'])) ||
                            ($field === 'url' && empty($product_data['product']['link'])) ||
                            ($field === 'article_number' && empty($product_data['product']['asin']))
                        ) {
                            unset($this->fields[$key]); // Remove the field from the list
                        }
                    }

                    // If there are no more fields left, throw an exception
                    if (empty($this->fields)) {
                        throw new Exception('All required product data fields are missing or null.');
                    }

                    // Putting the data into the model
                    if (in_array('name', $this->fields)) {
                        $this->order_article->name = $product_data['product']['title'];
                    }

                    if (in_array('price_gross', $this->fields)) {
                        $this->order_article->price_gross = $product_data['product']['buybox_winner']['price']['value'];
                        $taxRate = $this->order_article->tax_rate;
                        $priceNet = $product_data['product']['buybox_winner']['price']['value'] / (1 + $taxRate / 100);
                        $this->order_article->price_net = round($priceNet, 2);
                    }

                    if (in_array('picture', $this->fields)) {
                        $this->order_article->picture = $product_data['product']['main_image']['link'];
                    }

                    if (in_array('url', $this->fields)) {
                        $this->order_article->url = $product_data['product']['link'];
                    }

                    if (in_array('article_number', $this->fields)) {
                        $this->order_article->article_number = $product_data['product']['asin'];
                    }

                    // Saving the changes in the model
                    $this->order_article->save();
                }
            } else {
                $this->logError("Invalid URL format: " . $url, $this->order_article_id);
            }

            Cache::forget('SyncDataToOrderArticleJob_running');
        } catch (Exception $e) {
            $this->logError($e->getMessage(), $this->order_article_id);
            $this->fail($e);
            Cache::forget('SyncDataToOrderArticleJob_running');
        }

        Cache::forget('SyncDataToOrderArticleJob_running');
    }

    /**
     * Log errors to a file and send email.
     */
    private function logError(string $reason, int $article_id): void
    {
        try {
            $timestamp = now()->toDateTimeString();
            $jobTitle = 'SyncDataToOrderArticleJob';

            $logEntry = "User ID: {$this->current_user_id}, Job Title: {$jobTitle}, Timestamp: {$timestamp}, Error: {$reason}, Article ID: {$article_id}\n";

            Storage::disk('local_logs')->append('/job_errors_' . $article_id . '.log', $logEntry);
        } catch (Exception $e) {
            $this->fail($e);
        }
    }
}
