/**
 * Relevanssi Voice Search
 * 
 * Handles the Web Speech API integration for frontend search forms.
 */
(function () {
    'use strict';

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        return;
    }

    const siteLanguage = typeof relevanssiVoiceData !== 'undefined' ? relevanssiVoiceData.language : 'en-US';
    const micLabel = typeof relevanssiVoiceData !== 'undefined' ? relevanssiVoiceData.mic_label : 'Search by voice';
    const isDebugMode = typeof relevanssiVoiceData !== 'undefined' && relevanssiVoiceData.debug === 'on';

    /**
     * Custom logger that only fires when Relevanssi Debug Mode is on.
     */
    const debugLog = (message, ...args) => {
        if (isDebugMode) {
            console.log(`[Relevanssi Voice]: ${message}`, ...args);
        }
    };

    const bindSpeechAPI = (searchInput, micButton) => {
        const recognition = new SpeechRecognition();

        recognition.lang = siteLanguage;
        recognition.interimResults = true;
        recognition.maxAlternatives = 1;

        let usingLocal = false;

        if ('processLocally' in recognition) {
            recognition.processLocally = true;
            usingLocal = true;
            debugLog("Attempting local processing.");
        }

        micButton.addEventListener('click', (event) => {
            event.preventDefault();

            if (micButton.classList.contains('is-active')) {
                debugLog("User aborted search.");
                recognition.abort();
            } else {
                debugLog("Microphone clicked. Starting recognition...");
                recognition.start();
                micButton.classList.add('is-active');
            }
        });

        recognition.onspeechstart = () => {
            debugLog("Speech detected. Pulsing UI...");
            micButton.classList.add('is-talking');
        };

        recognition.onresult = (event) => {
            let finalTranscript = '';
            let confidence = 0;

            for (let i = event.resultIndex; i < event.results.length; ++i) {
                const alternative = event.results[i][0];
                finalTranscript += alternative.transcript;
                confidence = alternative.confidence;
            }

            debugLog(`Transcript: "${finalTranscript}" (Confidence: ${confidence.toFixed(2)})`);

            // Adjust value if needed.
            if (confidence < 0.25) {
                debugLog("Confidence too low. Ignoring result.");
                return;
            }

            searchInput.value = finalTranscript;

            searchInput.dataset.lastConfidence = confidence;
        };

        recognition.onerror = (event) => {
            console.error("[Relevanssi Voice API Error]:", event.error);

            if (event.error === 'language-not-supported' && usingLocal) {
                console.warn('[Relevanssi Voice]: Local language pack missing. Falling back to cloud.');
                recognition.processLocally = false;
                usingLocal = false;
                recognition.start();
                return;
            }
            micButton.classList.remove('is-talking');
            micButton.classList.remove('is-active');

            if (event.error === 'not-allowed' || event.error === 'network') {
                micButton.disabled = true;
                let titleMessage = relevanssiVoiceData.error_msg;
                if (event.error === 'network') {
                    titleMessage = "Voice search failed to connect in this browser.";
                }
                micButton.setAttribute('title', titleMessage);
            }
        };

        recognition.onspeechend = () => {
            debugLog("Speech ended.");
            micButton.classList.remove('is-talking');
            recognition.stop();
        };

        recognition.onend = () => {
            debugLog("Recognition session ended.");
            micButton.classList.remove('is-active');

            const isAutoSubmit = relevanssiVoiceData.auto_submit === 'on';

            if (isAutoSubmit && searchInput.value.trim().length > 0) {
                debugLog("Auto-submitting form...");
                if (searchInput.form) {
                    searchInput.form.submit();
                } else if (searchInput.closest('form')) {
                    searchInput.closest('form').submit();
                }
            }
        }
    };

    const setupVoiceSearchUI = () => {
        const searchElements = document.querySelectorAll('input[name="s"], input[type="search"]');

        if (searchElements.length > 0) {
            debugLog(`Found ${searchElements.length} search inputs.`);
        }

        searchElements.forEach((searchElement) => {
            const wrapper = document.createElement('div');
            wrapper.classList.add('relevanssi-voice-search-wrapper');

            searchElement.parentNode.insertBefore(wrapper, searchElement);
            wrapper.appendChild(searchElement);

            const micBtn = document.createElement('button');
            micBtn.classList.add('relevanssi-mic-button');
            micBtn.type = 'button';
            micBtn.setAttribute('aria-label', micLabel);

            /*
            * SVG Icon Credit:
            * "Microphone Slash Alt 1" from the Dazzle Line Icons collection by Dazzle UI.
            * License: CC BY (https://creativecommons.org/licenses/by/4.0/)
            * Changes: Added custom Relevanssi CSS classes for colors / animations.
            */
            micBtn.innerHTML = `
                <svg class="relevanssi-svg-mic-open" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 10V12C19 15.866 15.866 19 12 19M5 10V12C5 15.866 8.13401 19 12 19M12 19V22M8 22H16M12 15C10.3431 15 9 13.6569 9 12V5C9 3.34315 10.3431 2 12 2C13.6569 2 15 3.34315 15 5V12C15 13.6569 13.6569 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg class="relevanssi-svg-mic-blocked" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 9.4V5C15 3.34315 13.6569 2 12 2C10.8224 2 9.80325 2.67852 9.3122 3.66593M12 19V22M8 22H16M3 3L21 21M5.00043 10C5.00043 10 3.50062 19 12.0401 19C14.51 19 16.1333 18.2471 17.1933 17.1768M19.0317 13C19.2365 11.3477 19 10 19 10M12 15C10.3431 15 9 13.6569 9 12V9L14.1226 14.12C13.5796 14.6637 12.8291 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            `;

            wrapper.appendChild(micBtn);
            bindSpeechAPI(searchElement, micBtn);
        });
    };

    document.addEventListener('DOMContentLoaded', setupVoiceSearchUI);

})();