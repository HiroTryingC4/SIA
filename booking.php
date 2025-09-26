<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Trip - QC Travel Guide</title>
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
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .booking-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }

        .booking-info h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .booking-info p {
            color: #666;
            margin-bottom: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #5a67d8;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn">← Back to Map</a>
        
        <div class="header">
            <h1>🚖 Book Your Trip</h1>
            <p>Complete your booking details below</p>
        </div>

        <div class="booking-info" id="bookingInfo">
            <!-- Booking details will be populated by JavaScript -->
        </div>

        <form action="process_booking.php" method="POST" id="bookingForm">
            <input type="hidden" name="booking_type" id="bookingType">
            <input type="hidden" name="destination_name" id="destinationName">
            <input type="hidden" name="destination_lat" id="destinationLat">
            <input type="hidden" name="destination_lng" id="destinationLng">

            <div class="form-row">
                <div class="form-group">
                    <label for="firstName">First Name *</label>
                    <input type="text" id="firstName" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="lastName">Last Name *</label>
                    <input type="text" id="lastName" name="last_name" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
            </div>

            <div class="form-group">
                <label for="pickupAddress">Pickup Address *</label>
                <textarea id="pickupAddress" name="pickup_address" rows="3" required placeholder="Enter your pickup location"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tripDate">Trip Date *</label>
                    <input type="date" id="tripDate" name="trip_date" required>
                </div>
                <div class="form-group">
                    <label for="tripTime">Preferred Time *</label>
                    <input type="time" id="tripTime" name="trip_time" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="passengers">Number of Passengers *</label>
                    <select id="passengers" name="passengers" required>
                        <option value="">Select passengers</option>
                        <option value="1">1 Passenger</option>
                        <option value="2">2 Passengers</option>
                        <option value="3">3 Passengers</option>
                        <option value="4">4 Passengers</option>
                        <option value="5">5+ Passengers</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="vehicleType">Vehicle Preference</label>
                    <select id="vehicleType" name="vehicle_type">
                        <option value="">Any vehicle</option>
                        <option value="sedan">Sedan</option>
                        <option value="suv">SUV</option>
                        <option value="van">Van</option>
                        <option value="luxury">Luxury Car</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="specialRequests">Special Requests</label>
                <textarea id="specialRequests" name="special_requests" rows="3" placeholder="Any special requirements or requests..."></textarea>
            </div>

            <button type="submit" class="submit-btn">Complete Booking</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookingType = sessionStorage.getItem('bookingType');
            const destination = JSON.parse(sessionStorage.getItem('destination') || '{}');

            if (bookingType && destination.name) {
                // Populate hidden fields
                document.getElementById('bookingType').value = bookingType;
                document.getElementById('destinationName').value = destination.name;
                document.getElementById('destinationLat').value = destination.latitude;
                document.getElementById('destinationLng').value = destination.longitude;

                // Display booking info
                const serviceType = bookingType === 'driver' ? 'Book with Driver' : 'Car Rental';
                const icon = bookingType === 'driver' ? '🚖' : '🚗';
                
                document.getElementById('bookingInfo').innerHTML = `
                    <h3>${icon} ${serviceType}</h3>
                    <p><strong>Destination:</strong> ${destination.name}</p>
                    <p><strong>Category:</strong> ${destination.category}</p>
                    <p><strong>Description:</strong> ${destination.description}</p>
                `;

                // Set minimum date to today
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('tripDate').min = today;
            } else {
                // Redirect back if no booking data
                window.location.href = 'index.php';
            }
        });
    </script>
</body>
</html>
