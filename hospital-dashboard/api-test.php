<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Test - Hospital Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .loading { background: #fff3cd; border-color: #ffeaa7; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; max-height: 300px; }
        .endpoint { font-family: monospace; background: #f1f1f1; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Hospital Dashboard API Test</h1>
        <p>This page tests all API endpoints to ensure CRUD operations work correctly.</p>
        
        <div class="test-section">
            <h2>📊 Overview API Test</h2>
            <button onclick="testOverview()">Test Overview API</button>
            <div id="overview-result"></div>
        </div>

        <div class="test-section">
            <h2>👥 Patients API Test</h2>
            <button onclick="testPatients()">Test Patients List</button>
            <button onclick="testPatientsFilter()">Test Patients Filter</button>
            <div id="patients-result"></div>
        </div>

        <div class="test-section">
            <h2>🏢 Departments API Test</h2>
            <button onclick="testDepartments()">Test Departments List</button>
            <button onclick="testDepartmentsFilter()">Test Departments Filter</button>
            <div id="departments-result"></div>
        </div>

        <div class="test-section">
            <h2>👨‍⚕️ Doctors API Test</h2>
            <button onclick="testDoctors()">Test Doctors List</button>
            <button onclick="testDoctorsFilter()">Test Doctors Filter</button>
            <div id="doctors-result"></div>
        </div>

        <div class="test-section">
            <h2>📅 Appointments API Test</h2>
            <button onclick="testAppointments()">Test Appointments List</button>
            <button onclick="testAppointmentsFilter()">Test Appointments Filter</button>
            <div id="appointments-result"></div>
        </div>

        <div class="test-section">
            <h2>📋 Medical Reports API Test</h2>
            <button onclick="testMedicalReports()">Test Medical Reports List</button>
            <button onclick="testMedicalReportsFilter()">Test Medical Reports Filter</button>
            <div id="medical-reports-result"></div>
        </div>

        <div class="test-section">
            <h2>✅ All Tests</h2>
            <button onclick="runAllTests()">Run All Tests</button>
            <div id="all-tests-result"></div>
        </div>
    </div>

    <script>
        async function testAPI(endpoint, options = {}) {
            try {
                const response = await fetch(endpoint, options);
                const data = await response.json();
                return {
                    success: response.ok,
                    status: response.status,
                    data: data
                };
            } catch (error) {
                return {
                    success: false,
                    error: error.message
                };
            }
        }

        function displayResult(containerId, result, endpointName) {
            const container = document.getElementById(containerId);
            const timestamp = new Date().toLocaleTimeString();
            
            if (result.success) {
                container.innerHTML = `
                    <div class="success">
                        <strong>✅ ${endpointName} - SUCCESS (${timestamp})</strong>
                        <p>Status: ${result.status}</p>
                        <p>Data preview: ${JSON.stringify(result.data).substring(0, 200)}...</p>
                        <details>
                            <summary>Full Response</summary>
                            <pre>${JSON.stringify(result.data, null, 2)}</pre>
                        </details>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="error">
                        <strong>❌ ${endpointName} - FAILED (${timestamp})</strong>
                        <p>Status: ${result.status || 'Network Error'}</p>
                        <p>Error: ${result.error || 'Unknown error'}</p>
                        <details>
                            <summary>Error Details</summary>
                            <pre>${JSON.stringify(result, null, 2)}</pre>
                        </details>
                    </div>
                `;
            }
        }

        async function testOverview() {
            const result = await testAPI('php/overview.php');
            displayResult('overview-result', result, 'Overview API');
        }

        async function testPatients() {
            const result = await testAPI('php/patients.php?action=list');
            displayResult('patients-result', result, 'Patients List API');
        }

        async function testPatientsFilter() {
            const result = await testAPI('php/patients.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'filter',
                    gender: 'Male'
                })
            });
            displayResult('patients-result', result, 'Patients Filter API');
        }

        async function testDepartments() {
            const result = await testAPI('php/departments.php?action=list');
            displayResult('departments-result', result, 'Departments List API');
        }

        async function testDepartmentsFilter() {
            const result = await testAPI('php/departments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'filter',
                    search: 'Cardiology'
                })
            });
            displayResult('departments-result', result, 'Departments Filter API');
        }

        async function testDoctors() {
            const result = await testAPI('php/doctors.php?action=list');
            displayResult('doctors-result', result, 'Doctors List API');
        }

        async function testDoctorsFilter() {
            const result = await testAPI('php/doctors.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'filter',
                    specialization: 'Cardiologist'
                })
            });
            displayResult('doctors-result', result, 'Doctors Filter API');
        }

        async function testAppointments() {
            const result = await testAPI('php/appointments.php?action=list');
            displayResult('appointments-result', result, 'Appointments List API');
        }

        async function testAppointmentsFilter() {
            const result = await testAPI('php/appointments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'filter',
                    status: 'Scheduled'
                })
            });
            displayResult('appointments-result', result, 'Appointments Filter API');
        }

        async function testMedicalReports() {
            const result = await testAPI('php/medical-reports.php?action=list');
            displayResult('medical-reports-result', result, 'Medical Reports List API');
        }

        async function testMedicalReportsFilter() {
            const result = await testAPI('php/medical-reports.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'filter',
                    date_from: '2025-01-01'
                })
            });
            displayResult('medical-reports-result', result, 'Medical Reports Filter API');
        }

        async function runAllTests() {
            const allTestsContainer = document.getElementById('all-tests-result');
            allTestsContainer.innerHTML = '<div class="loading"><strong>🔄 Running all tests...</strong></div>';
            
            const tests = [
                { name: 'Overview', func: testOverview },
                { name: 'Patients', func: testPatients },
                { name: 'Departments', func: testDepartments },
                { name: 'Doctors', func: testDoctors },
                { name: 'Appointments', func: testAppointments },
                { name: 'Medical Reports', func: testMedicalReports }
            ];

            let passed = 0;
            let failed = 0;

            for (const test of tests) {
                try {
                    await test.func();
                    passed++;
                } catch (error) {
                    failed++;
                    console.error(`Test ${test.name} failed:`, error);
                }
            }

            const total = tests.length;
            const successRate = Math.round((passed / total) * 100);
            
            if (passed === total) {
                allTestsContainer.innerHTML = `
                    <div class="success">
                        <strong>🎉 All Tests Passed!</strong>
                        <p>${passed}/${total} tests successful (${successRate}%)</p>
                        <p>Your Hospital Dashboard API is fully functional!</p>
                    </div>
                `;
            } else {
                allTestsContainer.innerHTML = `
                    <div class="error">
                        <strong>⚠️ Some Tests Failed</strong>
                        <p>${passed}/${total} tests passed (${successRate}%)</p>
                        <p>${failed} tests failed - check individual test results above</p>
                    </div>
                `;
            }
        }

        // Auto-run overview test on page load
        window.addEventListener('load', () => {
            setTimeout(testOverview, 500);
        });
    </script>
</body>
</html>