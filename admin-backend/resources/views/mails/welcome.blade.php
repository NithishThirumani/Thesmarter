<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to theSmartr</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background-color: #000000;
            padding: 35px 40px;
            text-align: center;
        }
        .logo-img {
            max-width: 200px;
            height: auto;
        }
        .hero-section {
            background: linear-gradient(135deg, #FFD700 0%, #FFC700 100%);
            padding: 50px 40px;
            text-align: center;
            color: #000000;
        }
        .hero-title {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 15px 0;
            line-height: 1.2;
        }
        .hero-subtitle {
            font-size: 18px;
            margin: 0;
            font-weight: 400;
            opacity: 0.9;
        }
        .content {
            padding: 45px 40px;
        }
        .greeting {
            font-size: 20px;
            color: #000000;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .user-name {
            color: #FFD700;
        }
        .intro-text {
            font-size: 16px;
            line-height: 1.7;
            color: #333333;
            margin-bottom: 30px;
        }
        .value-prop {
            background-color: #f9f9f9;
            border-left: 4px solid #FFD700;
            padding: 25px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .value-prop-title {
            font-size: 18px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 15px;
        }
        .value-list {
            margin: 0;
            padding-left: 20px;
        }
        .value-list li {
            font-size: 15px;
            color: #444444;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .categories-section {
            margin: 40px 0;
        }
        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 25px;
            text-align: center;
        }
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .category-card {
            background-color: #ffffff;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            padding: 25px 15px;
            text-align: center;
            transition: all 0.2s ease;
        }
        .category-icon {
            font-size: 36px;
            margin-bottom: 12px;
            display: block;
        }
        .category-name {
            font-size: 14px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 5px;
        }
        .category-desc {
            font-size: 12px;
            color: #666666;
            line-height: 1.4;
        }
        .features-section {
            background-color: #fafafa;
            padding: 35px;
            margin: 35px 0;
            border-radius: 8px;
        }
        .feature-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e8e8e8;
        }
        .feature-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .feature-icon-box {
            width: 50px;
            height: 50px;
            background-color: #FFD700;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        .feature-content {
            flex: 1;
        }
        .feature-title {
            font-size: 16px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 5px;
        }
        .feature-description {
            font-size: 14px;
            color: #555555;
            line-height: 1.6;
        }
        .cta-section {
            background-color: #000000;
            color: #ffffff;
            padding: 40px;
            text-align: center;
            margin: 35px 0;
            border-radius: 8px;
        }
        .cta-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .cta-text {
            font-size: 15px;
            color: #cccccc;
            margin-bottom: 25px;
        }
        .cta-button {
            display: inline-block;
            background-color: #FFD700;
            color: #000000;
            padding: 16px 45px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
        }
        .stats-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 35px 0;
            padding: 30px;
            background-color: #fafafa;
            border-radius: 8px;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
        }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 13px;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-box {
            background-color: #fffbf0;
            border: 1px solid #FFD700;
            padding: 25px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .info-box-title {
            font-size: 16px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 12px;
        }
        .info-box-text {
            font-size: 14px;
            color: #555555;
            line-height: 1.6;
            margin: 0;
        }
        .footer {
            background-color: #000000;
            color: #ffffff;
            padding: 40px;
        }
        .footer-top {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #333333;
        }
        .footer-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #FFD700;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer-link {
            display: block;
            color: #cccccc;
            text-decoration: none;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .footer-contact {
            font-size: 13px;
            color: #cccccc;
            line-height: 1.8;
        }
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
        }
        .footer-powered {
            color: #FFD700;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .footer-copyright {
            font-size: 12px;
            color: #888888;
            line-height: 1.6;
        }
        .divider {
            height: 1px;
            background-color: #e8e8e8;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="https://ca-business.bizwy.in/v1/assets/thesmartr-logo.png" alt="theSmartr" class="logo-img">
        </div>

        <!-- Hero Section -->
        <div class="hero-section">
            {{-- <h1 class="hero-title">Welcome to Your All-in-One Business Platform</h1> --}}
            <p class="hero-subtitle">Connecting you with thousands of businesses across multiple industries</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Hi <span class="">{{ $name }}</span>,</p>
            
            <p class="intro-text">
                Thank you for joining <strong>theSmartr</strong> – Canada's premier business aggregator platform. We're excited to have you on board and look forward to revolutionizing the way you discover, connect with, and transact with businesses across various industries.
            </p>

            <div class="value-prop">
                <div class="value-prop-title">What theSmartr Offers You:</div>
                <ul class="value-list">
                    <li><strong>Multi-Domain Access:</strong> Browse and engage with businesses from retail, services, healthcare, hospitality, and more—all in one place</li>
                    <li><strong>Seamless Shopping:</strong> Purchase products directly through our integrated e-commerce experience</li>
                    <li><strong>Easy Booking:</strong> Schedule appointments with service providers instantly</li>
                    <li><strong>Verified Businesses:</strong> All partners are thoroughly vetted to ensure quality and reliability</li>
                    <li><strong>Secure Transactions:</strong> Enterprise-grade security for all your payments and personal data</li>
                </ul>
            </div>

            <!-- Categories Section -->
            <div class="categories-section">
                <h2 class="section-title">Explore Business Categories</h2>
                <div class="categories-grid">
                    <div class="category-card">
                        <span class="category-icon">🛍️</span>
                        <div class="category-name">Retail & Shopping</div>
                        <div class="category-desc">Browse products from verified retailers</div>
                    </div>
                    <div class="category-card">
                        <span class="category-icon">💇</span>
                        <div class="category-name">Beauty & Wellness</div>
                        <div class="category-desc">Book salons and spa appointments</div>
                    </div>
                    <div class="category-card">
                        <span class="category-icon">🏥</span>
                        <div class="category-name">Healthcare</div>
                        <div class="category-desc">Connect with medical professionals</div>
                    </div>
                    <div class="category-card">
                        <span class="category-icon">🍽️</span>
                        <div class="category-name">Food & Dining</div>
                        <div class="category-desc">Order from restaurants and cafes</div>
                    </div>
                    <div class="category-card">
                        <span class="category-icon">🏠</span>
                        <div class="category-name">Home Services</div>
                        <div class="category-desc">Find plumbers, electricians & more</div>
                    </div>
                    <div class="category-card">
                        <span class="category-icon">💼</span>
                        <div class="category-name">Professional Services</div>
                        <div class="category-desc">Legal, financial, and consulting</div>
                    </div>
                </div>
            </div>

            <!-- Platform Statistics -->
            <div class="stats-section">
                <div class="stat-box">
                    <div class="stat-number">10,000+</div>
                    <div class="stat-label">Partner Businesses</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Business Categories</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">100K+</div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="features-section">
                <h2 class="section-title" style="margin-top: 0;">Platform Features</h2>
                
                <div class="feature-row">
                    <div class="feature-icon-box">🔍</div>
                    <div class="feature-content">
                        <div class="feature-title">Smart Search & Discovery</div>
                        <div class="feature-description">Advanced filters and AI-powered recommendations help you find exactly what you need across all business categories.</div>
                    </div>
                </div>

                <div class="feature-row">
                    <div class="feature-icon-box">📱</div>
                    <div class="feature-content">
                        <div class="feature-title">Unified Dashboard</div>
                        <div class="feature-description">Manage all your purchases, bookings, and service requests from a single, intuitive interface.</div>
                    </div>
                </div>

                <div class="feature-row">
                    <div class="feature-icon-box">💳</div>
                    <div class="feature-content">
                        <div class="feature-title">Multiple Payment Options</div>
                        <div class="feature-description">Pay via cards, net banking with complete security and instant confirmations.</div>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <div class="info-box-title">Getting Started is Easy</div>
                <p class="info-box-text">
                    Your account is now active and ready to use. Simply log in to explore thousands of businesses, make purchases, book appointments, and manage all your transactions from your personalized dashboard.
                </p>
            </div>

            <!-- CTA Section -->
            <div class="cta-section">
                <div class="cta-title">Ready to Start Exploring?</div>
                <p class="cta-text">
                    Access your app to discover businesses and services tailored to your needs.
                </p>
            </div>

            <div class="divider"></div>

            <p class="intro-text" style="font-size: 14px; color: #666666; margin-bottom: 10px;">
                If you have any questions or need assistance, please don't hesitate to contact our support team at <strong>support@thesmartr.com</strong>.
            </p>

            <p class="intro-text" style="font-size: 14px; color: #666666; margin-bottom: 0;">
                Thank you for choosing theSmartr. We look forward to serving you.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            

            <div class="footer-bottom">
                <div class="footer-powered">Powered by <strong>Bizwy</strong></div>
                <div class="footer-copyright">
                    <p>© 2024 theSmartr. All rights reserved.</p>
                    <p><a href="#" style="color: #FFD700; text-decoration: none;">Privacy Policy</a> | <a href="#" style="color: #FFD700; text-decoration: none;">Terms of Service</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>