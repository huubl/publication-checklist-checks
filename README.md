# Demo Publication Checklist

Demo items for the [Publication Checklist](https://github.com/humanmade/publication-checklist) plugin and [Altis feature](https://www.altis-dxp.com/resources/docs/workflow/publication-checklist/).

This plugin includes three demo items:

* **Social headline**: This check always passes.
* **Alt text/caption**: Checks if any images are missing alt text or captions. Add text to make this pass.
* **Video**: Checks if the content contains any videos. Add a video block (or video embed from VideoPress, Vimeo, or YouTube) to make this pass.
* **Tags**: Checks if the post has tags. Add a tag to make this pass.

    //For Posts (Blog Category Only)

    //Rules:

    //Add featured image
    //More than 500 words
    //No H1 tags
    //Featured image at least 1200x630 (or some feedback around that)
    //
    //Required rules:
    //Featured image bigger than 540x285
    
    
    
// Check for video in case studies.
// add_action( 'altis.publication-checklist.register_prepublish_checks', 'video_present' ); // phpcs:ignore.

// checks for topics on webinar.
// add_action( 'altis.publication-checklist.register_prepublish_checks', 'webinar_topics' ); // phpcs:ignore.

/**
 * Check for video present (not required).
 */
function video_present() {
	$video_block_names = array(
		'core/video',
		'core-embed/videopress',
		'core-embed/vimeo',
		'core-embed/youtube',
	);

	Checklist\register_prepublish_check(
		'video',
		array(
			'type'      => array(
				'lf_case_study',
				'lf_case_study_cn',
			),
			'run_check' => function ( array $post ) use ( $video_block_names ) : Status {
				$blocks       = parse_blocks( $post['post_content'] );
				$video_blocks = array_filter(
					$blocks,
					function ( $block ) use ( $video_block_names ) {
						return in_array( $block['blockName'], $video_block_names, true );
					}
				);

				if ( count( $video_blocks ) > 0 ) {
					return new Status( Status::COMPLETE, __( 'Added a video to the case study', 'Lf_Mu' ) );
				}

				return new Status( STATUS::INFO, __( 'Add a video to the case study', 'Lf_Mu' ) );
			},
		)
	);
}

        add_action(
            'altis.publication-checklist.register_prepublish_checks',
            function () {
                register_prepublish_check(
                    'title_length',
                    [
                        'type' => 'post',
                        'run_check' => function ( array $post, array $meta ): Status {
                            if ( strlen( $post['post_title'] ) <= 80 ) {
                                return new Status( Status::INCOMPLETE, 'Post title is too short' );
                            }

                            return new Status( Status::COMPLETE, 'Title long enough' );

                        },
                    ]
                );

                register_prepublish_check(
                    'title',
                    array(
                        'type' => 'post',
                        //'type' => 'article',
                        'run_check' => function( array $post, array $meta ) : Status {
                            if ( isset( $post['post_title'] ) && '' !== $post['post_title'] ) {
                                return new Status( Status::COMPLETE, 'Title is set' );
                            }

                            return new Status( Status::INCOMPLETE, 'Missing title' );
                        },
                    )
                );

            }
        );

        add_filter( 'altis.publication-checklist.block_on_failing', '__return_true' );

/**
 * Check topics on webinar.
 */
function webinar_topics() {
	Checklist\register_prepublish_check(
		'webinar_topic_check',
		array(
			'type'      => array(
				'lf_webinar',
			),
			'run_check' => function ( array $post, array $meta, array $terms ) : Status {

				if ( empty( $terms['lf-topic'] ) ) {
					return new Status( Status::INCOMPLETE, __( 'Add topics to the webinar', 'Lf_Mu' ) );
				}
				return new Status( Status::COMPLETE, __( 'Add topics to the webinar', 'Lf_Mu' ) );

			},
		)
	);
}

