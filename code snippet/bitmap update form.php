// --- START: BITMAP UPDATE FORM SNIPPET (with Session Logic) ---

// 1. Register shortcode for the update form
add_shortcode('bitmap_update_form', 'bitmap_gallery_render_update_form_v2');

function bitmap_gallery_render_update_form_v2() {
    // Start a session to securely store the verified state
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // --- Session Check Logic ---
    $initial_state = ['is_verified' => false];
    $session_timeout = 4 * 3600; // 4 hours in seconds

    if (
        !empty($_SESSION['final_verification_passed']) &&
        !empty($_SESSION['verification_timestamp']) &&
        (time() - $_SESSION['verification_timestamp'] < $session_timeout) &&
        !empty($_SESSION['verified_bitmap_id']) &&
        !empty($_SESSION['verified_bitmap_owner_address'])
    ) {
        // Session is valid and not expired, fetch current data
        global $wpdb;
        $config_table = $wpdb->prefix . 'bitmap_configs';
        $bitmap_id = $_SESSION['verified_bitmap_id'];
        
        $current_config = $wpdb->get_row($wpdb->prepare(
            "SELECT image_url, iframe_url FROM $config_table WHERE bitmap_id = %d",
            $bitmap_id
        ));

        $initial_state = [
            'is_verified' => true,
            'bitmap_id' => $bitmap_id,
            'wallet_address' => $_SESSION['verified_bitmap_owner_address'],
            'image_url' => $current_config ? $current_config->image_url : '',
            'iframe_url' => $current_config ? $current_config->iframe_url : '',
        ];
    }
    // --- End Session Check ---

    $nonce = wp_create_nonce('wp_rest');
    $random_code = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
    
    ob_start();
    ?>
    <style>
        .bitmap-form-container { max-width: 600px; margin: 20px auto; padding: 25px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif; }
        .bitmap-form-container h2 { text-align: center; color: #333; margin-top: 0; }
        .form-step { margin-bottom: 20px; }
        .form-step label { display: block; font-weight: bold; margin-bottom: 8px; color: #555; }
        .form-step .input-hint { font-size: 12px; color: #777; margin-top: -5px; margin-bottom: 10px; }
        .form-step input[type="text"], .form-step input[type="url"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-step input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
        .form-step button { background: #f7931a; color: #fff; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; font-size: 16px; transition: background 0.3s; }
        #submit-btn { margin-top: 20px; }
        .form-step button:disabled { background: #ccc; cursor: not-allowed; }
        .status-box { margin-top: 15px; padding: 12px; border-radius: 4px; text-align: center; font-weight: bold; word-wrap: break-word; }
        .status-info { background: #e0e0e0; color: #444; }
        .status-success { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .verification-code { font-family: monospace; background: #fff; padding: 5px 10px; border: 1px dashed #f7931a; color: #d67d00; user-select: all; }
        .image-preview-box { width: 128px; height: 128px; border: 2px dashed #ccc; background-color: #f0f0f0; margin-top: 10px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 14px; background-size: contain; background-repeat: no-repeat; background-position: center; }
        .me-bio-guide-img { max-width: 100%; height: auto; margin-top: 15px; border-radius: 4px; border: 1px solid #ddd; display: block; }
    </style>

    <div id="bitmap-form-container" class="bitmap-form-container">
        <h2>Update Your Bitmap</h2>

        <div class="form-step" id="step1">
            <label for="bitmap-id">1. Your Bitmap Number</label>
            <input type="text" id="bitmap-id" placeholder="e.g., 454545">
            <p class="input-hint">Currently, only Bitmaps with OCI (on-chain index), i.e., numbers below 845,844, are supported.</p>
            <div id="step1-status" class="status-box status-info" style="display: none;"></div>
        </div>

        <div class="form-step" id="step2" style="display: none;">
            <label for="wallet-address">2. Your Bitcoin Wallet Address</label>
            <input type="text" id="wallet-address" placeholder="bc1p..." readonly>
            <p style="font-size: 14px; color: #666;">To verify you own this wallet, please copy the code below, paste it into your <a id="me-profile-link" href="https://magiceden.io/settings" target="_blank" rel="noopener noreferrer">Magic Eden Profile Bio</a>, and make sure your wallet is set to public, then click Verify.</p>
            <p>Your unique code: <strong id="verification-code" class="verification-code"><?php echo esc_html($random_code); ?></strong></p>
            <a href="https://cospace.pro/wp-content/uploads/2025/07/set-ME-profile-bio.png" target="_blank" title="Click to view guide">
                <img src="https://cospace.pro/wp-content/uploads/2025/07/set-ME-profile-bio.png" alt="How to set Magic Eden Bio" class="me-bio-guide-img">
            </a>
            <button id="verify-wallet-btn" style="margin-top: 20px;">Verify Magic Eden Bio</button>
            <div id="step2-status" class="status-box status-info" style="display: none;"></div>
        </div>
        
        <div class="form-step" id="step3" style="display: none;">
            <label for="avatar-url">3. Set Your Avatar URL (must be 1:1 ratio)</label>
            <input type="url" id="avatar-url" placeholder="https://example.com/your-avatar.png">
            
            <div id="avatar-preview" class="image-preview-box">Preview</div>
            <div id="avatar-status" class="status-box status-info" style="display: none;"></div>

            <label for="iframe-url" style="margin-top: 15px;">4. Set Your Modal Content URL</label>
             <p class="input-hint">This URL will be the content of the iframe popup that appears when your bitmap avatar is clicked.</p>
            <input type="url" id="iframe-url" placeholder="https://example.com/your-page.html">
            
            <button id="submit-btn" disabled>Confirm & Submit</button> 
            <div id="step3-status" class="status-box status-info" style="display: none;"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Get all DOM elements ---
        const step1Div = document.getElementById('step1');
        const step2Div = document.getElementById('step2');
        const step3Div = document.getElementById('step3');
        const bitmapIdInput = document.getElementById('bitmap-id');
        const walletAddressInput = document.getElementById('wallet-address');
        const avatarUrlInput = document.getElementById('avatar-url');
        const iframeUrlInput = document.getElementById('iframe-url');
        const step1Status = document.getElementById('step1-status');
        const step2Status = document.getElementById('step2-status');
        const step3Status = document.getElementById('step3-status');
        const verifyWalletBtn = document.getElementById('verify-wallet-btn');
        const submitBtn = document.getElementById('submit-btn');
        const verificationCodeEl = document.getElementById('verification-code');
        const verificationCode = verificationCodeEl ? verificationCodeEl.textContent : '<?php echo $random_code; ?>';
        const avatarPreview = document.getElementById('avatar-preview');
        const avatarStatus = document.getElementById('avatar-status');
        const meProfileLink = document.getElementById('me-profile-link');
        let ownerAddressFromChain = '';
        
        // --- Initial State from PHP ---
        const initialState = <?php echo json_encode($initial_state); ?>;

        // --- Helper Functions ---
        function updateStatus(element, message, type) {
            if (element) {
                element.innerHTML = message; // Use innerHTML to render the link
                element.className = 'status-box status-' + type;
                element.style.display = 'block';
            }
        }
        function truncateAddress(address) {
            if (address.length < 10) return address;
            return `${address.substring(0, 6)}...${address.substring(address.length - 4)}`;
        }
        function validateAndPreviewImage(url) {
            if (!url) {
                updateStatus(avatarStatus, '', 'info');
                avatarPreview.style.backgroundImage = 'none';
                avatarPreview.textContent = 'Preview';
                submitBtn.disabled = false; // Allow submitting empty URL
                return;
            }
            updateStatus(avatarStatus, 'Checking image...', 'info');
            submitBtn.disabled = true;
            const img = new Image();
            img.crossOrigin = "Anonymous";
            img.onload = function() {
                avatarPreview.style.backgroundImage = `url('${url}')`;
                avatarPreview.textContent = '';
                updateStatus(avatarStatus, 'Image is valid and ready!', 'success');
                submitBtn.disabled = false;
            };
            img.onerror = function() {
                avatarPreview.style.backgroundImage = 'none';
                avatarPreview.textContent = 'Preview';
                const errorMessage = "Image failed to load due to CORS policy. Please use a public image host.";
                updateStatus(avatarStatus, errorMessage, 'error');
                submitBtn.disabled = true;
            };
            img.src = url;
        }

        // --- Main Logic ---
        function initializeForm() {
            if (initialState.is_verified) {
                // User has a valid session, skip to step 3
                step1Div.style.display = 'none';
                step2Div.style.display = 'none';
                step3Div.style.display = 'block';

                // Pre-fill the form
                bitmapIdInput.value = initialState.bitmap_id;
                bitmapIdInput.readOnly = true;
                walletAddressInput.value = initialState.wallet_address;
                avatarUrlInput.value = initialState.image_url;
                iframeUrlInput.value = initialState.iframe_url;
                
                updateStatus(step3Status, 'You have a valid session. You can modify your URLs and submit again.', 'info');

                // Trigger preview for the existing avatar URL
                if(initialState.image_url) {
                    validateAndPreviewImage(initialState.image_url);
                } else {
                    submitBtn.disabled = false;
                }
            } else {
                // No valid session, start from step 1
                step1Div.style.display = 'block';
            }
        }

        // --- Event Listeners ---
        bitmapIdInput.addEventListener('blur', async function() {
            const bitmapId = this.value.trim();
            if (!/^\d+$/.test(bitmapId)) {
                updateStatus(step1Status, 'Please enter a valid number.', 'error');
                return;
            }
            if (parseInt(bitmapId, 10) >= 845844) {
                updateStatus(step1Status, 'Error: Only Bitmaps with a number less than 845,844 are currently supported.', 'error');
                return;
            }
            updateStatus(step1Status, 'Querying Bitmap owner...', 'info');
            const response = await fetch('<?php echo esc_url_raw(rest_url('bitmap-gallery/v1/get-owner')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                body: JSON.stringify({ bitmap_id: bitmapId })
            });
            const data = await response.json();
            if (data.success) {
                ownerAddressFromChain = data.address;
                updateStatus(step1Status, `Success! This Bitmap is held by address ${truncateAddress(ownerAddressFromChain)}.`, 'success');
                walletAddressInput.value = ownerAddressFromChain;
                if (meProfileLink) {
                    meProfileLink.href = `https://magiceden.io/u/${ownerAddressFromChain}`;
                }
                step2Div.style.display = 'block';
            } else {
                updateStatus(step1Status, `Query failed: ${data.message}`, 'error');
                step2Div.style.display = 'none';
            }
        });

        verifyWalletBtn.addEventListener('click', async function() {
            updateStatus(step2Status, 'Verifying Bio via Magic Eden API...', 'info');
            this.disabled = true;
            const response = await fetch('<?php echo esc_url_raw(rest_url('bitmap-gallery/v1/verify-bio')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                body: JSON.stringify({ code: verificationCode })
            });
            const data = await response.json();
            if (data.success) {
                updateStatus(step2Status, 'Verification successful! You\'ve proven ownership of the wallet.', 'success');
                step3Div.style.display = 'block';
            } else {
                updateStatus(step2Status, `Verification failed: ${data.message}`, 'error');
            }
            this.disabled = false;
        });
        
        avatarUrlInput.addEventListener('blur', function() {
            validateAndPreviewImage(this.value);
        });

        submitBtn.addEventListener('click', async function() {
            updateStatus(step3Status, 'Submitting...', 'info');
            this.disabled = true;
            const payload = {
                bitmap_id: bitmapIdInput.value.trim(),
                image_url: avatarUrlInput.value.trim(),
                iframe_url: iframeUrlInput.value.trim()
            };
            const response = await fetch('<?php echo esc_url_raw(rest_url('bitmap-gallery/v1/submit-config')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (data.success) {
                const bitmapId = bitmapIdInput.value.trim();
                const successLink = `/bitmap-billboard/?location=${bitmapId}.bitmap`;
                const successMessage = `Congratulations! Your Bitmap has been updated. <a href="${successLink}" target="_blank">Click here to view it!</a> You can make more changes and submit again.`;
                updateStatus(step3Status, successMessage, 'success');
            } else {
                updateStatus(step3Status, `Submission failed: ${data.message}`, 'error');
            }
            this.disabled = false;
        });

        // --- Run Initialization ---
        initializeForm();
    });
    </script>
    <?php
    return ob_get_clean();
}

// --- API Endpoint Registration ---
add_action('rest_api_init', function() {
    $permission_callback = function($request) {
        $nonce = $request->get_header('X-WP-Nonce');
        if ( ! wp_verify_nonce($nonce, 'wp_rest') ) {
            return new WP_Error('rest_forbidden', 'Nonce is invalid.', ['status' => 403]);
        }
        return true;
    };
    register_rest_route('bitmap-gallery/v1', '/get-owner', [ 'methods' => 'POST', 'callback' => 'bitmap_gallery_api_get_owner_v2', 'permission_callback' => $permission_callback ]);
    register_rest_route('bitmap-gallery/v1', '/verify-bio', [ 'methods' => 'POST', 'callback' => 'bitmap_gallery_api_verify_bio_v2', 'permission_callback' => $permission_callback ]);
    register_rest_route('bitmap-gallery/v1', '/submit-config', [ 'methods' => 'POST', 'callback' => 'bitmap_gallery_api_submit_config_v2', 'permission_callback' => $permission_callback ]);
});

// --- API Callback Functions (Updated for Session Logic) ---

function bitmap_gallery_api_get_owner_v2($request) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    global $wpdb;
    $bitmap_id = intval($request->get_param('bitmap_id'));
    if (empty($bitmap_id)) { return new WP_REST_Response(['success' => false, 'message' => 'Bitmap ID cannot be empty.'], 400); }
    
    $inscriptions_table = $wpdb->prefix . 'bitmap_inscriptions';
    $inscription_id = $wpdb->get_var($wpdb->prepare("SELECT inscription_id FROM $inscriptions_table WHERE bitmap_id = %d", $bitmap_id));

    if (empty($inscription_id)) { return new WP_REST_Response(['success' => false, 'message' => 'Could not find inscription data for this Bitmap.'], 404); }
    
    // IMPORTANT: Replace with your own Cloudflare Worker URL
    $worker_url = 'https://hiro-proxy-f2f1.info4104.workers.dev/' . $inscription_id;
    $response = wp_remote_get($worker_url);
    
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) { return new WP_REST_Response(['success' => false, 'message' => 'Failed to query on-chain data.'], 500); }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($data['address'])) {
        // 【Session】Store both bitmap ID and owner address
        $_SESSION['verified_bitmap_id'] = $bitmap_id;
        $_SESSION['verified_bitmap_owner_address'] = $data['address'];
        // Clear old verification status if starting over
        unset($_SESSION['final_verification_passed']);
        unset($_SESSION['verification_timestamp']);
        return new WP_REST_Response(['success' => true, 'address' => $data['address']], 200);
    }
    return new WP_REST_Response(['success' => false, 'message' => 'Could not parse owner address.'], 500);
}

function bitmap_gallery_api_verify_bio_v2($request) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (empty($_SESSION['verified_bitmap_owner_address'])) { return new WP_REST_Response(['success' => false, 'message' => 'Verification session expired. Please start from step 1.'], 400); }
    
    $wallet_address = $_SESSION['verified_bitmap_owner_address'];
    $code = sanitize_text_field($request->get_param('code'));
    
    if (empty($code)) { return new WP_REST_Response(['success' => false, 'message' => 'Verification code cannot be empty.'], 400); }

    // IMPORTANT: Replace with your own Cloudflare Worker URL
    $worker_url = 'https://magiceden-bio-api.info4104.workers.dev/' . $wallet_address;
    $response = wp_remote_get($worker_url);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) { return new WP_REST_Response(['success' => false, 'message' => 'Failed to query Magic Eden API.'], 500); }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($data['bio']) && trim($data['bio']) === $code) {
        // 【Session】Set verification flag and timestamp
        $_SESSION['final_verification_passed'] = true;
        $_SESSION['verification_timestamp'] = time();
        return new WP_REST_Response(['success' => true, 'message' => 'Verification successful!'], 200);
    }
    return new WP_REST_Response(['success' => false, 'message' => 'Magic Eden Bio does not match the code.'], 403);
}

function bitmap_gallery_api_submit_config_v2($request) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    // 【Session】Check all parts of the session, including the timestamp
    $session_timeout = 4 * 3600; // 4 hours
    if (
        empty($_SESSION['final_verification_passed']) ||
        empty($_SESSION['verification_timestamp']) ||
        (time() - $_SESSION['verification_timestamp'] > $session_timeout) ||
        empty($_SESSION['verified_bitmap_owner_address'])
    ) {
        return new WP_REST_Response(['success' => false, 'message' => 'Invalid or expired session. Please complete the verification process again.'], 403);
    }

    global $wpdb;
    $params = $request->get_json_params();
    $bitmap_id = intval($params['bitmap_id']);
    $image_url = esc_url_raw($params['image_url']);
    $iframe_url = esc_url_raw($params['iframe_url']);
    
    // 【Session】Confirm the bitmap ID from the form matches the one in the session for security
    if ($bitmap_id !== $_SESSION['verified_bitmap_id']) {
        return new WP_REST_Response(['success' => false, 'message' => 'Bitmap ID mismatch. Security check failed.'], 403);
    }
    
    $operator_address = $_SESSION['verified_bitmap_owner_address'];
    $config_table = $wpdb->prefix . 'bitmap_configs';
    
    $result = $wpdb->replace(
        $config_table,
        [
            'bitmap_id' => $bitmap_id,
            'image_url' => $image_url,
            'iframe_url' => $iframe_url,
            'operator' => $operator_address
        ],
        ['%d', '%s', '%s', '%s']
    );

    if ($result === false) {
        return new WP_REST_Response(['success' => false, 'message' => 'Failed to save data to the database.'], 500);
    }

    return new WP_REST_Response(['success' => true, 'message' => 'Configuration saved successfully!'], 200);
}

// --- END: BITMAP UPDATE FORM SNIPPET ---