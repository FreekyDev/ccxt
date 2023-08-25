<?php

namespace ccxt\pro;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use ccxt\InvalidNonce;
use ccxt\Precise;
use React\Async;

class idex extends \ccxt\async\idex {

    public function describe() {
        return $this->deep_extend(parent::describe(), array(
            'has' => array(
                'ws' => true,
                'watchOrderBook' => true,
                'watchTrades' => true,
                'watchOHLCV' => true,
                'watchTicker' => true,
                'watchTickers' => false, // for now
                'watchOrders' => true,
                'watchTransactions' => true,
            ),
            'urls' => array(
                'test' => array(
                    'ws' => 'wss://websocket-matic.idex.io/v1',
                ),
                'api' => array(),
            ),
            'options' => array(
                'tradesLimit' => 1000,
                'ordersLimit' => 1000,
                'OHLCVLimit' => 1000,
                'watchOrderBookLimit' => 1000, // default limit
                'orderBookSubscriptions' => array(),
                'token' => null,
                'watchOrderBook' => array(
                    'maxRetries' => 3,
                ),
                'fetchOrderBookSnapshotMaxAttempts' => 10,
                'fetchOrderBookSnapshotMaxDelay' => 10000, // throw if there are no orders in 10 seconds
            ),
        ));
    }

    public function subscribe($subscribeObject, $messageHash, $subscription = true) {
        return Async\async(function () use ($subscribeObject, $messageHash, $subscription) {
            $url = $this->urls['test']['ws'];
            $request = array(
                'method' => 'subscribe',
                'subscriptions' => array(
                    $subscribeObject,
                ),
            );
            return Async\await($this->watch($url, $messageHash, $request, $messageHash, $subscription));
        }) ();
    }

    public function subscribe_private($subscribeObject, $messageHash) {
        return Async\async(function () use ($subscribeObject, $messageHash) {
            $token = Async\await($this->authenticate());
            $url = $this->urls['test']['ws'];
            $request = array(
                'method' => 'subscribe',
                'token' => $token,
                'subscriptions' => array(
                    $subscribeObject,
                ),
            );
            return Async\await($this->watch($url, $messageHash, $request, $messageHash));
        }) ();
    }

    public function watch_ticker(string $symbol, $params = array ()) {
        return Async\async(function () use ($symbol, $params) {
            /**
             * watches a price ticker, a statistical calculation with the information calculated over the past 24 hours for a specific $market
             * @param {string} $symbol unified $symbol of the $market to fetch the ticker for
             * @param {array} [$params] extra parameters specific to the idex api endpoint
             * @return {array} a {@link https://github.com/ccxt/ccxt/wiki/Manual#ticker-structure ticker structure}
             */
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $name = 'tickers';
            $subscribeObject = array(
                'name' => $name,
                'markets' => [ $market['id'] ],
            );
            $messageHash = $name . ':' . $market['id'];
            return Async\await($this->subscribe(array_merge($subscribeObject, $params), $messageHash));
        }) ();
    }

    public function handle_ticker(Client $client, $message) {
        // { $type => 'tickers',
        //   $data:
        //    { m => 'DIL-ETH',
        //      t => 1599213946045,
        //      o => '0.09699020',
        //      h => '0.10301548',
        //      l => '0.09577222',
        //      c => '0.09907311',
        //      Q => '1.32723120',
        //      v => '297.80667468',
        //      q => '29.52142669',
        //      P => '2.14',
        //      n => 197,
        //      a => '0.09912245',
        //      b => '0.09686980',
        //      u => 5870 } }
        $type = $this->safe_string($message, 'type');
        $data = $this->safe_value($message, 'data');
        $marketId = $this->safe_string($data, 'm');
        $symbol = $this->safe_symbol($marketId);
        $messageHash = $type . ':' . $marketId;
        $timestamp = $this->safe_integer($data, 't');
        $close = $this->safe_string($data, 'c');
        $percentage = $this->safe_string($data, 'P');
        $change = null;
        if (($percentage !== null) && ($close !== null)) {
            $change = Precise::string_mul($close, $percentage);
        }
        $ticker = $this->safe_ticker(array(
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'high' => $this->safe_string($data, 'h'),
            'low' => $this->safe_string($data, 'l'),
            'bid' => $this->safe_string($data, 'b'),
            'bidVolume' => null,
            'ask' => $this->safe_string($data, 'a'),
            'askVolume' => null,
            'vwap' => null,
            'open' => $this->safe_string($data, 'o'),
            'close' => $close,
            'last' => $close,
            'previousClose' => null,
            'change' => $change,
            'percentage' => $percentage,
            'average' => null,
            'baseVolume' => $this->safe_string($data, 'v'),
            'quoteVolume' => $this->safe_string($data, 'q'),
            'info' => $message,
        ));
        $client->resolve ($ticker, $messageHash);
    }

    public function watch_trades(string $symbol, ?int $since = null, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * get the list of most recent $trades for a particular $symbol
             * @param {string} $symbol unified $symbol of the $market to fetch $trades for
             * @param {int} [$since] timestamp in ms of the earliest trade to fetch
             * @param {int} [$limit] the maximum amount of $trades to fetch
             * @param {array} [$params] extra parameters specific to the idex api endpoint
             * @return {array[]} a list of ~@link https://docs.ccxt.com/en/latest/manual.html?#public-$trades trade structures~
             */
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $symbol = $market['symbol'];
            $name = 'trades';
            $subscribeObject = array(
                'name' => $name,
                'markets' => [ $market['id'] ],
            );
            $messageHash = $name . ':' . $market['id'];
            $trades = Async\await($this->subscribe($subscribeObject, $messageHash));
            if ($this->newUpdates) {
                $limit = $trades->getLimit ($symbol, $limit);
            }
            return $this->filter_by_since_limit($trades, $since, $limit, 'timestamp', true);
        }) ();
    }

    public function handle_trade(Client $client, $message) {
        $type = $this->safe_string($message, 'type');
        $data = $this->safe_value($message, 'data');
        $marketId = $this->safe_string($data, 'm');
        $messageHash = $type . ':' . $marketId;
        $trade = $this->parse_ws_trade($data);
        $keys = is_array($this->trades) ? array_keys($this->trades) : array();
        $length = count($keys);
        if ($length === 0) {
            $limit = $this->safe_integer($this->options, 'tradesLimit');
            $this->trades = new ArrayCacheBySymbolById ($limit);
        }
        $trades = $this->trades;
        $trades->append ($trade);
        $client->resolve ($trades, $messageHash);
    }

    public function parse_ws_trade($trade, $market = null) {
        // public trades
        // { m => 'DIL-ETH',
        //   i => '897ecae6-4b75-368a-ac00-be555e6ad65f',
        //   p => '0.09696995',
        //   q => '2.00000000',
        //   Q => '0.19393990',
        //   t => 1599504616247,
        //   s => 'buy',
        //   u => 6620 }
        // private trades
        // { i => 'ee253d78-88be-37ed-a61c-a36395c2ce48',
        //   p => '0.09925382',
        //   q => '0.15000000',
        //   Q => '0.01488807',
        //   t => 1599499129369,
        //   s => 'sell',
        //   u => 6603,
        //   f => '0.00030000',
        //   a => 'DIL',
        //   g => '0.00856110',
        //   l => 'maker',
        //   S => 'pending' }
        $marketId = $this->safe_string($trade, 'm');
        $symbol = $this->safe_symbol($marketId);
        $id = $this->safe_string($trade, 'i');
        $price = $this->safe_string($trade, 'p');
        $amount = $this->safe_string($trade, 'q');
        $cost = $this->safe_string($trade, 'Q');
        $timestamp = $this->safe_integer($trade, 't');
        $side = $this->safe_string($trade, 's');
        $fee = array(
            'currency' => $this->safe_string($trade, 'a'),
            'cost' => $this->safe_string($trade, 'f'),
        );
        $takerOrMarker = $this->safe_string($trade, 'l');
        return $this->safe_trade(array(
            'info' => $trade,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'symbol' => $symbol,
            'id' => $id,
            'order' => null,
            'type' => null,
            'takerOrMaker' => $takerOrMarker,
            'side' => $side,
            'price' => $price,
            'amount' => $amount,
            'cost' => $cost,
            'fee' => $fee,
        ));
    }

    public function watch_ohlcv(string $symbol, $timeframe = '1m', ?int $since = null, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $timeframe, $since, $limit, $params) {
            /**
             * watches historical candlestick data containing the open, high, low, and close price, and the volume of a $market
             * @param {string} $symbol unified $symbol of the $market to fetch OHLCV data for
             * @param {string} $timeframe the length of time each candle represents
             * @param {int} [$since] timestamp in ms of the earliest candle to fetch
             * @param {int} [$limit] the maximum amount of candles to fetch
             * @param {array} [$params] extra parameters specific to the idex api endpoint
             * @return {int[][]} A list of candles ordered, open, high, low, close, volume
             */
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $symbol = $market['symbol'];
            $name = 'candles';
            $interval = $this->safe_string($this->timeframes, $timeframe, $timeframe);
            $subscribeObject = array(
                'name' => $name,
                'markets' => [ $market['id'] ],
                'interval' => $interval,
            );
            $messageHash = $name . ':' . $market['id'];
            $ohlcv = Async\await($this->subscribe($subscribeObject, $messageHash));
            if ($this->newUpdates) {
                $limit = $ohlcv->getLimit ($symbol, $limit);
            }
            return $this->filter_by_since_limit($ohlcv, $since, $limit, 0, true);
        }) ();
    }

    public function handle_ohlcv(Client $client, $message) {
        // { $type => 'candles',
        //   $data:
        //    { m => 'DIL-ETH',
        //      t => 1599477340109,
        //      i => '1m',
        //      s => 1599477300000,
        //      e => 1599477360000,
        //      o => '0.09911040',
        //      h => '0.09911040',
        //      l => '0.09911040',
        //      c => '0.09911040',
        //      v => '0.15000000',
        //      n => 1,
        //      u => 6531 } }
        $type = $this->safe_string($message, 'type');
        $data = $this->safe_value($message, 'data');
        $marketId = $this->safe_string($data, 'm');
        $messageHash = $type . ':' . $marketId;
        $parsed = array(
            $this->safe_integer($data, 's'),
            $this->safe_float($data, 'o'),
            $this->safe_float($data, 'h'),
            $this->safe_float($data, 'l'),
            $this->safe_float($data, 'c'),
            $this->safe_float($data, 'v'),
        );
        $symbol = $this->safe_symbol($marketId);
        $interval = $this->safe_string($data, 'i');
        $timeframe = $this->find_timeframe($interval);
        // TODO => move to base class
        $this->ohlcvs[$symbol] = $this->safe_value($this->ohlcvs, $symbol, array());
        $stored = $this->safe_value($this->ohlcvs[$symbol], $timeframe);
        if ($stored === null) {
            $limit = $this->safe_integer($this->options, 'OHLCVLimit', 1000);
            $stored = new ArrayCacheByTimestamp ($limit);
            $this->ohlcvs[$symbol][$timeframe] = $stored;
        }
        $stored->append ($parsed);
        $client->resolve ($stored, $messageHash);
    }

    public function handle_subscribe_message(Client $client, $message) {
        // {
        //   "type" => "subscriptions",
        //   "subscriptions" => array(
        //     {
        //       "name" => "l2orderbook",
        //       "markets" => array(
        //         "DIL-ETH"
        //       )
        //     }
        //   )
        // }
        $subscriptions = $this->safe_value($message, 'subscriptions');
        for ($i = 0; $i < count($subscriptions); $i++) {
            $subscription = $subscriptions[$i];
            $name = $this->safe_string($subscription, 'name');
            if ($name === 'l2orderbook') {
                $markets = $this->safe_value($subscription, 'markets');
                for ($j = 0; $j < count($markets); $j++) {
                    $marketId = $markets[$j];
                    $orderBookSubscriptions = $this->safe_value($this->options, 'orderBookSubscriptions', array());
                    if (!(is_array($orderBookSubscriptions) && array_key_exists($marketId, $orderBookSubscriptions))) {
                        $symbol = $this->safe_symbol($marketId);
                        if (!(is_array($this->orderbooks) && array_key_exists($symbol, $this->orderbooks))) {
                            $orderbook = $this->counted_order_book(array());
                            $orderbook->cache = array();
                            $this->orderbooks[$symbol] = $orderbook;
                        }
                        $this->spawn(array($this, 'fetch_order_book_snapshot'), $client, $symbol);
                    }
                }
                break;
            }
        }
    }

    public function fetch_order_book_snapshot($client, $symbol, $params = array ()) {
        return Async\async(function () use ($client, $symbol, $params) {
            $orderbook = $this->orderbooks[$symbol];
            $market = $this->market($symbol);
            $messageHash = 'l2orderbook' . ':' . $market['id'];
            $subscription = $client->subscriptions[$messageHash];
            if (!$subscription['fetchingOrderBookSnapshot']) {
                $subscription['startTime'] = $this->milliseconds();
            }
            $subscription['fetchingOrderBookSnapshot'] = true;
            $maxAttempts = $this->safe_integer($this->options, 'fetchOrderBookSnapshotMaxAttempts', 10);
            $maxDelay = $this->safe_integer($this->options, 'fetchOrderBookSnapshotMaxDelay', 10000);
            try {
                $limit = $this->safe_integer($subscription, 'limit', 0);
                // 3. Request a level-2 order book $snapshot for the $market from the REST API Order Books endpoint with $limit set to 0.
                $snapshot = Async\await($this->fetch_rest_order_book_safe($symbol, $limit));
                $firstBuffered = $this->safe_value($orderbook->cache, 0);
                $firstData = $this->safe_value($firstBuffered, 'data');
                $firstNonce = $this->safe_integer($firstData, 'u');
                $length = count($orderbook->cache);
                $lastBuffered = $this->safe_value($orderbook->cache, $length - 1);
                $lastData = $this->safe_value($lastBuffered, 'data');
                $lastNonce = $this->safe_integer($lastData, 'u');
                $bothExist = ($firstNonce !== null) && ($lastNonce !== null);
                // ensure the $snapshot is inside the range of our cached messages
                // for example if the $snapshot nonce is 100
                // the first nonce must be less than or equal to 101 and the last nonce must be greater than 101
                if ($bothExist && ($firstNonce <= $snapshot['nonce'] + 1) && ($lastNonce > $snapshot['nonce'])) {
                    $orderbook->reset ($snapshot);
                    for ($i = 0; $i < count($orderbook->cache); $i++) {
                        $message = $orderbook->cache[$i];
                        $data = $this->safe_value($message, 'data');
                        $u = $this->safe_integer($data, 'u');
                        if ($u > $orderbook['nonce']) {
                            // 5. Discard all order book update messages with sequence numbers less than or equal to the $snapshot sequence number.
                            // 6. Apply the remaining buffered order book update messages and any incoming order book update messages to the order book $snapshot->
                            $this->handle_order_book_message($client, $message, $orderbook);
                        }
                    }
                    $subscription['fetchingOrderBookSnapshot'] = false;
                    $client->resolve ($orderbook, $messageHash);
                } else {
                    // 4. If the sequence in the order book $snapshot is less than the sequence of the
                    //    first buffered order book update $message, discard the order book $snapshot and retry step 3.
                    // this will continue to recurse until we have a buffered $message
                    // since updates the order book endpoint depend on order events
                    // so it will eventually throw if there are no orders on a pair
                    $subscription['numAttempts'] = $subscription['numAttempts'] + 1;
                    $timeElapsed = $this->milliseconds() - $subscription['startTime'];
                    $maxAttemptsValid = $subscription['numAttempts'] < $maxAttempts;
                    $timeElapsedValid = $timeElapsed < $maxDelay;
                    if ($maxAttemptsValid && $timeElapsedValid) {
                        $this->delay($this->rateLimit, array($this, 'fetch_order_book_snapshot'), $client, $symbol);
                    } else {
                        $endpart = (!$maxAttemptsValid) ? ' in ' . (string) $maxAttempts . ' attempts' : ' after ' . (string) $maxDelay . ' milliseconds';
                        throw new InvalidNonce($this->id . ' failed to synchronize WebSocket feed with the $snapshot for $symbol ' . $symbol . $endpart);
                    }
                }
            } catch (Exception $e) {
                $subscription['fetchingOrderBookSnapshot'] = false;
                $client->reject ($e, $messageHash);
            }
        }) ();
    }

    public function watch_order_book(string $symbol, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $limit, $params) {
            /**
             * watches information on open orders with bid (buy) and ask (sell) prices, volumes and other data
             * @param {string} $symbol unified $symbol of the $market to fetch the order book for
             * @param {int} [$limit] the maximum amount of order book entries to return
             * @param {array} [$params] extra parameters specific to the idex api endpoint
             * @return {array} A dictionary of {@link https://github.com/ccxt/ccxt/wiki/Manual#order-book-structure order book structures} indexed by $market symbols
             */
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $name = 'l2orderbook';
            $subscribeObject = array(
                'name' => $name,
                'markets' => [ $market['id'] ],
            );
            $messageHash = $name . ':' . $market['id'];
            $subscription = array(
                'fetchingOrderBookSnapshot' => false,
                'numAttempts' => 0,
                'startTime' => null,
            );
            if ($limit === null) {
                $subscription['limit'] = 1000;
            } else {
                $subscription['limit'] = $limit;
            }
            // 1. Connect to the WebSocket API endpoint and subscribe to the L2 Order Book for the target $market->
            $orderbook = Async\await($this->subscribe($subscribeObject, $messageHash, $subscription));
            return $orderbook->limit ();
        }) ();
    }

    public function handle_order_book(Client $client, $message) {
        $data = $this->safe_value($message, 'data');
        $marketId = $this->safe_string($data, 'm');
        $symbol = $this->safe_symbol($marketId);
        $orderbook = $this->orderbooks[$symbol];
        if ($orderbook['nonce'] === null) {
            // 2. Buffer the incoming order book update subscription messages.
            $orderbook->cache[] = $message;
        } else {
            $this->handle_order_book_message($client, $message, $orderbook);
        }
    }

    public function handle_order_book_message(Client $client, $message, $orderbook) {
        // {
        //   "type" => "l2orderbook",
        //   "data" => {
        //     "m" => "DIL-ETH",
        //     "t" => 1600197205037,
        //     "u" => 94116643,
        //     "b" => array(
        //       array(
        //         "0.09662187",
        //         "0.00000000",
        //         0
        //       )
        //     ),
        //     "a" => array()
        //   }
        // }
        $type = $this->safe_string($message, 'type');
        $data = $this->safe_value($message, 'data');
        $marketId = $this->safe_string($data, 'm');
        $messageHash = $type . ':' . $marketId;
        $nonce = $this->safe_integer($data, 'u');
        $timestamp = $this->safe_integer($data, 't');
        $bids = $this->safe_value($data, 'b');
        $asks = $this->safe_value($data, 'a');
        $this->handle_deltas($orderbook['bids'], $bids);
        $this->handle_deltas($orderbook['asks'], $asks);
        $orderbook['nonce'] = $nonce;
        $orderbook['timestamp'] = $timestamp;
        $orderbook['datetime'] = $this->iso8601($timestamp);
        $client->resolve ($orderbook, $messageHash);
    }

    public function handle_delta($bookside, $delta) {
        $price = $this->safe_float($delta, 0);
        $amount = $this->safe_float($delta, 1);
        $count = $this->safe_integer($delta, 2);
        $bookside->store ($price, $amount, $count);
    }

    public function handle_deltas($bookside, $deltas) {
        for ($i = 0; $i < count($deltas); $i++) {
            $this->handle_delta($bookside, $deltas[$i]);
        }
    }

    public function authenticate($params = array ()) {
        return Async\async(function () use ($params) {
            $time = $this->seconds();
            $lastAuthenticatedTime = $this->safe_integer($this->options, 'lastAuthenticatedTime', 0);
            if ($time - $lastAuthenticatedTime > 900) {
                $request = array(
                    'wallet' => $this->walletAddress,
                    'nonce' => $this->uuidv1(),
                );
                $response = Async\await($this->privateGetWsToken (array_merge($request, $params)));
                $this->options['lastAuthenticatedTime'] = $time;
                $this->options['token'] = $this->safe_string($response, 'token');
            }
            return $this->options['token'];
        }) ();
    }

    public function watch_orders(?string $symbol = null, ?int $since = null, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * watches information on multiple $orders made by the user
             * @param {string} $symbol unified market $symbol of the market $orders were made in
             * @param {int} [$since] the earliest time in ms to fetch $orders for
             * @param {int} [$limit] the maximum number of  orde structures to retrieve
             * @param {array} [$params] extra parameters specific to the idex api endpoint
             * @return {array[]} a list of {@link https://github.com/ccxt/ccxt/wiki/Manual#order-structure order structures}
             */
            Async\await($this->load_markets());
            $name = 'orders';
            $subscribeObject = array(
                'name' => $name,
            );
            $messageHash = $name;
            if ($symbol !== null) {
                $symbol = $this->symbol($symbol);
                $marketId = $this->market_id($symbol);
                $subscribeObject['markets'] = array( $marketId );
                $messageHash = $name . ':' . $marketId;
            }
            $orders = Async\await($this->subscribe_private($subscribeObject, $messageHash));
            if ($this->newUpdates) {
                $limit = $orders->getLimit ($symbol, $limit);
            }
            return $this->filter_by_since_limit($orders, $since, $limit, 'timestamp', true);
        }) ();
    }

    public function handle_order(Client $client, $message) {
        // {
        //   "type" => "orders",
        //   "data" => {
        //     "m" => "DIL-ETH",
        //     "i" => "8f75dd30-f12d-11ea-b63c-df3381b4b5b4",
        //     "w" => "0x0AB991497116f7F5532a4c2f4f7B1784488628e1",
        //     "t" => 1599498857138,
        //     "T" => 1599498857092,
        //     "x" => "fill",
        //     "X" => "filled",
        //     "u" => 67695627,
        //     "o" => "limit",
        //     "S" => "buy",
        //     "q" => "0.15000000",
        //     "z" => "0.15000000",
        //     "Z" => "0.01486286",
        //     "v" => "0.09908573",
        //     "p" => "1.00000000",
        //     "f" => "gtc",
        //     "V" => "2",
        //     "F" => array(
        //       {
        //         "i" => "5cdc6d14-bc35-3279-ab5e-40d654ca1523",
        //         "p" => "0.09908577",
        //         "q" => "0.15000000",
        //         "Q" => "0.01486286",
        //         "t" => 1599498857092,
        //         "s" => "sell",
        //         "u" => 6600,
        //         "f" => "0.00030000",
        //         "a" => "DIL",
        //         "g" => "0.00856977",
        //         "l" => "maker",
        //         "S" => "pending"
        //       }
        //     )
        //   }
        // }
        $type = $this->safe_string($message, 'type');
        $order = $this->safe_value($message, 'data');
        $marketId = $this->safe_string($order, 'm');
        $symbol = $this->safe_symbol($marketId);
        $timestamp = $this->safe_integer($order, 't');
        $fills = $this->safe_value($order, 'F', array());
        $trades = array();
        for ($i = 0; $i < count($fills); $i++) {
            $trades[] = $this->parse_ws_trade($fills[$i]);
        }
        $id = $this->safe_string($order, 'i');
        $side = $this->safe_string($order, 's');
        $orderType = $this->safe_string($order, 'o');
        $amount = $this->safe_string($order, 'q');
        $filled = $this->safe_string($order, 'z');
        $average = $this->safe_string($order, 'v');
        $price = $this->safe_string($order, 'price', $average);  // for market $orders
        $rawStatus = $this->safe_string($order, 'X');
        $status = $this->parse_order_status($rawStatus);
        $fee = array(
            'currency' => null,
            'cost' => null,
        );
        $lastTrade = null;
        for ($i = 0; $i < count($trades); $i++) {
            $lastTrade = $trades[$i];
            $fee['currency'] = $lastTrade['fee']['currency'];
            $stringLastTradeFee = $lastTrade['fee']['cost'];
            $fee['cost'] = Precise::string_add($fee['cost'], $stringLastTradeFee);
        }
        $lastTradeTimestamp = $this->safe_integer($lastTrade, 'timestamp');
        $parsedOrder = $this->safe_order(array(
            'info' => $message,
            'id' => $id,
            'clientOrderId' => null,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'lastTradeTimestamp' => $lastTradeTimestamp,
            'symbol' => $symbol,
            'type' => $orderType,
            'side' => $side,
            'price' => $this->parse_number($price),
            'stopPrice' => null,
            'triggerPrice' => null,
            'amount' => $this->parse_number($amount),
            'cost' => null,
            'average' => $this->parse_number($average),
            'filled' => $this->parse_number($filled),
            'remaining' => null,
            'status' => $status,
            'fee' => $fee,
            'trades' => $trades,
        ));
        if ($this->orders === null) {
            $limit = $this->safe_integer($this->options, 'ordersLimit', 1000);
            $this->orders = new ArrayCacheBySymbolById ($limit);
        }
        $orders = $this->orders;
        $orders->append ($parsedOrder);
        $symbolSpecificMessageHash = $type . ':' . $marketId;
        $client->resolve ($orders, $symbolSpecificMessageHash);
        $client->resolve ($orders, $type);
    }

    public function watch_transactions(?string $code = null, ?int $since = null, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($code, $since, $limit, $params) {
            Async\await($this->load_markets());
            $name = 'balances';
            $subscribeObject = array(
                'name' => $name,
            );
            $messageHash = $name;
            if ($code !== null) {
                $messageHash = $name . ':' . $code;
            }
            $transactions = Async\await($this->subscribe_private($subscribeObject, $messageHash));
            if ($this->newUpdates) {
                $limit = $transactions->getLimit ($code, $limit);
            }
            return $this->filter_by_since_limit($transactions, $since, $limit, 'timestamp');
        }) ();
    }

    public function handle_transaction(Client $client, $message) {
        // Update Speed => Real time, updates on any deposit or withdrawal of the wallet
        // { $type => 'balances',
        //   $data:
        //    { w => '0x0AB991497116f7F5532a4c2f4f7B1784488628e1',
        //      a => 'ETH',
        //      q => '0.11198667',
        //      f => '0.11198667',
        //      l => '0.00000000',
        //      d => '0.00' } }
        $type = $this->safe_string($message, 'type');
        $data = $this->safe_value($message, 'data');
        $currencyId = $this->safe_string($data, 'a');
        $messageHash = $type . ':' . $currencyId;
        $code = $this->safe_currency_code($currencyId);
        $address = $this->safe_string($data, 'w');
        $transaction = array(
            'info' => $message,
            'id' => null,
            'currency' => $code,
            'amount' => null,
            'address' => $address,
            'addressTo' => null,
            'addressFrom' => null,
            'tag' => null,
            'tagTo' => null,
            'tagFrom' => null,
            'status' => 'ok',
            'type' => null,
            'updated' => null,
            'txid' => null,
            'timestamp' => null,
            'datetime' => null,
            'fee' => null,
        );
        if (!(is_array($this->transactions) && array_key_exists($code, $this->transactions))) {
            $limit = $this->safe_integer($this->options, 'transactionsLimit', 1000);
            $this->transactions[$code] = new ArrayCache ($limit);
        }
        $transactions = $this->transactions[$code];
        $transactions->append ($transaction);
        $client->resolve ($transactions, $messageHash);
        $client->resolve ($transactions, $type);
    }

    public function handle_message(Client $client, $message) {
        $type = $this->safe_string($message, 'type');
        $methods = array(
            'tickers' => array($this, 'handle_ticker'),
            'trades' => array($this, 'handle_trade'),
            'subscriptions' => array($this, 'handle_subscribe_message'),
            'candles' => array($this, 'handle_ohlcv'),
            'l2orderbook' => array($this, 'handle_order_book'),
            'balances' => array($this, 'handle_transaction'),
            'orders' => array($this, 'handle_order'),
        );
        if (is_array($methods) && array_key_exists($type, $methods)) {
            $method = $methods[$type];
            $method($client, $message);
        }
    }
}
