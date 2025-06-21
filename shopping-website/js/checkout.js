
// Billing address toggle
document.getElementById('same_as_shipping').addEventListener('change', function() {
    const billingFields = document.getElementById('billing_fields');
    billingFields.style.display = this.checked ? 'none' : 'block';
});
// Payment method toggle with shipping visibility
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Hide all payment details
        document.querySelectorAll('.payment-details').forEach(detail => {
            detail.style.display = 'none';
        });
        
        // Show selected payment details
        const selectedDetails = document.getElementById(this.value + '_fields');
        if (selectedDetails) {
            selectedDetails.style.display = 'block';
        }
        
        // Handle Bitcoin payment special case
        const isBitcoin = this.value === 'bitcoin';
        const bitcoinNotice = document.getElementById('bitcoinNotice');
        const billingSection = document.getElementById('billingSection');
        const shippingTitle = document.getElementById('shippingTitle');
        const nameFields = document.getElementById('nameFields');
        const phoneField = document.getElementById('phoneField');
        const addressField = document.getElementById('addressField');
        
        if (isBitcoin) {
            // Show Bitcoin notice and hide full shipping/billing
            bitcoinNotice.style.display = 'block';
            billingSection.style.display = 'none';
            shippingTitle.textContent = 'Delivery Location';
            
            // Hide name, phone, and address fields for Bitcoin
            nameFields.style.display = 'none';
            phoneField.style.display = 'none';
            addressField.style.display = 'none';
            
            // Remove required attribute from hidden fields
            [nameFields, phoneField, addressField].forEach(section => {
                section.querySelectorAll('input[required]').forEach(field => {
                    field.removeAttribute('required');
                    field.setAttribute('data-was-required', 'true');
                });
            });
            
            // Update total display for Bitcoin
            updateBitcoinAmount();
        } else {
            // Hide Bitcoin notice and show full shipping/billing
            bitcoinNotice.style.display = 'none';
            billingSection.style.display = 'block';
            shippingTitle.textContent = 'Shipping Information';
            
            // Show all fields for traditional payments
            nameFields.style.display = 'grid';
            phoneField.style.display = 'block';
            addressField.style.display = 'block';
            
            // Restore required attribute to shipping fields
            [nameFields, phoneField, addressField].forEach(section => {
                section.querySelectorAll('input[data-was-required]').forEach(field => {
                    field.setAttribute('required', '');
                    field.removeAttribute('data-was-required');
                });
            });
        }
    });
});
// Bitcoin address validation
const bitcoinAddressInput = document.getElementById('bitcoin_address');
if (bitcoinAddressInput) {
    bitcoinAddressInput.addEventListener('input', function() {
        validateBitcoinAddress(this);
    });
}
function validateBitcoinAddress(input) {
    const address = input.value.trim();
    const legacyPattern = /^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/;
    const bech32Pattern = /^bc1[a-z0-9]{39,59}$/;
    
    input.classList.remove('bitcoin-valid', 'bitcoin-invalid');
    
    if (address.length === 0) {
        return;
    }
    
    if (legacyPattern.test(address) || bech32Pattern.test(address)) {
        input.classList.add('bitcoin-valid');
    } else {
        input.classList.add('bitcoin-invalid');
    }
}

// Format credit card number
document.getElementById('card_number').addEventListener('input', function() {
    let value = this.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    this.value = formattedValue;
});
// Form validation
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    
    if (paymentMethod === 'bitcoin') {
        // Bitcoin payment validation
        const bitcoinAddress = document.getElementById('bitcoin_address').value.trim();
        const email = document.getElementById('email').value.trim();
        const city = document.getElementById('city').value.trim();
        const state = document.getElementById('state').value.trim();
        const zipCode = document.getElementById('zip_code').value.trim();
        
        if (!email) {
            e.preventDefault();
            alert('Please enter your email address');
            return;
        }
        
        if (!city || !state || !zipCode) {
            e.preventDefault();
            alert('Please enter your city, region, and ZIP code');
            return;
        }
        
        if (!bitcoinAddress) {
            e.preventDefault();
            alert('Please enter your Bitcoin wallet address');
            return;
        }
        
        const legacyPattern = /^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/;
        const bech32Pattern = /^bc1[a-z0-9]{39,59}$/;
        
        if (!legacyPattern.test(bitcoinAddress) && !bech32Pattern.test(bitcoinAddress)) {
            e.preventDefault();
            alert('Please enter a valid Bitcoin address');
            return;
        }
    } else if (paymentMethod === 'credit_card') {
        // Credit card validation
        const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
        const expiryMonth = document.getElementById('expiry_month').value;
        const expiryYear = document.getElementById('expiry_year').value;
        const cvv = document.getElementById('cvv').value;
        
        if (cardNumber.length < 16) {
            e.preventDefault();
            alert('Please enter a valid credit card number');
            return;
        }
        
        if (!expiryMonth || !expiryYear) {
            e.preventDefault();
            alert('Please enter card expiry date');
            return;
        }
        
        if (cvv.length < 3) {
            e.preventDefault();
            alert('Please enter a valid CVV');
            return;
        }
    }
});
