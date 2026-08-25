import api from './api';

export async function trackEngagement(event_key, event_source = null, metadata = null) {
    try {
        await api.post('/engagement/events', {
            event_key,
            event_source,
            metadata,
        });
    } catch {
        // Non-blocking: engagement tracking should never interrupt user flows.
    }
}
