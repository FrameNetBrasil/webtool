/**
 * Tracks point between two consecutive frames using optical flow.
 */
class OpticalFlowObject {
    constructor() {
        this.isInitialized = false;
        this.previousPyramid = new jsfeat.pyramid_t(3);
        this.currentPyramid = new jsfeat.pyramid_t(3);

        // Configuration parameters
        this.config = {
            pointsPerDimension: 11,
            baseSearchWindow: 30,
            adaptiveWindowMultiplier: 1.5,
            maxSearchWindow: 80,
            minConfidenceThreshold: 0.3,
            enableLogging: true
        };

        // Tracking statistics
        this.stats = {
            lastTrackingQuality: 0,
            lastTranslation: [0, 0],
            averageDisplacement: 0,
            successfulPointsRatio: 0
        };

        // Initialize debugger if available
        if (typeof initializeOpticalFlowDebugger === 'function') {
            initializeOpticalFlowDebugger(this);
        }
    }

    init(imageData) {
//        console.log(imageData, jsfeat);
        this.previousPyramid.allocate(imageData.width, imageData.height, jsfeat.U8_t | jsfeat.C1_t);
        this.currentPyramid.allocate(imageData.width, imageData.height, jsfeat.U8_t | jsfeat.C1_t);
        jsfeat.imgproc.grayscale(imageData.data, imageData.width, imageData.height, this.previousPyramid.data[0]);
        this.previousPyramid.build(this.previousPyramid.data[0]);
        this.isInitialized = true;
    }

    reset() {
        this.isInitialized = false;
        this.stats = {
            lastTrackingQuality: 0,
            lastTranslation: [0, 0],
            averageDisplacement: 0,
            successfulPointsRatio: 0
        };
    }

    updateConfig(newConfig) {
        this.config = {...this.config, ...newConfig};
    }

    getTrackingStats() {
        return {...this.stats};
    }

    calculateAdaptiveSearchWindow(previousDisplacement) {
        const baseMagnitude = Math.sqrt(previousDisplacement[0] ** 2 + previousDisplacement[1] ** 2);
        const adaptiveWindow = Math.max(
            this.config.baseSearchWindow,
            Math.min(this.config.maxSearchWindow,
                    this.config.baseSearchWindow + baseMagnitude * this.config.adaptiveWindowMultiplier)
        );
        return Math.round(adaptiveWindow);
    }

    track(imageData, bboxes) {
        if (!this.isInitialized) {
            console.log('OpticalFlow: not initialized');
            throw 'not initialized';
        }

        const startTime = performance.now();

        jsfeat.imgproc.grayscale(imageData.data, imageData.width, imageData.height, this.currentPyramid.data[0]);
        this.currentPyramid.build(this.currentPyramid.data[0]);

        let bboxBorderWidth = 1;
        let pointsPerDimension = this.config.pointsPerDimension;
        let pointsPerObject = pointsPerDimension * pointsPerDimension;
        let pointsCountUpperBound = bboxes.length * pointsPerObject;
        let pointsStatus = new Uint8Array(pointsCountUpperBound);
        let previousPoints = new Float32Array(pointsCountUpperBound * 2);
        let currentPoints = new Float32Array(pointsCountUpperBound * 2);

        // Generate tracking points with better distribution
        let pointsCount = 0;
        for (let i = 0; i < bboxes.length; i++) {
            let bbox = bboxes[i];
            if (bbox != null) {
                pointsCount += this.generateTrackingPoints(bbox, previousPoints, pointsCount, pointsPerDimension);
            }
        }

        if (pointsCount == 0) {
            throw 'no points to track';
        }

        // Calculate adaptive search window
        const searchWindow = this.calculateAdaptiveSearchWindow(this.stats.lastTranslation);

        if (this.config.enableLogging) {
            console.log(`OpticalFlow: Tracking ${pointsCount} points with ${searchWindow}x${searchWindow} window`);
        }

        // Perform optical flow tracking
        jsfeat.optical_flow_lk.track(
            this.previousPyramid,
            this.currentPyramid,
            previousPoints,
            currentPoints,
            pointsCount,
            searchWindow,
            searchWindow,
            pointsStatus,
            0.01,
            0.001
        );

        // Process results with enhanced validation
        let newBboxes = [];
        let p = 0;
        let totalSuccessfulPoints = 0;
        let totalPoints = 0;

        for (let i = 0; i < bboxes.length; i++) {
            let bbox = bboxes[i];
            let newBbox = null;

            if (bbox != null) {
                const result = this.processBboxTracking(
                    bbox, previousPoints, currentPoints, pointsStatus,
                    p, pointsPerObject, imageData, bboxBorderWidth
                );

                newBbox = result.bbox;
                totalSuccessfulPoints += result.successfulPoints;
                totalPoints += pointsPerObject;
                p += pointsPerObject;

                if (this.config.enableLogging && result.diagnostics) {
                    console.log(`OpticalFlow bbox ${i}:`, result.diagnostics);
                }
            }

            newBboxes.push(newBbox);
        }

        // Update tracking statistics
        this.updateTrackingStats(totalSuccessfulPoints, totalPoints, newBboxes, bboxes);

        const processingTime = performance.now() - startTime;
        if (this.config.enableLogging) {
            console.log(`OpticalFlow: Processing took ${processingTime.toFixed(2)}ms, success rate: ${(this.stats.successfulPointsRatio * 100).toFixed(1)}%`);
        }

        // Swap current and previous pyramids
        let oldPyramid = this.previousPyramid;
        this.previousPyramid = this.currentPyramid;
        this.currentPyramid = oldPyramid;

        return newBboxes;
    }

    generateTrackingPoints(bbox, previousPoints, startIndex, pointsPerDimension) {
        let pointsCount = 0;

        // Generate uniform grid with better edge coverage
        for (let x = 0; x < pointsPerDimension; x++) {
            for (let y = 0; y < pointsPerDimension; y++) {
                const pointIndex = (startIndex + pointsCount) * 2;
                previousPoints[pointIndex] = bbox.x + x * (bbox.width / (pointsPerDimension - 1));
                previousPoints[pointIndex + 1] = bbox.y + y * (bbox.height / (pointsPerDimension - 1));
                pointsCount++;
            }
        }

        return pointsCount;
    }

    processBboxTracking(bbox, previousPoints, currentPoints, pointsStatus, startP, pointsPerObject, imageData, bboxBorderWidth) {
        let before = [];
        let after = [];
        let displacements = [];
        let successfulPoints = 0;

        // Collect successful tracking points
        for (let j = 0; j < pointsPerObject; j++) {
            const p = startP + j;
            if (pointsStatus[p] === 1) {
                const x = p * 2;
                const y = x + 1;

                const prevX = previousPoints[x];
                const prevY = previousPoints[y];
                const currX = currentPoints[x];
                const currY = currentPoints[y];

                before.push([prevX, prevY]);
                after.push([currX, currY]);

                const displacement = Math.sqrt((currX - prevX) ** 2 + (currY - prevY) ** 2);
                displacements.push(displacement);
                successfulPoints++;
            }
        }

        let newBbox = null;
        let diagnostics = null;

        if (before.length > 0) {
            // Try different transformation models
            const result = this.robustTransformationEstimation(before, after, displacements);

            if (result.confidence >= this.config.minConfidenceThreshold) {
                const translation = result.translation;

                const minX = Math.max(Math.round(bbox.x + translation[0]), 0);
                const minY = Math.max(Math.round(bbox.y + translation[1]), 0);
                const maxX = Math.min(Math.round(bbox.x + bbox.width + translation[0]), imageData.width - 2 * bboxBorderWidth);
                const maxY = Math.min(Math.round(bbox.y + bbox.height + translation[1]), imageData.height - 2 * bboxBorderWidth);
                const newWidth = maxX - minX;
                const newHeight = maxY - minY;

                if (newWidth > 0 && newHeight > 0) {
                    newBbox = {x: minX, y: minY, width: newWidth, height: newHeight};
                }
            }

            diagnostics = {
                successfulPoints: successfulPoints,
                totalPoints: pointsPerObject,
                successRate: (successfulPoints / pointsPerObject * 100).toFixed(1) + '%',
                confidence: result.confidence.toFixed(3),
                translation: result.translation.map(t => t.toFixed(2)),
                avgDisplacement: result.avgDisplacement.toFixed(2),
                used: newBbox !== null ? 'YES' : 'NO (fallback)'
            };
        }

        // Fallback strategy
        if (newBbox === null) {
            newBbox = {x: bbox.x, y: bbox.y, width: bbox.width, height: bbox.height};

            if (!diagnostics) {
                diagnostics = {
                    successfulPoints: 0,
                    totalPoints: pointsPerObject,
                    successRate: '0%',
                    confidence: 0,
                    translation: [0, 0],
                    avgDisplacement: 0,
                    used: 'NO (no valid points)'
                };
            }
        }

        return {
            bbox: newBbox,
            successfulPoints: successfulPoints,
            diagnostics: diagnostics
        };
    }

    robustTransformationEstimation(before, after, displacements) {
        if (before.length === 0) {
            return {
                translation: [0, 0],
                confidence: 0,
                avgDisplacement: 0
            };
        }

        // Calculate average displacement for quality assessment
        const avgDisplacement = displacements.reduce((sum, d) => sum + d, 0) / displacements.length;

        // Try translation-only first
        let diff = nudged.estimate('T', before, after);
        let translation = diff.getTranslation();

        // Calculate confidence based on consistency of point movements
        let confidence = this.calculateTrackingConfidence(before, after, translation);

        // If confidence is low, try with RANSAC-like outlier rejection
        if (confidence < this.config.minConfidenceThreshold && before.length > 4) {
            const result = this.ransacTransformationEstimation(before, after);
            if (result.confidence > confidence) {
                translation = result.translation;
                confidence = result.confidence;
            }
        }

        return {
            translation: translation,
            confidence: confidence,
            avgDisplacement: avgDisplacement
        };
    }

    calculateTrackingConfidence(before, after, translation) {
        if (before.length === 0) return 0;

        let totalError = 0;
        for (let i = 0; i < before.length; i++) {
            const expectedX = before[i][0] + translation[0];
            const expectedY = before[i][1] + translation[1];
            const actualX = after[i][0];
            const actualY = after[i][1];

            const error = Math.sqrt((expectedX - actualX) ** 2 + (expectedY - actualY) ** 2);
            totalError += error;
        }

        const avgError = totalError / before.length;
        const confidence = Math.max(0, 1 - (avgError / 20)); // Normalize error to confidence

        return confidence;
    }

    ransacTransformationEstimation(before, after) {
        const iterations = Math.min(50, before.length * 2);
        const threshold = 3.0; // Pixel threshold for inliers

        let bestTranslation = [0, 0];
        let bestConfidence = 0;
        let bestInlierCount = 0;

        for (let iter = 0; iter < iterations; iter++) {
            // Sample random points for estimation
            const sampleSize = Math.min(4, before.length);
            const indices = this.randomSample(before.length, sampleSize);

            const sampleBefore = indices.map(i => before[i]);
            const sampleAfter = indices.map(i => after[i]);

            const diff = nudged.estimate('T', sampleBefore, sampleAfter);
            const translation = diff.getTranslation();

            // Count inliers
            let inlierCount = 0;
            for (let i = 0; i < before.length; i++) {
                const expectedX = before[i][0] + translation[0];
                const expectedY = before[i][1] + translation[1];
                const actualX = after[i][0];
                const actualY = after[i][1];

                const error = Math.sqrt((expectedX - actualX) ** 2 + (expectedY - actualY) ** 2);
                if (error < threshold) {
                    inlierCount++;
                }
            }

            const confidence = inlierCount / before.length;
            if (confidence > bestConfidence) {
                bestConfidence = confidence;
                bestTranslation = translation;
                bestInlierCount = inlierCount;
            }
        }

        return {
            translation: bestTranslation,
            confidence: bestConfidence,
            inlierCount: bestInlierCount
        };
    }

    randomSample(arrayLength, sampleSize) {
        const indices = [];
        const available = Array.from({length: arrayLength}, (_, i) => i);

        for (let i = 0; i < sampleSize && available.length > 0; i++) {
            const randomIndex = Math.floor(Math.random() * available.length);
            indices.push(available.splice(randomIndex, 1)[0]);
        }

        return indices;
    }

    updateTrackingStats(successfulPoints, totalPoints, newBboxes, originalBboxes) {
        this.stats.successfulPointsRatio = totalPoints > 0 ? successfulPoints / totalPoints : 0;

        // Calculate average translation
        let totalTranslationX = 0;
        let totalTranslationY = 0;
        let validBboxes = 0;

        for (let i = 0; i < newBboxes.length; i++) {
            const newBbox = newBboxes[i];
            const originalBbox = originalBboxes[i];

            if (newBbox && originalBbox) {
                totalTranslationX += newBbox.x - originalBbox.x;
                totalTranslationY += newBbox.y - originalBbox.y;
                validBboxes++;
            }
        }

        if (validBboxes > 0) {
            this.stats.lastTranslation = [
                totalTranslationX / validBboxes,
                totalTranslationY / validBboxes
            ];
        }

        this.stats.lastTrackingQuality = this.stats.successfulPointsRatio;
        this.stats.averageDisplacement = Math.sqrt(
            this.stats.lastTranslation[0] ** 2 + this.stats.lastTranslation[1] ** 2
        );
    }
}

