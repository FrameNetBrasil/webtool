# Enhanced Optical Flow Debugging Guide

## Overview

The optical flow algorithm has been enhanced with comprehensive logging, adaptive parameters, and robust tracking mechanisms to address bbox tracking failures during large movements.

## Key Improvements

### 1. Adaptive Search Window
- **Before**: Fixed 30x30 pixel search window
- **After**: Adaptive window size based on previous movement (30-120 pixels)
- **Benefit**: Can track larger movements between frames

### 2. Robust Transformation Estimation
- **Before**: Simple translation-only estimation
- **After**: RANSAC-based outlier rejection with confidence scoring
- **Benefit**: More reliable tracking in presence of noise and occlusion

### 3. Comprehensive Diagnostics
- **Before**: No visibility into tracking quality
- **After**: Real-time statistics and failure detection
- **Benefit**: Easy debugging and parameter tuning

### 4. Intelligent Fallback
- **Before**: Return original bbox when tracking fails
- **After**: Confidence-based decision making
- **Benefit**: Better handling of partial tracking failures

## Configuration Options

### Available Parameters

```javascript
{
    pointsPerDimension: 11,          // Grid size for feature points
    baseSearchWindow: 30,            // Minimum search window size
    adaptiveWindowMultiplier: 1.5,   // Multiplier for adaptive window
    maxSearchWindow: 80,             // Maximum search window size
    minConfidenceThreshold: 0.3,     // Minimum confidence to accept tracking
    enableLogging: true              // Enable/disable debug logging
}
```

### Preset Configurations

Use `window.opticalFlowDebugger.applyTestConfiguration(name)`:

- **conservative**: Safe settings for stable environments
- **aggressive**: Higher search windows for fast movements
- **highPrecision**: More feature points for detailed tracking
- **fastTracking**: Optimized for performance

## Debugging Interface

### Enable Debugging

```javascript
// Enable comprehensive logging
window.opticalFlowDebugger.enableDebugging();

// Apply test configuration
window.opticalFlowDebugger.applyTestConfiguration('aggressive');
```

### Monitor Tracking Quality

```javascript
// Check current tracking statistics
console.log(opticalFlow.getTrackingStats());

// Generate comprehensive report
window.opticalFlowDebugger.generateReport();
```

### Example Console Output

```
OpticalFlow: Tracking 121 points with 45x45 window
OpticalFlow bbox 0: {
  successfulPoints: 87,
  totalPoints: 121,
  successRate: "71.9%",
  confidence: "0.742",
  translation: ["23.45", "15.67"],
  avgDisplacement: "28.34",
  used: "YES"
}
```

## Troubleshooting Common Issues

### Issue: Bbox doesn't move despite large object movement

**Possible Causes:**
1. Search window too small
2. Low confidence threshold causing fallback
3. Poor feature point distribution

**Solutions:**
```javascript
// Increase search window
opticalFlow.updateConfig({
    baseSearchWindow: 50,
    maxSearchWindow: 120
});

// Lower confidence threshold
opticalFlow.updateConfig({
    minConfidenceThreshold: 0.2
});

// Use more feature points
opticalFlow.updateConfig({
    pointsPerDimension: 15
});
```

### Issue: Tracking is too sensitive/erratic

**Solutions:**
```javascript
// Increase confidence threshold
opticalFlow.updateConfig({
    minConfidenceThreshold: 0.5
});

// Use conservative configuration
window.opticalFlowDebugger.applyTestConfiguration('conservative');
```

### Issue: Performance is too slow

**Solutions:**
```javascript
// Use fast tracking configuration
window.opticalFlowDebugger.applyTestConfiguration('fastTracking');

// Reduce feature points
opticalFlow.updateConfig({
    pointsPerDimension: 7
});
```

## Validation Methods

### Manual Testing

```javascript
// Test specific configuration for 10 frames
window.opticalFlowDebugger.testConfiguration('aggressive', 10);

// Compare expected vs actual bbox movement
const expected = { x: 200, y: 150, width: 80, height: 60 };
const actual = { x: 198, y: 153, width: 80, height: 60 };
window.opticalFlowDebugger.compareBboxes(expected, actual, 5);
```

### Automated Analysis

The system automatically detects and warns about:
- Low tracking success rates (< 20%)
- No movement detection (possible failures)
- Large movements (> 50 pixels)

## Performance Metrics

Monitor these key indicators:

1. **Success Rate**: Percentage of feature points successfully tracked
2. **Confidence Score**: Algorithm confidence in transformation estimate
3. **Average Displacement**: Magnitude of detected movement
4. **Processing Time**: Computational performance

### Ideal Ranges
- Success Rate: > 50%
- Confidence Score: > 0.3
- Processing Time: < 50ms per frame

## Best Practices

1. **Start with conservative settings** and gradually increase aggressiveness
2. **Monitor console logs** during testing to identify issues
3. **Use appropriate configuration** for your specific use case:
   - Fast movement → aggressive
   - High precision needed → highPrecision
   - Performance critical → fastTracking
4. **Generate reports regularly** to track algorithm performance over time
5. **Test with various video content** to ensure robustness

## Integration Example

```javascript
// Initialize tracking with enhanced optical flow
const tracker = new ObjectTrackerObject(config);

// Enable debugging for problematic sequences
if (difficultTrackingScenario) {
    window.opticalFlowDebugger.enableDebugging();
    window.opticalFlowDebugger.applyTestConfiguration('aggressive');
}

// Track object
await tracker.trackObject(frameNumber, annotatedObject);

// Review performance
const stats = tracker.opticalFlow.getTrackingStats();
if (stats.successfulPointsRatio < 0.3) {
    console.warn('Poor tracking quality detected');
    // Consider adjusting parameters or manual intervention
}
```

This enhanced system should significantly improve bbox tracking accuracy, especially for scenarios with large object movements between frames.