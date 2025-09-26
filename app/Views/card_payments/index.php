<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - Stripe Integration</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .payment-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            position: relative;
        }

        .payment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .payment-header h1 {
            font-size: 24px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .payment-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .payment-form {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .form-group-half {
            flex: 1;
            margin-bottom: 0;
        }

        .card-input-field {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            background: #f8f9fa;
            font-size: 16px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }

        .card-input-field:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: #fff;
        }

        .card-input-field::placeholder {
            color: #aab7c4;
        }

        .card-input-container {
            position: relative;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 15px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .card-input-container.focused {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: #fff;
        }

        .card-input-container.error {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        #card-element {
            padding: 0;
        }

        .amount-display {
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin-bottom: 25px;
        }

        .amount-display .amount {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        .amount-display .currency {
            font-size: 16px;
            color: #666;
            margin-left: 5px;
        }

        .error-message {
            background: #fee;
            color: #e74c3c;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            border-left: 4px solid #e74c3c;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            border-left: 4px solid #28a745;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .payment-success {
            text-align: center;
            padding: 40px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }

        .security-badges {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            opacity: 0.7;
        }

        .security-badge {
            font-size: 12px;
            color: #666;
        }

        @media (max-width: 480px) {
            .payment-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .payment-form {
                padding: 30px 20px;
            }
            
            .payment-header {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <!-- Payment Header -->
        <div class="payment-header">
            <h1>💳 Secure Payment</h1>
            <p>Your payment information is encrypted and secure</p>
        </div>

        <!-- Payment Form -->
        <div id="payment-form-container">
            <form id="payment-form" class="payment-form">
                <!-- Amount Display -->
                <div class="amount-display">
                    <span class="amount">$10.00</span>
                    <span class="currency">USD</span>
                </div>

                <!-- Error Message -->
                <div id="error-message" class="error-message" role="alert"></div>

                <!-- Success Message -->
                <div id="success-message" class="success-message"></div>

                <!-- Card Number -->
                <div class="form-group">
                    <label for="card-number-element">Card Number *</label>
                    <div id="card-number-container" class="card-input-container">
                        <div id="card-number-element"></div>
                    </div>
                </div>

                <!-- Expiry and CVV Row -->
                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="card-expiry-element">Expiry Date *</label>
                        <div id="card-expiry-container" class="card-input-container">
                            <div id="card-expiry-element"></div>
                        </div>
                    </div>
                    <div class="form-group form-group-half">
                        <label for="card-cvc-element">CVV *</label>
                        <div id="card-cvc-container" class="card-input-container">
                            <div id="card-cvc-element"></div>
                        </div>
                    </div>
                </div>


                <!-- Submit Button -->
                <button id="submit-btn" type="submit" class="submit-btn">
                    <span class="loading-spinner"></span>
                    <span class="btn-text">Pay $10.00</span>
                </button>

                <!-- Security Badges -->
                <div class="security-badges">
                    <div class="security-badge">🔒 SSL Encrypted</div>
                    <div class="security-badge">🛡️ PCI Compliant</div>
                    <div class="security-badge">✅ Stripe Secured</div>
                </div>
            </form>
        </div>

        <!-- Success Screen (Hidden by default) -->
        <div id="payment-success" class="payment-success" style="display: none;">
            <div class="success-icon">✓</div>
            <h2>Payment Successful!</h2>
            <p>Your payment has been processed successfully.</p>
            <div class="security-badges">
                <div class="security-badge">🔒 SSL Encrypted</div>
                <div class="security-badge">🛡️ PCI Compliant</div>
                <div class="security-badge">✅ Stripe Secured</div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize Stripe
            const stripe = Stripe("<?= getenv('STRIPE_PUBLISHABLE_KEY') ?>");
            const elements = stripe.elements();
            
            // Custom styling for all elements
            const elementStyle = {
                base: {
                    fontSize: '16px',
                    color: '#333',
                    fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
                invalid: {
                    color: '#e74c3c',
                },
            };

            // Create separate card elements
            const cardNumberElement = elements.create('cardNumber', {
                style: elementStyle,
                placeholder: '1234 5678 9012 3456'
            });

            const cardExpiryElement = elements.create('cardExpiry', {
                style: elementStyle,
                placeholder: 'MM/YY'
            });

            const cardCvcElement = elements.create('cardCvc', {
                style: elementStyle,
                placeholder: '123'
            });

            // Mount the elements
            cardNumberElement.mount('#card-number-element');
            cardExpiryElement.mount('#card-expiry-element');
            cardCvcElement.mount('#card-cvc-element');

            // Handle card number events
            cardNumberElement.on('change', function(event) {
                const container = $('#card-number-container');
                handleElementChange(event, container);
            });

            cardNumberElement.on('focus', function() {
                $('#card-number-container').addClass('focused');
            });

            cardNumberElement.on('blur', function() {
                $('#card-number-container').removeClass('focused');
            });

            // Handle expiry events
            cardExpiryElement.on('change', function(event) {
                const container = $('#card-expiry-container');
                handleElementChange(event, container);
            });

            cardExpiryElement.on('focus', function() {
                $('#card-expiry-container').addClass('focused');
            });

            cardExpiryElement.on('blur', function() {
                $('#card-expiry-container').removeClass('focused');
            });

            // Handle CVC events
            cardCvcElement.on('change', function(event) {
                const container = $('#card-cvc-container');
                handleElementChange(event, container);
            });

            cardCvcElement.on('focus', function() {
                $('#card-cvc-container').addClass('focused');
            });

            cardCvcElement.on('blur', function() {
                $('#card-cvc-container').removeClass('focused');
            });


            // Helper function for element change events
            function handleElementChange(event, container) {
                if (event.error) {
                    container.addClass('error');
                    showError(event.error.message);
                } else {
                    container.removeClass('error');
                    hideError();
                }
            }

            // Handle form submission
            $('#payment-form').on('submit', async function(e) {
                e.preventDefault();
                
                // Show loading state
                setLoadingState(true);
                hideError();
                hideSuccess();

                try {
                    // Step 1 - Create PaymentMethod with separate elements
                    // When using separate elements, use cardNumberElement directly as it groups all elements
                    const { error: pmError, paymentMethod } = await stripe.createPaymentMethod({
                        type: 'card',
                        card: cardNumberElement,
                    });

                    if (pmError) {
                        showError(pmError.message);
                        setLoadingState(false);
                        return;
                    }

                    // Step 2 - Send PaymentMethod ID + amount to backend
                    const response = await fetch("<?= env('api.baseURL') ?>/payments/intent", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            amount: 10, // $10
                            payment_method_id: paymentMethod.id
                        })
                    });

                    const data = await response.json();
                    
                    // Debug: Log the response for troubleshooting
                    console.log('Payment response:', data);

                    // Check if the HTTP response was successful
                    if (!response.ok) {
                        showError(data.message || 'Payment failed. Please try again.');
                        setLoadingState(false);
                        return;
                    }

                    // Payment successful - check if Stripe payment succeeded
                    if (data.data && data.data.status === "succeeded") {
                        console.log('Payment succeeded, clearing form and showing success');
                        showSuccess();
                        setLoadingState(false);
                    } else {
                        showError('Payment status: ' + (data.data ? data.data.status : "unknown"));
                        setLoadingState(false);
                    }

                } catch (error) {
                    console.error('Payment error:', error);
                    showError('An unexpected error occurred. Please try again.');
                    setLoadingState(false);
                }
            });

            // Helper functions
            function setLoadingState(loading) {
                const btn = $('#submit-btn');
                const spinner = $('.loading-spinner');
                const btnText = $('.btn-text');
                
                if (loading) {
                    btn.prop('disabled', true);
                    spinner.show();
                    btnText.text('Processing...');
                } else {
                    btn.prop('disabled', false);
                    spinner.hide();
                    btnText.text('Pay $10.00');
                }
            }

            function showError(message) {
                $('#error-message').text(message).slideDown(300);
                $('#success-message').slideUp(300);
            }

            function hideError() {
                $('#error-message').slideUp(300);
            }

            function showSuccess() {
                // Clear all form data
                clearFormData();
                
                $('#payment-form-container').slideUp(400, function() {
                    $('#payment-success').slideDown(400);
                });
            }

            function clearFormData() {
                // Clear Stripe elements
                cardNumberElement.clear();
                cardExpiryElement.clear();
                cardCvcElement.clear();
                
                // Remove any error states
                $('#card-number-container').removeClass('error focused');
                $('#card-expiry-container').removeClass('error focused');
                $('#card-cvc-container').removeClass('error focused');
                
                // Reset form state
                setLoadingState(false);
                hideError();
            }

            function hideSuccess() {
                $('#success-message').slideUp(300);
            }
        });
    </script>
</body>

</html>