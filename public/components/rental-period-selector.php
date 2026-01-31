<!-- Rental Period Selector Component -->
<!-- Usage: Include this file where you need rental period selection -->

<div class="rental-period-selector">
    <h3>Select Rental Period</h3>
    
    <div class="period-grid">
        <div class="form-group">
            <label for="rental_start">Start Date & Time <span class="required">*</span></label>
            <input 
                type="datetime-local" 
                id="rental_start" 
                name="rental_start" 
                min="<?= date('Y-m-d\TH:i') ?>"
                required
            >
        </div>
        
        <div class="form-group">
            <label for="rental_end">End Date & Time <span class="required">*</span></label>
            <input 
                type="datetime-local" 
                id="rental_end" 
                name="rental_end" 
                min="<?= date('Y-m-d\TH:i') ?>"
                required
            >
        </div>
    </div>
    
    <div class="duration-display" id="duration_display" style="display: none;">
        <div class="duration-info">
            <span class="duration-label">Rental Duration:</span>
            <span class="duration-value" id="duration_value">-</span>
        </div>
        <div class="price-estimate" id="price_estimate" style="display: none;">
            <span class="price-label">Estimated Price:</span>
            <span class="price-value" id="price_value">$0.00</span>
        </div>
    </div>
</div>

<style>
.rental-period-selector {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.rental-period-selector h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.period-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.rental-period-selector .form-group {
    margin-bottom: 0;
}

.rental-period-selector .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.rental-period-selector .form-group label .required {
    color: #e74c3c;
}

.rental-period-selector input[type="datetime-local"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: inherit;
}

.rental-period-selector input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.duration-display {
    background: white;
    border-radius: 6px;
    padding: 1rem;
    margin-top: 1rem;
}

.duration-info,
.price-estimate {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.duration-label,
.price-label {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.duration-value {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.1rem;
}

.price-value {
    font-weight: 700;
    color: #27ae60;
    font-size: 1.3rem;
}

@media (max-width: 768px) {
    .period-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Calculate rental duration
function calculateDuration() {
    const startInput = document.getElementById('rental_start');
    const endInput = document.getElementById('rental_end');
    const durationDisplay = document.getElementById('duration_display');
    const durationValue = document.getElementById('duration_value');
    
    if (!startInput || !endInput) return;
    
    const start = new Date(startInput.value);
    const end = new Date(endInput.value);
    
    if (start && end && end > start) {
        const diffMs = end - start;
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffDays = Math.floor(diffHours / 24);
        const remainingHours = diffHours % 24;
        
        let durationText = '';
        if (diffDays > 0) {
            durationText = `${diffDays} day${diffDays > 1 ? 's' : ''}`;
            if (remainingHours > 0) {
                durationText += ` ${remainingHours} hour${remainingHours > 1 ? 's' : ''}`;
            }
        } else {
            durationText = `${diffHours} hour${diffHours > 1 ? 's' : ''}`;
        }
        
        durationValue.textContent = durationText;
        durationDisplay.style.display = 'block';
        
        // Trigger custom event for price calculation
        const event = new CustomEvent('rentalPeriodChanged', {
            detail: { start, end, hours: diffHours, days: diffDays }
        });
        document.dispatchEvent(event);
    } else {
        durationDisplay.style.display = 'none';
    }
}

// Attach event listeners
document.addEventListener('DOMContentLoaded', function() {
    const startInput = document.getElementById('rental_start');
    const endInput = document.getElementById('rental_end');
    
    if (startInput && endInput) {
        startInput.addEventListener('change', calculateDuration);
        endInput.addEventListener('change', calculateDuration);
        
        // Validate end date is after start date
        endInput.addEventListener('change', function() {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            
            if (start && end && end <= start) {
                alert('End date must be after start date');
                endInput.value = '';
            }
        });
    }
});
</script>
