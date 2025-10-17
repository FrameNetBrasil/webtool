function sessionTrackerComponent(idDocumentSentence) {
    return {
        isActive: false,
        sessionToken: null,
        startedAt: null,
        lastHeartbeat: null,
        duration: 0,
        heartbeatInterval: null,
        heartbeatLogs: [],
        durationInterval: null,
        idDocumentSentence: null,

        async init() {
            // Check for existing session on load
            //this.checkSessionStatus();

            console.log('init',idDocumentSentence);

            // Handle page unload/reload
            window.addEventListener('beforeunload', () => {
                console.log('beforeonload');
                if (this.isActive) {
                    console.log('is visible ');
                    // Use sendBeacon for reliable delivery during page unload
                    navigator.sendBeacon(
                        '/annotation/session/end',
                        new URLSearchParams({
                            idDocumentSentence: this.idDocumentSentence,
                            // session_token: this.sessionToken,
                            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        })
                    );
                }
            });

            // Handle visibility changes (tab switching, minimizing)
            document.addEventListener('visibilitychange', () => {
                if (document.hidden && this.isActive) {
                    // Page became hidden - could pause heartbeats or end session
                    this.addLog('Page hidden - continuing heartbeats');
                } else if (!document.hidden && this.isActive) {
                    // Page became visible again
                    this.addLog('Page visible again');
                }
            });

            await this.startSession(idDocumentSentence);
        },

        async checkSessionStatus() {
            try {
                const response = await fetch('/annotation-session/status', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                });

                const data = await response.json();

                if (data.has_active_session) {
                    this.isActive = true;
                    this.sessionToken = data.session_token;
                    this.startedAt = new Date(data.started_at).toLocaleTimeString();
                    this.lastHeartbeat = new Date(data.last_heartbeat).toLocaleTimeString();
                    this.startHeartbeat();
                    this.startDurationCounter();
                    this.addLog('Resumed existing session');
                }
            } catch (error) {
                this.addLog('Error checking session status: ' + error.message);
            }
        },

        async startSession(idDocumentSentence) {
            try {
                this.idDocumentSentence = idDocumentSentence;
                const response = await fetch('/annotation/session/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        idDocumentSentence
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.isActive = true;
                    this.sessionToken = data.session_token;
                    this.startedAt = new Date(data.startedAt).toLocaleTimeString();
                    this.lastHeartbeat = this.startedAt;
                    this.duration = 0;
                    // this.startHeartbeat();
                    // this.startDurationCounter();
                    messenger.notify('warning','Session started successfully');
                    this.addLog('Session started successfully');
                } else {
                    this.addLog('Failed to start session');
                }
            } catch (error) {
                this.addLog('Error starting session: ' + error.message);
            }
        },

        async endSession(idDocumentSentence) {
            try {
                const response = await fetch('/annotation/session/end', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        idDocumentSentence
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // this.stopHeartbeat();
                    // this.stopDurationCounter();
                    this.isActive = false;
                    this.sessionToken = null;
                    this.startedAt = null;
                    this.lastHeartbeat = null;
                    this.duration = 0;
                    this.addLog(`Session ended. Total duration: ${data.duration} seconds`);
                } else {
                    this.addLog('Failed to end session: ' + data.message);
                }
            } catch (error) {
                this.addLog('Error ending session: ' + error.message);
            }
        },

        startHeartbeat() {
            // Send heartbeat every 30 seconds
            this.heartbeatInterval = setInterval(async () => {
                await this.sendHeartbeat();
            }, 30000);
        },

        stopHeartbeat() {
            if (this.heartbeatInterval) {
                clearInterval(this.heartbeatInterval);
                this.heartbeatInterval = null;
            }
        },

        startDurationCounter() {
            this.durationInterval = setInterval(() => {
                this.duration++;
            }, 1000);
        },

        stopDurationCounter() {
            if (this.durationInterval) {
                clearInterval(this.durationInterval);
                this.durationInterval = null;
            }
        },

        async sendHeartbeat() {
            if (!this.isActive || !this.sessionToken) return;

            try {
                const response = await fetch('/annotation-session/heartbeat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        session_token: this.sessionToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.lastHeartbeat = new Date(data.last_heartbeat).toLocaleTimeString();
                    this.addLog('Heartbeat sent successfully');
                } else {
                    this.addLog('Heartbeat failed: ' + data.message);
                    // Session might be expired, check status
                    this.checkSessionStatus();
                }
            } catch (error) {
                this.addLog('Heartbeat error: ' + error.message);
            }
        },

        addLog(message) {
            const timestamp = new Date().toLocaleTimeString();
            this.heartbeatLogs.unshift({
                timestamp,
                message: `[${timestamp}] ${message}`
            });

            // Keep only last 10 logs
            if (this.heartbeatLogs.length > 10) {
                this.heartbeatLogs = this.heartbeatLogs.slice(0, 10);
            }
        }
    };
}
