#!/usr/bin/env bash

# =====================================================
# Production Build Script for Webtool 4.2
# =====================================================
# This script builds the production Docker image with
# proper tagging and optional registry push
# =====================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="webtool"
DEFAULT_VERSION=$(date +%Y%m%d-%H%M%S)
DOCKERFILE="Dockerfile.production"

# Print colored message
print_msg() {
    color=$1
    shift
    echo -e "${color}$@${NC}"
}

print_header() {
    echo ""
    print_msg "${BLUE}" "=========================================="
    print_msg "${BLUE}" "$@"
    print_msg "${BLUE}" "=========================================="
    echo ""
}

print_success() {
    print_msg "${GREEN}" "✓ $@"
}

print_error() {
    print_msg "${RED}" "✗ $@"
}

print_warning() {
    print_msg "${YELLOW}" "⚠ $@"
}

# Usage information
usage() {
    cat << EOF
Usage: $0 [OPTIONS]

Build production Docker image for Webtool 4.2

OPTIONS:
    -v, --version VERSION    Image version tag (default: timestamp)
    -r, --registry REGISTRY  Docker registry URL (optional)
    -p, --push              Push image to registry after build
    -l, --latest            Also tag as 'latest'
    -h, --help              Show this help message

EXAMPLES:
    # Build with automatic timestamp version
    $0

    # Build with specific version
    $0 --version 4.2.1

    # Build and tag as latest
    $0 --version 4.2.1 --latest

    # Build and push to registry
    $0 --version 4.2.1 --registry registry.example.com --push

EOF
    exit 0
}

# Parse command line arguments
VERSION=""
REGISTRY=""
PUSH_IMAGE=false
TAG_LATEST=false

while [[ $# -gt 0 ]]; do
    case $1 in
        -v|--version)
            VERSION="$2"
            shift 2
            ;;
        -r|--registry)
            REGISTRY="$2"
            shift 2
            ;;
        -p|--push)
            PUSH_IMAGE=true
            shift
            ;;
        -l|--latest)
            TAG_LATEST=true
            shift
            ;;
        -h|--help)
            usage
            ;;
        *)
            print_error "Unknown option: $1"
            usage
            ;;
    esac
done

# Set version (use provided or default)
VERSION=${VERSION:-$DEFAULT_VERSION}

# Build image name
if [ -n "$REGISTRY" ]; then
    IMAGE_NAME="${REGISTRY}/${APP_NAME}"
else
    IMAGE_NAME="${APP_NAME}"
fi

IMAGE_TAG="${IMAGE_NAME}:${VERSION}"
IMAGE_LATEST="${IMAGE_NAME}:latest"

# Pre-flight checks
print_header "Pre-flight Checks"

# Check if Dockerfile exists
if [ ! -f "$DOCKERFILE" ]; then
    print_error "Dockerfile not found: $DOCKERFILE"
    exit 1
fi
print_success "Found $DOCKERFILE"

# Check if docker is available
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed or not in PATH"
    exit 1
fi
print_success "Docker is available"

# Check if docker daemon is running
if ! docker info &> /dev/null; then
    print_error "Docker daemon is not running"
    exit 1
fi
print_success "Docker daemon is running"

# Display build information
print_header "Build Information"
echo "App Name:     $APP_NAME"
echo "Version:      $VERSION"
echo "Image Tag:    $IMAGE_TAG"
if [ "$TAG_LATEST" = true ]; then
    echo "Latest Tag:   $IMAGE_LATEST"
fi
if [ -n "$REGISTRY" ]; then
    echo "Registry:     $REGISTRY"
fi
if [ "$PUSH_IMAGE" = true ]; then
    echo "Push:         Yes"
fi
echo ""

# Confirm build
read -p "Continue with build? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_warning "Build cancelled"
    exit 0
fi

# Build the image
print_header "Building Production Image"
print_msg "${YELLOW}" "This may take several minutes..."
echo ""

BUILD_START=$(date +%s)

docker build \
    --file "$DOCKERFILE" \
    --tag "$IMAGE_TAG" \
    --build-arg WWWUSER=$(id -u) \
    --build-arg WWWGROUP=$(id -g) \
    --progress=plain \
    .

BUILD_END=$(date +%s)
BUILD_DURATION=$((BUILD_END - BUILD_START))

print_success "Image built successfully in ${BUILD_DURATION}s"
print_success "Tagged as: $IMAGE_TAG"

# Tag as latest if requested
if [ "$TAG_LATEST" = true ]; then
    print_msg "${YELLOW}" "Tagging as latest..."
    docker tag "$IMAGE_TAG" "$IMAGE_LATEST"
    print_success "Tagged as: $IMAGE_LATEST"
fi

# Display image information
print_header "Image Information"
docker images "$IMAGE_NAME" --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}"
echo ""

# Push to registry if requested
if [ "$PUSH_IMAGE" = true ]; then
    if [ -z "$REGISTRY" ]; then
        print_error "Cannot push: No registry specified"
        exit 1
    fi

    print_header "Pushing to Registry"

    # Push versioned tag
    print_msg "${YELLOW}" "Pushing $IMAGE_TAG..."
    docker push "$IMAGE_TAG"
    print_success "Pushed: $IMAGE_TAG"

    # Push latest tag if it exists
    if [ "$TAG_LATEST" = true ]; then
        print_msg "${YELLOW}" "Pushing $IMAGE_LATEST..."
        docker push "$IMAGE_LATEST"
        print_success "Pushed: $IMAGE_LATEST"
    fi
fi

# Final summary
print_header "Build Complete"
print_success "Production image ready: $IMAGE_TAG"
echo ""
print_msg "${BLUE}" "Next steps:"
echo "  1. Test the image locally:"
echo "     docker-compose -f docker-compose.prod.yml up -d"
echo ""
echo "  2. Deploy to production:"
echo "     - Update APP_VERSION=$VERSION in .env"
echo "     - Run: docker-compose -f docker-compose.prod.yml up -d"
echo ""
if [ "$PUSH_IMAGE" = false ] && [ -n "$REGISTRY" ]; then
    echo "  3. To push to registry:"
    echo "     $0 --version $VERSION --registry $REGISTRY --push"
    echo ""
fi
