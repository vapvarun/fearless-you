/**
 * LearnDash Progress Rings Implementation
 * Creates circular progress indicators for lessons and topics
 * @param $
 */

(function ($) {
	'use strict';

	// Initialize on document ready
	$(document).ready(function () {
		// Check if custom styles are enabled
		if (
			window.fliLearnDashSettings &&
			window.fliLearnDashSettings.showProgress === 'yes'
		) {
			initProgressRings();
			initQuizDisplay();
		}
	});

	/**
	 * Initialize progress rings for lesson/topic items
	 */
	function initProgressRings() {
		// Find all lesson and topic items
		const lessonItems = $(
			'.ld-item-list-item, .ld-table-list-item, .ld-lesson-item'
		);

		lessonItems.each(function () {
			const $item = $(this);
			const $statusIcon = $item.find('.ld-status-icon, .ld-status');

			// Determine the status
			let status = 'not_started';
			let progress = 0;
			let quizScore = null;

			if ($statusIcon.hasClass('ld-status-complete')) {
				status = 'completed';
				progress = 100;
			} else if ($statusIcon.hasClass('ld-status-in-progress')) {
				status = 'in_progress';
				// Get actual progress percentage if available
				const progressText = $item
					.find('.ld-progress-percentage')
					.text();
				if (progressText) {
					progress = parseInt(progressText);
				} else {
					progress = 50; // Default to 50% if no specific progress
				}
			}

			// Check for quiz score
			const $quizScore = $item.find('.ld-quiz-score, .quiz-score');
			if ($quizScore.length) {
				quizScore = parseInt($quizScore.text());
			}

			// Create progress ring
			createProgressRing($item, status, progress, quizScore);
		});
	}

	/**
	 * Create a progress ring for a lesson/topic item
	 * @param $item
	 * @param status
	 * @param progress
	 * @param quizScore
	 */
	function createProgressRing($item, status, progress, quizScore) {
		// Remove existing progress indicators
		$item.find('.ld-progress-ring-wrapper').remove();

		// Create wrapper
		const $wrapper = $('<div class="ld-progress-ring-wrapper"></div>');

		if (quizScore !== null) {
			// Display quiz score
			const passThreshold = window.fliLearnDashSettings
				? window.fliLearnDashSettings.quizPass
				: 70;
			const scoreClass =
				quizScore >= passThreshold ? 'score-high' : 'score-low';
			$wrapper.html(`
                <div class="ld-progress-ring quiz-score ${scoreClass}">
                    <span>${quizScore}%</span>
                </div>
            `);
		} else if (status === 'completed') {
			// Display checkmark for completed items
			$wrapper.html(`
                <div class="ld-progress-ring completed">
                    <svg viewBox="0 0 24 24" class="checkmark">
                        <path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    </svg>
                </div>
            `);
		} else {
			// Display progress ring
			const circumference = 138.23; // 2 * Math.PI * 22 (radius)
			const strokeDashoffset =
				circumference - (circumference * progress) / 100;

			$wrapper.html(`
                <div class="ld-progress-ring ${status}">
                    <svg width="50" height="50">
                        <circle class="ring-background" cx="25" cy="25" r="22" stroke-width="3" fill="none"/>
                        <circle class="ring-progress" cx="25" cy="25" r="22" stroke-width="3" fill="none"
                                stroke-dasharray="${circumference}"
                                stroke-dashoffset="${strokeDashoffset}"/>
                    </svg>
                    ${status === 'in_progress' ? `<span class="progress-text">${progress}%</span>` : ''}
                </div>
            `);
		}

		// Add to item
		$item.addClass(`ld-item-${status}`);
		$item.attr('data-status', status);
		if (quizScore !== null) {
			$item.attr('data-quiz-score', quizScore);
			$item.attr('data-quiz-passed', quizScore >= 70 ? 'true' : 'false');
		}

		// Position the ring
		if ($item.find('.ld-item-title, .ld-topic-title').length) {
			$item.find('.ld-item-title, .ld-topic-title').append($wrapper);
		} else {
			$item.append($wrapper);
		}
	}

	/**
	 * Initialize quiz display enhancements
	 */
	function initQuizDisplay() {
		// Add question counter
		const $quizQuestions = $('.wpProQuiz_questionList');
		if ($quizQuestions.length) {
			const currentQuestion = 1;
			const totalQuestions = $quizQuestions.find(
				'.wpProQuiz_listItem'
			).length;

			// Add progress bar
			const $progressBar = $(`
                <div class="quiz-progress-wrapper">
                    <div class="question-counter">${currentQuestion}/${totalQuestions}</div>
                    <div class="quiz-progress">
                        <div class="quiz-progress-bar" style="width: ${(currentQuestion / totalQuestions) * 100}%"></div>
                    </div>
                </div>
            `);

			$quizQuestions.prepend($progressBar);

			// Add letter indicators to answer options
			$('.wpProQuiz_questionListItem li').each(function (index) {
				const letter = String.fromCharCode(65 + index); // A, B, C, D...
				$(this).attr('data-letter', letter);
			});
		}

		// Style quiz container
		$('.wpProQuiz_content').addClass('ldvc-styled-quiz');

		// Add skip button if needed
		const $quizHeader = $('.wpProQuiz_header');
		if (
			$quizHeader.length &&
			!$quizHeader.find('.quiz-skip-button').length
		) {
			$quizHeader.append(
				'<button class="quiz-skip-button">Skip</button>'
			);
		}

		// Handle answer selection
		$('.wpProQuiz_questionListItem li').on('click', function () {
			$(this).siblings().removeClass('selected');
			$(this).addClass('selected');
		});
	}

	/**
	 * Update progress ring dynamically (for AJAX updates)
	 * @param itemId
	 * @param status
	 * @param progress
	 * @param quizScore
	 */
	window.updateLDProgressRing = function (
		itemId,
		status,
		progress,
		quizScore
	) {
		const $item = $(`[data-ld-item-id="${itemId}"], #post-${itemId}`);
		if ($item.length) {
			createProgressRing($item, status, progress, quizScore);
		}
	};

	// Listen for LearnDash completion events
	$(document).on('learndash_lesson_complete', function (e, data) {
		if (data.lesson_id) {
			updateLDProgressRing(data.lesson_id, 'completed', 100, null);
		}
	});

	$(document).on('learndash_topic_complete', function (e, data) {
		if (data.topic_id) {
			updateLDProgressRing(data.topic_id, 'completed', 100, null);
		}
	});

	$(document).on('learndash_quiz_complete', function (e, data) {
		if (data.quiz_id && data.score !== undefined) {
			const itemId = data.lesson_id || data.topic_id;
			if (itemId) {
				updateLDProgressRing(itemId, 'completed', 100, data.score);
			}
		}
	});
})(jQuery);
