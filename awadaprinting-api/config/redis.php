<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Predis\Client;
$redis = new Client([
    'scheme' => 'tcp',
    'host' => '127.0.0.1',
    'port' => 6379,
    'database' => 0

]);
global $redis;
function clearCacheByPattern(Client $redis, string $pattern): void
{
    try {
        $keys = $redis->keys($pattern);
        if (!empty($keys)) {
            foreach (array_chunk($keys, 1000) as $chunk) {
                $redis->del($chunk);
            }
        }
    } catch (\Exception $e) {
    }
}
function clearCustomersCache(Client $redis): void
{
    clearCacheByPattern($redis, 'customers:*');
}
function clearSuppliersCache(Client $redis): void
{
    clearCacheByPattern($redis, 'suppliers:*');
}
function clearStockCache(Client $redis): void
{
    clearCacheByPattern($redis, 'stock:*');
}
function clearPurchasesCache(Client $redis): void
{
    clearCacheByPattern($redis, 'purchases:*');
}