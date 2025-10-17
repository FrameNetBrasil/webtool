/**
 * Debugging and testing utilities for optical flow algorithm
 */
class OpticalFlowDebugger {
    constructor(opticalFlowInstance) {
        this.opticalFlow = opticalFlowInstance;
        this.testResults = [];
        this.isDebugging = false;
    }

    enableDebugging() {
        this.isDebugging = true;
        this.opticalFlow.updateConfig({ enableLogging: true });
        console.log('OpticalFlow debugging enabled');
    }

    disableDebugging() {
        this.isDebugging = false;
        this.opticalFlow.updateConfig({ enableLogging: false });
        console.log('OpticalFlow debugging disabled');
    }

    // Preset configurations for different scenarios
    getTestConfigurations() {
        return {
            conservative: {
                pointsPerDimension: 11,
                baseSearchWindow: 30,
                adaptiveWindowMultiplier: 1.2,
                maxSearchWindow: 60,
                minConfidenceThreshold: 0.5
            },
            aggressive: {
                pointsPerDimension: 15,
                baseSearchWindow: 50,
                adaptiveWindowMultiplier: 2.0,
                maxSearchWindow: 120,
                minConfidenceThreshold: 0.2
            },
            highPrecision: {
                pointsPerDimension: 21,
                baseSearchWindow: 40,
                adaptiveWindowMultiplier: 1.8,
                maxSearchWindow: 100,
                minConfidenceThreshold: 0.4
            },
            fastTracking: {
                pointsPerDimension: 7,
                baseSearchWindow: 25,
                adaptiveWindowMultiplier: 1.5,
                maxSearchWindow: 80,
                minConfidenceThreshold: 0.3
            }
        };
    }

    applyTestConfiguration(configName) {
        const configs = this.getTestConfigurations();
        if (configs[configName]) {
            this.opticalFlow.updateConfig(configs[configName]);
            console.log(`Applied ${configName} configuration:`, configs[configName]);
            return true;
        }
        console.error(`Configuration '${configName}' not found`);
        return false;
    }

    // Test bbox movement scenarios
    createTestScenarios() {
        return [
            {
                name: 'Small Movement',
                description: 'Object moves 5-10 pixels',
                originalBbox: { x: 100, y: 100, width: 80, height: 60 },
                expectedBbox: { x: 107, y: 103, width: 80, height: 60 },
                tolerance: 3
            },
            {
                name: 'Medium Movement',
                description: 'Object moves 20-30 pixels',
                originalBbox: { x: 150, y: 120, width: 100, height: 80 },
                expectedBbox: { x: 175, y: 145, width: 100, height: 80 },
                tolerance: 5
            },
            {
                name: 'Large Movement',
                description: 'Object moves 50+ pixels',
                originalBbox: { x: 200, y: 150, width: 120, height: 90 },
                expectedBbox: { x: 260, y: 210, width: 120, height: 90 },
                tolerance: 10
            },
            {
                name: 'Edge Case',
                description: 'Object near image boundaries',
                originalBbox: { x: 10, y: 10, width: 60, height: 40 },
                expectedBbox: { x: 15, y: 20, width: 60, height: 40 },
                tolerance: 3
            }
        ];
    }

    logTrackingResult(originalBboxes, trackedBboxes, frameNumber) {
        if (!this.isDebugging) return;

        const result = {
            frameNumber: frameNumber,
            timestamp: new Date().toISOString(),
            stats: this.opticalFlow.getTrackingStats(),
            bboxes: []
        };

        for (let i = 0; i < originalBboxes.length; i++) {
            const original = originalBboxes[i];
            const tracked = trackedBboxes[i];

            if (original && tracked) {
                const movement = {
                    deltaX: tracked.x - original.x,
                    deltaY: tracked.y - original.y,
                    magnitude: Math.sqrt(
                        Math.pow(tracked.x - original.x, 2) +
                        Math.pow(tracked.y - original.y, 2)
                    )
                };

                result.bboxes.push({
                    index: i,
                    original: original,
                    tracked: tracked,
                    movement: movement
                });
            }
        }

        this.testResults.push(result);
        console.log(`Frame ${frameNumber} tracking result:`, result);

        // Alert for significant issues
        if (result.stats.successfulPointsRatio < 0.2) {
            console.warn(`⚠️ Low tracking success rate (${(result.stats.successfulPointsRatio * 100).toFixed(1)}%) at frame ${frameNumber}`);
        }

        if (result.bboxes.some(b => b.movement.magnitude === 0)) {
            console.warn(`⚠️ No movement detected at frame ${frameNumber} - possible tracking failure`);
        }

        if (result.bboxes.some(b => b.movement.magnitude > 50)) {
            console.log(`🚀 Large movement detected (${result.bboxes.find(b => b.movement.magnitude > 50).movement.magnitude.toFixed(1)}px) at frame ${frameNumber}`);
        }
    }

    generateReport() {
        if (this.testResults.length === 0) {
            console.log('No tracking data available for report');
            return null;
        }

        const report = {
            totalFrames: this.testResults.length,
            avgSuccessRate: 0,
            avgMovementMagnitude: 0,
            largestMovement: 0,
            potentialFailures: 0,
            recommendations: []
        };

        let totalSuccessRate = 0;
        let totalMovements = [];
        let failures = 0;

        this.testResults.forEach(result => {
            totalSuccessRate += result.stats.successfulPointsRatio;

            result.bboxes.forEach(bbox => {
                totalMovements.push(bbox.movement.magnitude);

                if (bbox.movement.magnitude === 0 && result.stats.successfulPointsRatio < 0.3) {
                    failures++;
                }
            });
        });

        report.avgSuccessRate = (totalSuccessRate / this.testResults.length * 100).toFixed(1);
        report.avgMovementMagnitude = totalMovements.length > 0 ?
            (totalMovements.reduce((a, b) => a + b) / totalMovements.length).toFixed(2) : 0;
        report.largestMovement = totalMovements.length > 0 ? Math.max(...totalMovements).toFixed(2) : 0;
        report.potentialFailures = failures;

        // Generate recommendations
        if (parseFloat(report.avgSuccessRate) < 50) {
            report.recommendations.push('Consider increasing pointsPerDimension for better feature coverage');
            report.recommendations.push('Try increasing maxSearchWindow for larger movements');
        }

        if (parseFloat(report.largestMovement) > 80) {
            report.recommendations.push('Large movements detected - increase adaptiveWindowMultiplier');
        }

        if (failures > this.testResults.length * 0.3) {
            report.recommendations.push('High failure rate - consider lowering minConfidenceThreshold');
            report.recommendations.push('Enable more aggressive configuration for difficult scenarios');
        }

        console.log('📊 Optical Flow Tracking Report:', report);
        return report;
    }

    clearResults() {
        this.testResults = [];
        console.log('Test results cleared');
    }

    // Quick configuration tester
    testConfiguration(configName, testDuration = 10) {
        console.log(`🧪 Testing ${configName} configuration for ${testDuration} frames...`);

        this.clearResults();
        this.applyTestConfiguration(configName);
        this.enableDebugging();

        // Setup automatic report generation
        setTimeout(() => {
            const report = this.generateReport();
            console.log(`✅ ${configName} configuration test completed`);
            return report;
        }, testDuration * 1000);
    }

    // Manual bbox comparison for testing
    compareBboxes(expected, actual, tolerance = 5) {
        const deltaX = Math.abs(expected.x - actual.x);
        const deltaY = Math.abs(expected.y - actual.y);
        const deltaW = Math.abs(expected.width - actual.width);
        const deltaH = Math.abs(expected.height - actual.height);

        const isAccurate = deltaX <= tolerance && deltaY <= tolerance &&
                          deltaW <= tolerance && deltaH <= tolerance;

        const result = {
            accurate: isAccurate,
            deltas: { x: deltaX, y: deltaY, width: deltaW, height: deltaH },
            tolerance: tolerance
        };

        console.log('Bbox comparison:', result);
        return result;
    }
}

// Global debugger instance - can be accessed from browser console
window.opticalFlowDebugger = null;

// Initialize debugger when optical flow is available
function initializeOpticalFlowDebugger(opticalFlowInstance) {
    window.opticalFlowDebugger = new OpticalFlowDebugger(opticalFlowInstance);
    console.log('🔧 OpticalFlow debugger initialized. Access via window.opticalFlowDebugger');
    console.log('Available methods: enableDebugging(), testConfiguration(), generateReport()');
}