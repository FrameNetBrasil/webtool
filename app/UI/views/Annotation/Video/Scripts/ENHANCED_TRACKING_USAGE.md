# Enhanced Optical Flow Tracking - Usage Guide

## Overview

The `boxComponent.js` `performTracking()` method has been enhanced with intelligent optical flow configuration, comprehensive diagnostics, and easy debugging capabilities.

## How It Works

### Automatic Configuration
The system now automatically configures optical flow parameters based on:

1. **Bbox size**: Small, medium, or large objects get different tracking strategies
2. **Position**: Objects near image edges get high-precision tracking
3. **Context**: Different scenarios trigger appropriate configurations

### Enhanced Console Output

Instead of basic logging, you now get:

```javascript
🎯 Performing enhanced tracking for frame 125
🎯 Using aggressive tracking (large object)
📊 Tracking Analysis: {
  movement: "23.45px",
  delta: "(18.2, 14.7)",
  successRate: "78.5%",
  confidence: "0.742"
}
✅ Enhanced tracking completed
```

## Usage Methods

### 1. Default Usage (Automatic)

Your existing code continues to work with automatic enhancements:

```javascript
// Your existing call - now automatically enhanced!
let trackedBBox = await this.performTracking(previousBBox);
```

The system automatically:
- Analyzes the bbox context
- Chooses optimal configuration
- Provides detailed diagnostics
- Warns about potential issues

### 2. Manual Configuration

Enable specific tracking modes for challenging scenarios:

```javascript
// For fast-moving objects
boxComponent.useAggressiveTracking();
let trackedBBox = await boxComponent.performTracking(previousBBox);

// For high-precision needs
boxComponent.useConservativeTracking();
let trackedBBox = await boxComponent.performTracking(previousBBox);
```

### 3. Debug Mode

Enable comprehensive debugging:

```javascript
// Enable debug mode for this box component
boxComponent.enableDebugMode();

// Perform tracking with detailed logging
let trackedBBox = await boxComponent.performTracking(previousBBox);

// Generate comprehensive report
window.opticalFlowDebugger.generateReport();
```

### 4. Global Configuration

Configure all optical flow instances:

```javascript
// Apply configuration to all instances
window.opticalFlowDebugger.applyTestConfiguration('aggressive');

// Enable global debugging
window.opticalFlowDebugger.enableDebugging();
```

## Configuration Options

### Automatic Configurations

| Scenario | Configuration | When Applied |
|----------|---------------|--------------|
| **Small Object** | `fastTracking` | BBox area < 2500px² |
| **Large Object** | `aggressive` | BBox area > 10000px² |
| **Near Edge** | `highPrecision` | BBox within 50px of edge |
| **Default** | `conservative` | All other cases |

### Manual Configurations

```javascript
// Available preset configurations
const configs = {
    conservative: {    // Stable, reliable tracking
        pointsPerDimension: 11,
        baseSearchWindow: 30,
        maxSearchWindow: 60,
        minConfidenceThreshold: 0.5
    },
    aggressive: {      // For large movements
        pointsPerDimension: 15,
        baseSearchWindow: 50,
        maxSearchWindow: 120,
        minConfidenceThreshold: 0.2
    },
    highPrecision: {   // More feature points
        pointsPerDimension: 21,
        baseSearchWindow: 40,
        maxSearchWindow: 100,
        minConfidenceThreshold: 0.4
    },
    fastTracking: {    // Performance optimized
        pointsPerDimension: 7,
        baseSearchWindow: 25,
        maxSearchWindow: 80,
        minConfidenceThreshold: 0.3
    }
};
```

## Diagnostic Messages

### Success Indicators
- ✅ **Enhanced tracking completed**: Normal successful tracking
- 🚀 **Large movement detected**: Significant movement successfully tracked
- 📊 **High success rate**: >70% feature points tracked successfully

### Warning Signs
- ⚠️ **No movement detected**: Possible tracking failure
- ⚠️ **Low tracking confidence**: <30% success rate
- ❌ **Tracking failed**: Exception occurred during tracking

### Recommended Actions
- 💡 **Try aggressive configuration**: For tracking failures
- 💡 **Consider manual verification**: For low confidence results
- 💡 **Check video quality**: For consistent failures

## Troubleshooting

### Issue: BBox doesn't move despite visible object movement

**Immediate Solution:**
```javascript
// Switch to aggressive tracking
boxComponent.useAggressiveTracking();
let result = await boxComponent.performTracking(previousBBox);
```

**Analysis:**
```javascript
// Check what's happening
boxComponent.enableDebugMode();
```

### Issue: Tracking is too sensitive/erratic

**Immediate Solution:**
```javascript
// Use conservative settings
boxComponent.useConservativeTracking();
```

### Issue: Poor performance

**Solution:**
```javascript
// Use performance-optimized settings
window.opticalFlowDebugger.applyTestConfiguration('fastTracking');
```

## Browser Console Commands

Once your video annotation page is loaded, you can use these commands:

```javascript
// Quick testing
window.opticalFlowDebugger.enableDebugging();
window.opticalFlowDebugger.applyTestConfiguration('aggressive');

// Performance analysis
window.opticalFlowDebugger.generateReport();

// Configuration testing
window.opticalFlowDebugger.testConfiguration('aggressive', 10);

// Manual bbox comparison
const expected = {x: 200, y: 150, width: 80, height: 60};
const actual = {x: 198, y: 153, width: 80, height: 60};
window.opticalFlowDebugger.compareBboxes(expected, actual, 5);
```

## Best Practices

1. **Monitor Console Output**: Watch for warning messages during tracking
2. **Use Appropriate Modes**: Switch to aggressive for fast movements
3. **Enable Debug Mode**: For problematic sequences to understand failures
4. **Generate Reports**: Periodically check tracking quality statistics
5. **Test Configurations**: Try different settings for specific video content

## Example Workflow

```javascript
// 1. Enable debugging for difficult sequences
boxComponent.enableDebugMode();

// 2. Try automatic tracking first
let result = await boxComponent.performTracking(previousBBox);

// 3. If movement is 0 and object clearly moved
if (result.movement === 0) {
    console.log("Switching to aggressive tracking...");
    boxComponent.useAggressiveTracking();
    result = await boxComponent.performTracking(previousBBox);
}

// 4. Analyze results
const stats = boxComponent.tracker.opticalFlow.getTrackingStats();
console.log("Final tracking quality:", stats);

// 5. Generate comprehensive report
window.opticalFlowDebugger.generateReport();
```

This enhanced system should significantly improve tracking accuracy for large object movements while providing clear visibility into tracking quality and failures.