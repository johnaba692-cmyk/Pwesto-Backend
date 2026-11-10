<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Pwesto_assistant extends Controller
{
    private $session;

    public function __construct()
    {
        parent::__construct();
        $this->session = $this->call->library('session');
    }

    public function index()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method === 'GET') {
            $this->ensure_csrf_token();
            $this->respond([
                'csrf_token' => $this->session->userdata('pwesto_csrf'),
                'configured' => (bool) (getenv('GEMINI_API_KEY') ?: ''),
            ]);
        }
        if ($method !== 'POST') {
            header('Allow: GET, POST');
            $this->respond(['error' => 'Method not allowed.'], 405);
        }

        $this->verify_csrf_token();
        $body = $this->json_body();
        $message = isset($body['message']) && is_string($body['message']) ? trim($body['message']) : '';
        if ($message === '' || strlen($message) > 12000) {
            $this->respond(['error' => 'Enter a message up to 3,000 characters long.'], 422);
        }

        $history = $this->validated_history($body['history'] ?? []);
        $this->enforce_rate_limit();
        $projectAnswer = $this->assistant_project_answer($message);
        if ($projectAnswer !== null) {
            $this->respond(['answer' => $projectAnswer]);
        }
        $catalogRows = $this->assistant_catalog_rows();
        $budgetAnswer = $this->assistant_budget_answer($message, $catalogRows);
        if ($budgetAnswer !== null) {
            $this->respond(['answer' => $budgetAnswer]);
        }
        $catalogContext = $this->assistant_catalog_context($message, $catalogRows);
        $apiKey = trim((string) (getenv('GEMINI_API_KEY') ?: ''));
        if ($apiKey === '') {
            $this->respond([
                'error' => $this->assistant_is_filipino($message)
                    ? 'Hindi naka-configure ang AI assistant ngayon. Subukan ulit mamaya.'
                    : 'The AI assistant is not configured right now. Please try again later.',
            ], 503);
        }
        if (!function_exists('curl_init')) {
            error_log('Pwesto Gemini assistant requires the PHP cURL extension.');
            $this->respond([
                'error' => $this->assistant_is_filipino($message)
                    ? 'Hindi makakonekta ang AI assistant ngayon. Pakisubukan ulit mamaya.'
                    : 'The assistant is temporarily unavailable. Please try again later.',
            ], 503);
        }

        $contents = [];
        foreach ($history as $item) {
            $contents[] = [
                'role' => $item['role'],
                'parts' => [['text' => $item['text']]],
            ];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        $payload = [
            'systemInstruction' => [
                'parts' => [[
                    'text' => $this->assistant_instructions() . "\n\n" . $catalogContext,
                ]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 1200,
                'temperature' => 0.6,
            ],
        ];

        $result = null;
        $lastStatus = 0;
        $lastCurlError = '';
        foreach (['gemini-3.8-flash', 'gemini-3.6-flash', 'gemini-3.5-flash'] as $model) {
            $handle = curl_init(
                'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent'
            );
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $apiKey,
                ],
            ]);
            $responseBody = curl_exec($handle);
            $lastCurlError = curl_error($handle);
            $lastStatus = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);

            if ($responseBody === false) {
                continue;
            }
            $candidateResult = json_decode($responseBody, true);
            if ($lastStatus >= 200 && $lastStatus < 300 && is_array($candidateResult)) {
                $result = $candidateResult;
                break;
            }
            if (in_array($lastStatus, [400, 401, 403], true)) {
                break;
            }
        }

        if ($result === null) {
            error_log(
                'Pwesto Gemini request failed after model fallbacks (HTTP '
                . $lastStatus . ($lastCurlError !== '' ? ', ' . $lastCurlError : '') . ').'
            );
            $this->respond([
                'error' => $this->assistant_is_filipino($message)
                    ? 'Pansamantalang abala ang AI ngayon. Subukan ulit mamaya; kung tungkol ito sa pag-order, rent, o requirements, itanong ang eksaktong hakbang.'
                    : 'The assistant is temporarily busy. Please try again later.',
            ], 503);
        }

        $parts = $result['candidates'][0]['content']['parts'] ?? [];
        $answer = '';
        foreach ($parts as $part) {
            if (isset($part['text']) && is_string($part['text'])) {
                $answer .= $part['text'];
            }
        }
        $answer = trim($answer);
        if ($answer === '') {
            $this->respond([
                'error' => $this->assistant_is_filipino($message)
                    ? 'Walang nabuong sagot ang assistant. Pakiulit o linawin ang tanong.'
                    : 'The assistant did not return an answer. Please rephrase your question and try again.',
            ], 502);
        }

        $this->respond(['answer' => $answer]);
    }

    private function assistant_instructions()
    {
        return 'You are the Pwesto project assistant. Answer the latest user message directly; do not give a generic stall-recommendation response unless they asked for recommendations. Detect the language of the latest message, not the history. If it is Filipino or Taglish, reply in natural, conversational Filipino/Taglish for the entire answer. Do not switch to English unless the user asks. Understand informal Filipino, omitted words, spelling variations, and common phrasing such as "bat/ bakit", "pano/ paano", "ayaw", "di/hindi", "yung", "may tinda", "saan makakabili", and "mabibili ba". First identify what the user is asking, then answer that exact question in a few complete sentences. If the project context below does not establish a fact, say so plainly instead of guessing. '
            . 'PROJECT FACTS: Pwesto is a local-market web app built with PHP/LavaLust and a React shopping interface. Customers discover stalls and active products, filter/search, save stalls, plan visits, message vendors, and order for pickup or delivery. Checkout supports cash at pickup and Xendit GCash. Vendors manage their stall profile, product catalog, messages, and customer orders. A new rental application requires stall details, a completed application form, barangay cedula, a square 2×2 photo, and verified first-month rent; rent is currently ₱7,000 per month. Administrators review applications, verify documents and rental payment, link vendor accounts to stalls, and use Storage to inspect stall/product photos, application documents, and rental status. Cloudinary stores product images and private vendor documents; Xendit processes GCash checkout and rental payments; Gmail SMTP sends configured account/order emails. '
            . 'For this project running locally, Xendit is in test mode: a successful test transaction is simulated and is not real money. After checkout, the user can return to Pwesto and choose Check payment; an application still needs admin review after the payment and documents are verified. Do not claim that a specific user payment or application is confirmed unless that status is included in the provided context. '
            . 'Use the current public catalog below as the only source for product names, listed prices, stalls, and stock. Search all relevant product rows, not just stall names. Understand common Filipino/English variants, including bangus/milkfish, tilapia, galunggong, hipon/shrimp, gulay/vegetables, prutas/fruits, and baboy/pork. Include the stall, market, listed price, and stock when answering an exact product question. If a matching active product is absent, say so plainly and do not invent one. '
            . 'Rows marked SAMPLE DATA ONLY are demonstrations: their price/stock are illustrative, unconfirmed, and not orderable. Do not present sample numbers as real or advise customers to checkout with them. Distinguish current listed stock from a reservation. '
            . 'For budget questions, compare the budget with listed unit prices and clearly state when figures are sample-only. For any project feature, explain the actual workflow and page/role where it is used. Do not claim access to private documents, accounts, credentials, or live payment status. Never invent project behavior, data, or prices. Keep answers concise, specific, and useful; ask a follow-up only when needed.';
    }

    private function assistant_project_answer($message)
    {
        $normalized = strtolower($message);
        $isFilipino = $this->assistant_is_filipino($message);
        $hasRent = preg_match('/\b(rent|renta|upa|bayad|bayaran|pagbayad|pagbabayad|magbayad|checkout|gcash)\b/i', $normalized);
        $asksAboutApproval = preg_match(
            '/\b(approve|approved|approval|aprub|aprubahan|pending)\b/i',
            $normalized
        );
        if ($hasRent && $asksAboutApproval) {
            return $isFilipino
                ? 'Hindi awtomatikong maaaprubahan ang stall pagkatapos magbayad. Isumite muna ang stall details, application form, cedula, at 2×2 photo; bayaran ang unang buwan sa Xendit, saka pindutin ang “Check payment” sa Pwesto. Kapag kumpirmado na ang bayad, rerepasuhin pa rin ng admin ang requirements bago mag-apruba. Tandaan: test mode ang checkout ngayon, kaya simulated lang ito at hindi totoong bayad.'
                : 'Paying does not automatically approve a stall. Submit the stall details, application form, cedula, and 2×2 photo; pay the first month through Xendit, then choose “Check payment” in Pwesto. The admin still reviews the requirements before approving. The current test-mode checkout is simulated, not a real payment.';
        }

        if ($hasRent && preg_match('/\b(ano|magkano|how much|price|presyo|fee|monthly|buwan|bayad|bayaran|pagbayad|pagbabayad|pay|magbayad)\b/i', $normalized)) {
            return $isFilipino
                ? '₱7,000 kada buwan ang unang stall rent. Sa vendor dashboard, isumite ang stall application kasama ang kumpletong requirements, saka pindutin ang “Pay ₱7,000 monthly” at tapusin ang Xendit checkout. Pagbalik sa Pwesto, piliin ang “Check payment”; saka pa rerepasuhin ng admin ang application. Test mode ito kaya simulated, hindi totoong bayad.'
                : 'The initial stall rent is ₱7,000 per month. Submit the stall application and required documents in the vendor dashboard, choose “Pay ₱7,000 monthly,” and complete the Xendit checkout. Back in Pwesto, choose “Check payment”; the admin reviews the application afterward. Test mode is simulated, not a real payment.';
        }

        $asksAboutRequirements = preg_match(
            '/\b(requirement|requirements|application form|cedula|2x2|upload|ipasa|magpasa|pasa|papeles|dokumento|document)\b/i',
            $normalized
        );
        if ($asksAboutRequirements) {
            return $isFilipino
                ? 'Sa vendor dashboard, isumite ang stall rental request kasama ang completed application form, barangay cedula, at square na 2×2 photo. Dapat PDF, JPEG, o PNG ang form at cedula; JPEG o PNG ang photo. Hanggang 2 MB bawat file, at dapat hindi bababa sa 300×300 pixels ang photo. Pagkatapos, magbayad ng unang buwang renta sa Xendit bago ang admin review.'
                : 'In the vendor dashboard, submit the stall rental request with the completed application form, barangay cedula, and a square 2×2 photo. The form and cedula can be PDF, JPEG, or PNG; the photo must be JPEG or PNG. Each file must be at most 2 MB, and the photo at least 300×300 pixels. Pay the first month through Xendit before admin review.';
        }

        if (preg_match('/\b(product|produkto|paninda|tinda)\b/i', $normalized)
            && preg_match('/\b(add|dagdag|idagdag|magdagdag|maglagay|ilagay|update|edit|baguhin|palitan)\b/i', $normalized)) {
            return $isFilipino
                ? 'Kung vendor ka, buksan ang dashboard at piliin ang “Add product” para gumawa ng listing o “Products” para pamahalaan ang mga paninda. Piliin ang stall, ilagay ang pangalan, presyo, available na dami, at optional na detalye o larawan, saka pindutin ang “Add product” o “Save changes”.'
                : 'As a vendor, open the dashboard and choose “Add product” to create a listing or “Products” to manage existing listings. Select a stall, enter the name, price, available quantity, and any optional description or photo, then choose “Add product” or “Save changes”.';
        }

        $asksHowToOrder = preg_match(
            '/\b(paano|pano|how|umorder|makapagorder|makapag-order|mag[- ]?order|makaorder|maka-order|mag-checkout|checkout)\b/i',
            $normalized
        );
        $asksAboutBudget = preg_match(
            '/\b(ilang|mabibili|mabili|bilhin|bibili|budget|kasya|afford|how much)\b|(?:₱|PHP\s*)\d+/i',
            $normalized
        );
        if ($asksHowToOrder && !$asksAboutBudget) {
            return $isFilipino
                ? 'Para mag-order, pumili ng aktibong product listing at idagdag ito sa cart, pagkatapos mag-checkout para sa pickup o delivery. Maaari kang pumili ng cash at pickup o Xendit GCash. Demo/sample products ay hindi puwedeng i-order. Kung ayaw pa rin, sabihin ang eksaktong error na lumalabas.'
                : 'Choose an active product listing, add it to your cart, and check out for pickup or delivery. Payment options include cash at pickup or Xendit GCash. Demo/sample products cannot be ordered. If checkout still fails, share the exact error shown.';
        }

        return null;
    }

    private function assistant_is_filipino($message)
    {
        return preg_match(
            '/\b(ako|alin|ang|ano|anu|atin|ayaw|ba|bakit|balita|bat|bale|bilhin|bili|buhay|dahil|di|dito|doon|filipino|gusto|hindi|ilang|ito|iyan|iyon|kami|kayo|kailangan|kasya|kumusta|lang|magkano|maki|makabili|mabili|mabibili|man|may|meron|mo|mga|na|naman|namin|natin|nito|niya|n\'yo|o|oo|pa|paano|pano|para|pero|po|pwede|puwede|saan|sa|sakin|siya|sila|tagalog|taglish|tayo|tinda|tungkol|wala|yung)\b/iu',
            $message
        ) === 1;
    }

    private function assistant_catalog_rows()
    {
        require_once APP_DIR . 'libraries' . DIRECTORY_SEPARATOR . 'Pwesto_store.php';
        try {
            return (new Pwesto_store())->assistantCatalog();
        } catch (Throwable $error) {
            error_log('Pwesto assistant catalog lookup failed: ' . $error->getMessage());
            $this->respond(['error' => 'The market catalog could not be loaded for the assistant. Please try again later.'], 503);
        }
    }

    private function assistant_budget_answer($message, array $rows)
    {
        if (!preg_match('/\b(ilang|mabibili|mabili|bilhin|bibili|bilhin|bili|bumili|buy|how many|how much|kasya|budget|afford|can i get)\b/i', $message)) {
            return null;
        }
        if (preg_match('/(?:₱|PHP\s*)\s*(\d[\d,]*(?:\.\d{1,2})?)/i', $message, $budgetMatch)) {
            $budget = $budgetMatch[1];
        } elseif (preg_match_all('/\b(\d[\d,]*(?:\.\d{1,2})?)\b/', $message, $budgetMatches)) {
            $budget = end($budgetMatches[1]);
        } else {
            return null;
        }
        $budgetCents = (int) round((float) str_replace(',', '', $budget) * 100);
        if ($budgetCents < 1 || $budgetCents > 1000000000) {
            return null;
        }

        $categoryTerms = [
            'Seafood' => ['seafood', 'fish', 'isda', 'bangus', 'milkfish', 'hipon', 'shrimp', 'prawn', 'tilapia', 'galunggong'],
            'Meat' => ['meat', 'pork', 'baboy', 'beef', 'chicken', 'manok', 'poultry'],
            'Fruits' => ['fruit', 'fruits', 'prutas'],
            'Vegetables' => ['vegetable', 'vegetables', 'gulay'],
            'Pantry' => ['pantry', 'grocery', 'groceries'],
            'Flowers' => ['flower', 'flowers', 'bulaklak'],
        ];
        $normalized = strtolower($message);
        $category = null;
        foreach ($categoryTerms as $name => $terms) {
            foreach ($terms as $term) {
                if (preg_match('/\b' . preg_quote($term, '/') . '\b/i', $normalized)) {
                    $category = $name;
                    break 2;
                }
            }
        }
        if ($category === null) {
            return null;
        }

        $categoryItems = [];
        foreach ($rows as $row) {
            if (($row['category'] ?? '') !== $category || !is_numeric($row['price_cents']) || (int) $row['price_cents'] < 1) {
                continue;
            }
            $categoryItems[] = $row;
        }

        $items = [];
        foreach ($categoryItems as $row) {
            $productName = (string) $row['product_name'];
            $variants = [$productName];
            if (preg_match('/^(.+?)\s*\(([^)]+)\)/', $productName, $nameMatch)) {
                $variants[] = trim($nameMatch[1]);
                $variants[] = trim($nameMatch[2]);
            }
            foreach ($variants as $variant) {
                if ($variant !== '' && preg_match('/\b' . preg_quote($variant, '/') . '\b/i', $message)) {
                    $items[] = $row;
                    break;
                }
            }
        }
        if (!$items) {
            $items = $categoryItems;
        }
        if (!$items) {
            return 'Wala akong nakikitang kasalukuyang product listing sa ' . strtolower($category) . ' category, kaya hindi ko makukuwenta ang mabibili sa budget na iyan.';
        }

        $results = [];
        $hasSampleResult = false;
        foreach ($items as $item) {
            $price = (int) $item['price_cents'];
            $stock = max(0, (int) $item['stock']);
            $quantity = min($stock, intdiv($budgetCents, $price));
            if ($quantity < 1) {
                continue;
            }
            $isSample = empty($item['has_owner'])
                || stripos((string) ($item['product_description'] ?? ''), 'Sample dataset:') !== false;
            $hasSampleResult = $hasSampleResult || $isSample;
            $total = $quantity * $price;
            $results[] = $item['product_name'] . ($isSample ? ' (SAMPLE ONLY; hindi orderable)' : '')
                . ' sa ' . $item['vendor_name'] . ': hanggang ' . $quantity
                . ' unit(s) sa ₱' . number_format($price / 100, 2) . ' bawat isa (₱'
                . number_format($total / 100, 2) . ' total; ₱' . number_format(($budgetCents - $total) / 100, 2)
                . ' matitira; listed stock ' . $stock . ').';
        }
        if (!$results) {
            return 'Sa kasalukuyang listed prices ng ' . strtolower($category) . ', walang item na pasok sa budget na ₱'
                . number_format($budgetCents / 100, 2) . '. Hindi ito nangangahulugang real-time availability.';
        }

        $answer = 'Batay sa listed prices at whole units, sa budget na ₱' . number_format($budgetCents / 100, 2) . ":\n"
            . implode("\n", $results);
        if ($hasSampleResult) {
            $answer .= "\nPaalala: demo lamang ang presyo/stock ng mga sample item, hindi kumpirmado ng vendor at hindi puwedeng i-order.";
        }
        return $answer;
    }

    private function assistant_catalog_context($message, array $rows)
    {

        if (!$rows) {
            return 'LIVE PUBLIC CATALOG: There are currently no approved stalls in the database.';
        }

        $vendors = [];
        foreach ($rows as $row) {
            $key = (string) ($row['vendor_id'] ?? $row['stall_code'] ?? $row['vendor_name']);
            if (!isset($vendors[$key])) {
                $vendors[$key] = [
                    'id' => $key,
                    'name' => (string) $row['vendor_name'],
                    'vendor_name' => (string) $row['vendor_name'],
                    'stall_code' => (string) ($row['stall_code'] ?? ''),
                    'category' => (string) $row['category'],
                    'market' => (string) $row['market'],
                    'specialty' => (string) $row['specialty'],
                    'has_owner' => (bool) $row['has_owner'],
                    'products' => [],
                ];
            }
            if (is_string($row['product_name']) && $row['product_name'] !== '') {
                $vendors[$key]['products'][] = [
                    'name' => $row['product_name'],
                    'description' => (string) ($row['product_description'] ?? ''),
                    'price_cents' => $row['price_cents'],
                    'stock' => $row['stock'],
                ];
            }
        }

        $terms = preg_split('/[^\p{L}\p{N}]+/u', strtolower($message), -1, PREG_SPLIT_NO_EMPTY);
        $stopWords = ['about', 'ako', 'ang', 'ano', 'anong', 'anu', 'and', 'any', 'available', 'ba', 'bang', 'can', 'do', 'does', 'eh', 'for', 'hanap', 'hanapin', 'have', 'how', 'ilan', 'ilang', 'is', 'item', 'kami', 'kayo', 'ko', 'looking', 'magkano', 'mabili', 'mabibili', 'bilhin', 'bibili', 'bumili', 'may', 'me', 'meron', 'na', 'naghahanap', 'nagtitinda', 'nga', 'ng', 'nito', 'naman', 'namin', 'natin', 'niyo', 'nyo', 'nung', 'of', 'pa', 'please', 'po', 'presyo', 'product', 'products', 'rin', 'din', 'sa', 'si', 'stock', 'the', 'to', 'tinda', 'titinda', 'vendor', 'where', 'with', 'yung', 'buy', 'bili', 'budget', 'price', 'kilo', 'kg'];
        $terms = array_values(array_unique(array_filter($terms, function ($term) use ($stopWords) {
            return strlen($term) > 1 && !ctype_digit($term) && !in_array($term, $stopWords, true);
        })));
        $aliases = [
            'bangus' => ['seafood', 'milkfish'],
            'milkfish' => ['seafood', 'bangus'],
            'hipon' => ['shrimp', 'prawn', 'seafood'],
            'shrimp' => ['hipon', 'prawn', 'seafood'],
            'prawn' => ['hipon', 'shrimp', 'seafood'],
            'tilapia' => ['fish', 'seafood', 'isda'],
            'galunggong' => ['fish', 'seafood', 'isda'],
            'pork' => ['meat'],
            'baboy' => ['pork', 'meat'],
            'baka' => ['beef', 'meat'],
            'beef' => ['baka', 'meat'],
            'chop' => ['pork', 'meat'],
            'manok' => ['chicken', 'meat'],
            'chicken' => ['manok', 'meat'],
            'isda' => ['fish', 'seafood'],
            'fish' => ['isda', 'seafood'],
            'gulay' => ['vegetable', 'produce'],
            'vegetable' => ['gulay', 'produce'],
            'saging' => ['banana', 'lakatan'],
            'banana' => ['saging', 'lakatan'],
            'lakatan' => ['banana', 'saging'],
            'pakwan' => ['watermelon'],
            'watermelon' => ['pakwan'],
            'abokado' => ['avocado'],
            'avocado' => ['abokado'],
            'talong' => ['eggplant'],
            'eggplant' => ['talong'],
            'bawang' => ['garlic'],
            'garlic' => ['bawang'],
            'kamatis' => ['tomato', 'tomatoes'],
            'tomato' => ['kamatis'],
            'tomatoes' => ['kamatis'],
            'kalabasa' => ['squash'],
            'squash' => ['kalabasa'],
            'kilo' => ['kg'],
        ];
        $specificAliases = [
            'bangus' => ['milkfish'],
            'milkfish' => ['bangus'],
            'hipon' => ['shrimp', 'prawn'],
            'shrimp' => ['hipon', 'prawn'],
            'prawn' => ['hipon', 'shrimp'],
            'baboy' => ['pork'],
            'manok' => ['chicken'],
            'chicken' => ['manok'],
            'saging' => ['banana', 'lakatan'],
            'banana' => ['saging', 'lakatan'],
            'lakatan' => ['banana', 'saging'],
            'pakwan' => ['watermelon'],
            'watermelon' => ['pakwan'],
            'abokado' => ['avocado'],
            'avocado' => ['abokado'],
            'talong' => ['eggplant'],
            'eggplant' => ['talong'],
            'bawang' => ['garlic'],
            'garlic' => ['bawang'],
            'kamatis' => ['tomato', 'tomatoes'],
            'tomato' => ['kamatis'],
            'tomatoes' => ['kamatis'],
            'kalabasa' => ['squash'],
            'squash' => ['kalabasa'],
        ];
        $expandedTerms = $terms;
        foreach ($terms as $term) {
            if (isset($aliases[$term])) {
                $expandedTerms = array_merge($expandedTerms, $aliases[$term]);
            }
        }
        $expandedTerms = array_values(array_unique($expandedTerms));
        $categoryTerms = ['seafood', 'fish', 'isda', 'meat', 'fruit', 'fruits', 'prutas', 'vegetable', 'vegetables', 'gulay', 'pantry', 'flowers', 'flower'];
        $expandedTerms = array_values(array_diff($expandedTerms, ['stall', 'stalls', 'market', 'markets', 'vendor', 'vendors']));
        $productTerms = array_values(array_diff($terms, array_merge(['stall', 'stalls', 'market', 'markets', 'vendor', 'vendors'], $categoryTerms)));
        $isCatalogQuestion = preg_match(
            '/\b(may|meron|tinda|nagtitinda|stock|available|hanap|naghahanap|find|buy|bili|bilhin|mabibili|product|products|item|stall|stalls|vendor|vendors|market|seafood|meat|produce|flowers|fruit|fruits|bangus|milkfish|hipon|shrimp|prawn|tilapia|galunggong|pork|baboy|beef|baka|chop|manok|chicken|isda|fish|gulay|prutas|vegetable|saging|banana|lakatan|pakwan|watermelon|abokado|avocado|talong|eggplant|bawang|garlic|kamatis|tomato|tomatoes|kalabasa|squash|presyo|magkano|price|kilo|kg)\b/i',
            $message
        ) === 1;

        $candidates = [];
        $exactProducts = [];
        foreach ($vendors as $vendor) {
            $stallText = strtolower(implode(' ', [
                $vendor['name'],
                $vendor['category'],
                $vendor['market'],
                $vendor['specialty'],
            ]));
            $categoryText = strtolower($vendor['category'] . ' ' . $vendor['specialty']);
            $productCount = 0;
            foreach ($vendor['products'] as $product) {
                $productText = strtolower($product['name'] . ' ' . $product['description']);
                $score = 0;
                $matchedProductTerms = 0;
                foreach ($productTerms as $term) {
                    $termVariants = array_merge([$term], $specificAliases[$term] ?? []);
                    foreach ($termVariants as $variant) {
                        if (strpos($productText, $variant) !== false) {
                            $matchedProductTerms++;
                            break;
                        }
                    }
                }
                foreach ($expandedTerms as $term) {
                    if (strpos($productText, $term) !== false) {
                        $score += 3;
                    } elseif (in_array($term, $categoryTerms, true)) {
                        if (strpos($categoryText, $term) !== false) {
                            $score += 2;
                        }
                    } elseif (strpos($stallText, $term) !== false) {
                        $score++;
                    }
                }
                $line = $this->format_assistant_product($vendor, $product);
                $isExactMatch = count($productTerms) > 0 && $matchedProductTerms === count($productTerms);
                if ($isExactMatch) {
                    $exactProducts[] = $line;
                }
                $candidates[] = [
                    'score' => $score,
                    'line' => $line,
                    'product' => true,
                ];
                $productCount++;
            }
            if ($productCount === 0) {
                $score = 0;
                foreach ($expandedTerms as $term) {
                    if (strpos($stallText, $term) !== false) {
                        $score++;
                    }
                }
                $candidates[] = [
                    'score' => $score,
                    'line' => $this->format_assistant_vendor($vendor),
                    'product' => false,
                ];
            }
        }

        usort($candidates, function ($left, $right) {
            if ($left['score'] === $right['score']) {
                return (int) $right['product'] - (int) $left['product'];
            }
            return $right['score'] <=> $left['score'];
        });

        $selected = [];
        foreach ($candidates as $candidate) {
            if (count($selected) >= 35) {
                break;
            }
            if ($candidate['score'] > 0) {
                $selected[] = $candidate['line'];
            }
        }
        if (!$selected) {
            foreach (array_slice($candidates, 0, 25) as $candidate) {
                $selected[] = $candidate['line'];
            }
        }

        $context = "LIVE PUBLIC CATALOG FROM THE DATABASE (approved stalls and active products; prices are PHP):\n";
        if ($isCatalogQuestion && $productTerms) {
            $context .= $exactProducts
                ? "Exact active product matches for this question:\n" . implode("\n", array_slice($exactProducts, 0, 15)) . "\n"
                : "No active product name or description exactly matches the specific product terms in this question. Do not claim that a product is listed; use relevant stalls below only as suggestions and explain that their current listing does not confirm the requested item.\n";
        }
        return $context
            . "Relevant stall and product records:\n" . implode("\n", $selected)
            . "\nUse only these records for current listing and availability claims. Product stock is the current listed stock count, not a reservation.";
    }

    private function format_assistant_vendor(array $vendor)
    {
        return '- Stall: ' . $vendor['stall_code'] . ' | ' . $vendor['name'] . ' | Category: ' . $vendor['category']
            . ' | Market: ' . $vendor['market'] . ' | Specialty: ' . $vendor['specialty']
            . (!$vendor['has_owner'] ? ' | Demo stall; no registered vendor' : '')
            . ' | Active products: none listed';
    }

    private function format_assistant_product(array $vendor, array $product)
    {
        $line = '- Stall: ' . $vendor['stall_code'] . ' | ' . $vendor['name'] . ' | Category: ' . $vendor['category']
            . ' | Market: ' . $vendor['market'] . ' | Specialty: ' . $vendor['specialty']
            . ' | Product: ' . $product['name']
            . (!$vendor['has_owner'] || strpos((string) $product['description'], 'Sample dataset:') !== false
                ? ' | SAMPLE DATA ONLY; price and stock are illustrative, unverified, and not orderable'
                : '');
        if ($product['description'] !== '') {
            $line .= ' | Description: ' . $product['description'];
        }
        if (is_numeric($product['price_cents'])) {
            $line .= ' | Price: PHP ' . number_format((int) $product['price_cents'] / 100, 2);
        }
        if (is_numeric($product['stock'])) {
            $line .= ' | Stock: ' . (int) $product['stock'];
        }
        return $line;
    }

    private function validated_history($history)
    {
        if (!is_array($history) || count($history) > 4) {
            $this->respond(['error' => 'Conversation history is invalid. Start a new chat and try again.'], 422);
        }
        $validated = [];
        foreach ($history as $item) {
            if (!is_array($item)
                || !isset($item['role'], $item['text'])
                || !in_array($item['role'], ['user', 'model'], true)
                || !is_string($item['text'])
                || trim($item['text']) === ''
                || strlen($item['text']) > 4000) {
                $this->respond(['error' => 'Conversation history is invalid. Start a new chat and try again.'], 422);
            }
            $validated[] = ['role' => $item['role'], 'text' => trim($item['text'])];
        }
        return $validated;
    }

    private function enforce_rate_limit()
    {
        $now = time();
        $requests = $this->session->userdata('pwesto_assistant_requests');
        $recent = [];
        if (is_array($requests)) {
            foreach ($requests as $timestamp) {
                if (is_int($timestamp) && $timestamp > $now - 600) {
                    $recent[] = $timestamp;
                }
            }
        }
        if (count($recent) >= 10) {
            header('Retry-After: 600');
            $this->respond(['error' => 'You have reached the chat limit for now. Please try again in a few minutes.'], 429);
        }
        $recent[] = $now;
        $this->session->set_userdata('pwesto_assistant_requests', $recent);
    }

    private function ensure_csrf_token()
    {
        if (!$this->session->has_userdata('pwesto_csrf')) {
            $this->session->set_userdata('pwesto_csrf', bin2hex(random_bytes(32)));
        }
    }

    private function verify_csrf_token()
    {
        $expected = $this->session->userdata('pwesto_csrf');
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($expected) || !is_string($provided) || $provided === '' || !hash_equals($expected, $provided)) {
            $this->respond(['error' => 'Your session has expired. Refresh the page and try again.'], 403);
        }
    }

    private function json_body()
    {
        $raw = file_get_contents('php://input', false, null, 0, 40961);
        if ($raw !== false && strlen($raw) > 40960) {
            $this->respond(['error' => 'Request body is too large.'], 413);
        }
        $body = json_decode($raw === false ? '' : $raw, true);
        if (!is_array($body)) {
            $this->respond(['error' => 'A valid JSON request body is required.'], 400);
        }
        return $body;
    }

    private function respond($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
