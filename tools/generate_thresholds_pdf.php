<?php
/**
 * SHINEGUARD THESIS DOCUMENTATION (OFFICIAL EDITION)
 * 4-Page Technical Manual.
 * Format: [Metric Name] ([Standard]): Rule: [X]. Justification: [Y].
 */

require_once '../dbconnect.php';
requireLogin('System Admin');
require_once('../tcpdf/tcpdf.php');

// 1. Initialize TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('ShineGuard Hulo');
$pdf->SetAuthor('Infrastructure Group');
$pdf->SetTitle('Infrastructure Governance & Compliance manual');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(TRUE, 20);

// Assets
$logoPath = realpath(__DIR__ . '/../img/ShineGuard3.png');
$archMapPath = realpath(__DIR__ . '/../img/architecture_map.png');

// --- PAGE 1: IOT SENSOR THRESHOLDS ---
$pdf->AddPage();
$html1 = '
<style>
    h1 { color: #000000; font-size: 18pt; font-weight: bold; margin-bottom: 20px; text-align: left; }
    p { color: #222222; font-size: 9.5pt; line-height: 1.5; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { color: #000000; font-weight: bold; border: 1.5px solid #000000; padding: 10px; font-size: 11pt; text-align: left; }
    td { border: 1px solid #000000; padding: 10px; font-size: 10pt; color: #000000; vertical-align: middle; }
    .rule-box { border-left: 3px solid #000000; padding-left: 10px; margin-bottom: 20px; }
    .rule-title { font-weight: bold; font-size: 10.5pt; display: block; margin-bottom: 3px; }
    .rule-line { font-size: 9.5pt; color: #333333; margin-bottom: 2px; }
</style>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:none; margin-bottom: 30px;">
    <tr>
        <td width="30%" style="border:none;"><img src="' . $logoPath . '" style="width: 100px;"></td>
        <td width="70%" style="border:none; text-align: right;">
            <p style="font-size: 8pt; color: #555555; margin:0; line-height: 1.2;">
                OFFICIAL DOCUMENTATION: SG-2026-FINAL<br>
                DEPARTMENT: URBAN GOVERNANCE & INFRASTRUCTURE
            </p>
        </td>
    </tr>
</table>

<h1>1. IOT Sensor Thresholds</h1>

<table>
    <thead>
        <tr>
            <th width="20%">Metric</th>
            <th width="20%">Range</th>
            <th width="20%">Warning</th>
            <th width="15%">Critical</th>
            <th width="25%">Compliance Standard</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><b>Voltage</b></td><td>210V - 240V</td><td>&lt; 200V / &gt; 250V</td><td>&lt; 180V</td><td>IEC 60038</td></tr>
        <tr><td><b>Temperature</b></td><td>20&deg;C - 55&deg;C</td><td>&gt; 65&deg;C</td><td>&gt; 85&deg;C</td><td>Industrial Grade</td></tr>
        <tr><td><b>Current</b></td><td>0.01A - 2.0A</td><td>&gt; 2.5A</td><td>&gt; 5.0A</td><td>IEC 60598-1</td></tr>
        <tr><td><b>Ambient Light</b></td><td>10 - 1000 lux</td><td>&lt; 10 lux</td><td>0 lux</td><td>EN 13201</td></tr>
        <tr><td><b>Humidity</b></td><td>10% - 60%</td><td>&gt; 80%</td><td>&gt; 95%</td><td>IP65/67 Standard</td></tr>
    </tbody>
</table>

<div class="rule-box">
    <span class="rule-title">Voltage (IEC 60038 Compliance):</span>
    <p class="rule-line"><b>Rule:</b> Critical Trip at < 180V.</p>
    <p class="rule-line"><b>Justification:</b> Prevents SMPS current surges during brownouts that lead to silicon failure.</p>
</div>

<div class="rule-box">
    <span class="rule-title">Temperature (Industrial Grade):</span>
    <p class="rule-line"><b>Rule:</b> Shutdown at > 85&deg;C.</p>
    <p class="rule-line"><b>Justification:</b> Prevents "Thermal Fatigue" in power semiconductors, prolonging hardware lifespan.</p>
</div>

<div class="rule-box">
    <span class="rule-title">Current (IEC 60598-1 Compliance):</span>
    <p class="rule-line"><b>Rule:</b> Critical Trip at > 5.0A.</p>
    <p class="rule-line"><b>Justification:</b> Acts as a digital circuit breaker to protect the controller from high-impedance short circuits.</p>
</div>

<div class="rule-box">
    <span class="rule-title">Ambient Light (EN 13201 Compliance):</span>
    <p class="rule-line"><b>Rule:</b> Override at < 10 lux.</p>
    <p class="rule-line"><b>Justification:</b> Ensures urban safety by maintaining road illumination during visibility drops.</p>
</div>

<div class="rule-box">
    <span class="rule-title">Humidity (IP65/67 Standard):</span>
    <p class="rule-line"><b>Rule:</b> Alert at > 80% RH.</p>
    <p class="rule-line"><b>Justification:</b> Predicts gasket failure to prevent water ingress damage to internal electronics.</p>
</div>
';

$pdf->writeHTML($html1, true, false, true, false, '');

// --- PAGE 2: CYBERSECURITY THRESHOLDS ---
$pdf->AddPage();
$html2 = '
<h1>2. Security/ Cybersecurity Thresholds</h1>

<table>
    <thead>
        <tr>
            <th width="25%">Rule Name</th>
            <th width="20%">Limit</th>
            <th width="27%">Compliance Standard</th>
            <th width="28%">Security Threat</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><b>API Velocity</b></td><td>60 req / min</td><td>OWASP API7:2023</td><td>DDoS Mitigation</td></tr>
        <tr><td><b>Auth Expiry</b></td><td>15 mins idle</td><td>NIST SP 800-63B</td><td>Hijacking Defense</td></tr>
        <tr><td><b>SBA Bulk Lockout</b></td><td>5 min Window</td><td>High Assurance IAM</td><td>Muli-factor Re-Auth</td></tr>
        <tr><td><b>Log Chain</b></td><td>HMAC-SHA256</td><td>FIPS 180-4</td><td>Non-Repudiation</td></tr>
    </tbody>
</table>

<div class="rule-box">
    <span class="rule-title">API Velocity (OWASP API7:2023 Compliance):</span>
    <p class="rule-line"><b>Rule:</b> Throttle at 60 req/min.</p>
    <p class="rule-line"><b>Justification:</b> Mitigates automated DDoS attacks and ensures high API availability for sensors.</p>
</div>

<div class="rule-box">
    <span class="rule-title">Auth Expiry (NIST SP 800-63B Compliance):</span>
    <p class="rule-line"><b>Rule:</b> Session destruction after 15 mins idle.</p>
    <p class="rule-line"><b>Justification:</b> Prevents unauthorized terminal hijacking in unattended city maintenance hubs.</p>
</div>

<div class="rule-box">
    <span class="rule-title">SBA Bulk Lockout (High Assurance IAM):</span>
    <p class="rule-line"><b>Rule:</b> 5-minute administrative window.</p>
    <p class="rule-line"><b>Justification:</b> Forces re-authentication for critical city-wide commands to prevent unauth access.</p>
</div>

<div class="rule-box">
    <span class="rule-title">Log Chain (FIPS 180-4 Compliance):</span>
    <p class="rule-line"><b>Rule:</b> HMAC-SHA256 Cryptographic Chaining.</p>
    <p class="rule-line"><b>Justification:</b> Provably ensures that all activity logs are immutable and mathematically untamperable.</p>
</div>
';

$pdf->writeHTML($html2, true, false, true, false, '');

// --- PAGE 3: ASSET LIFECYCLE ---
$pdf->AddPage();
$html3 = '
<h1>3. Asset Lifecycle & Connectivity Map</h1>
<br>
<div style="text-align: center; margin-bottom: 20px;">
    <img src="' . $archMapPath . '" width="500">
</div>
<br>
<table>
    <thead>
        <tr>
            <th width="35%">Hardware Lifecycle</th>
            <th width="30%">Threshold</th>
            <th width="35%">Compliance Standard</th>
        </tr>
    </thead>
    <tbody>
        <tr><td><b>Service Interval Replacement</b></td><td>12 Months / 4k Hrs</td><td>Preventive (EN 13306)</td></tr>
        <tr><td><b>Health Threshold</b></td><td>> 5 Critical Errors</td><td>Condition Based Maintenance</td></tr>
        <tr><td><b>Resolution Target</b></td><td>&lt; 24 Hours</td><td>SLA Compliance (ISO 9001)</td></tr>
        <tr><td><b>Retirement Age</b></td><td>5 Years / 25k Hrs</td><td>Total Cost of Ownership</td></tr>
    </tbody>
</table>

<div class="rule-box">
    <span class="rule-title">Service Interval Replacement (EN 13306 Compliance):</span>
    <p class="rule-line"><b>Rule:</b> Maintenance alert at 12 Months / 4k Hrs.</p>
    <p class="rule-line"><b>Justification:</b> Schedules routine maintenance to reduce overall repair costs and ensure hardware reliability.</p>
</div>

<div class="rule-box">
    <span class="rule-title">Health Threshold (Condition Based Maintenance):</span>
    <p class="rule-line"><b>Rule:</b> Maintenance trigger at > 5 Critical Errors.</p>
    <p class="rule-line"><b>Justification:</b> Triggers preventative maintenance when a device logs multiple errors that correlate with imminent failure.</p>
</div>

<div class="rule-box">
    <span class="rule-title">Resolution Target (ISO 9001 Compliance):</span>
    <p class="rule-line"><b>Rule:</b> Resolution in < 24 hours.</p>
    <p class="rule-line"><b>Justification:</b> Ensures public safety by maintaining high urban lighting availability and meeting service standards.</p>
</div>

<div class="rule-box">
    <span class="rule-title">Retirement Age (Total Cost of Ownership):</span>
    <p class="rule-line"><b>Rule:</b> Asset decommission at 5 Years / 25k Hrs.</p>
    <p class="rule-line"><b>Justification:</b> Defines the projected asset lifespan to optimize replacement cycles and manage long-term operational costs.</p>
</div>

<p style="text-align: center; color: #555555; font-size: 8pt; margin-top: 50px;">
  -- END OF TECHNICAL GOVERNANCE MANUAL --<br>
  ShineGuard Smart Monitoring System &copy; 2026
</p>
';

$pdf->writeHTML($html3, true, false, true, false, '');

// 3. Output
$filename = 'ShineGuard_Technical_Docs_Final.pdf';
$savePath = __DIR__ . '/../exports/docs/' . $filename;
$pdf->Output($savePath, 'F');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manual Export | ShineGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f4f5; color: #18181b; padding: 40px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border: 1px solid #e4e4e7; padding: 40px; border-radius: 16px; text-align: center; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .success-icon { font-size: 3.5rem; margin-bottom: 20px; }
        h1 { margin: 0; font-weight: 900; color: #000; font-size: 1.5rem; }
        p { color: #71717a; margin: 10px 0 20px; line-height: 1.5; font-size: 0.9rem; }
        .btn { background: #000; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; display: inline-block; transition: background 0.2s; font-size: 0.85rem; }
        .btn:hover { background: #27272a; }
    </style>
</head>
<body>
    <div class="card">
        <div class="success-icon">📄</div>
        <h1>Documentation Completed</h1>
        <p>I have applied the [Rule/Justification] format to the Cybersecurity and Hardware Lifecycle sections as requested.</p>
        <a href="../exports/docs/<?php echo $filename; ?>" class="btn" target="_blank">Download Finalized docs</a>
    </div>
</body>
</html>
