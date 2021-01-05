<?php

/**
 * Plugin Name: Smeedijzer Internet - Publication Checklist
 * Plugin URI:  https://github.com/smeedijzer-internet
 * Description: Default checks for the Publication Checklist plugin (https://github.com/humanmade/publication-checklist)
 * Version:     1.0.0
 * Author:      Smeedijzer Internet
 * Author URI:  https://github.com/smeedijzer-internet
 * Licence:     MIT
 */

namespace Altis\Workflow\ChecklistItems;

use Altis\Workflow\PublicationChecklist as Checklist;
use Altis\Workflow\PublicationChecklist\Status;

function bootstrap() {

    // Checks featured image size
    add_action( 'altis.publication-checklist.register_prepublish_checks', __NAMESPACE__ . '\\register_featured_image_check' );

	// Add a caption/alt to solve
	add_action( 'altis.publication-checklist.register_prepublish_checks', __NAMESPACE__ . '\\register_image_texts_check' );

	// Checking meta:
	add_action( 'altis.publication-checklist.register_prepublish_checks', __NAMESPACE__ . '\\register_seo_title_check' );

    // This item is always completed:
    //add_action( 'altis.publication-checklist.register_prepublish_checks', __NAMESPACE__ . '\\register_social_headline_check' );

    // This is optional:
    //add_action( 'altis.publication-checklist.register_prepublish_checks', __NAMESPACE__ . '\\register_video_check' );

    // This checks tags:
    //add_action( 'altis.publication-checklist.register_prepublish_checks', __NAMESPACE__ . '\\register_tags_check' );
}


/**
 * Check for featured image on posts.
 */
function register_featured_image_check() {
    Checklist\register_prepublish_check(
        'featured_image',
        array(
            'type'      => array(
                'post',
                'page',
                'verhalen',
                'inspiratie',
                get_post_types([], 'names')
            ),
            'run_check' => function ( array $post, array $meta, array $terms ) : Status {

                if ( ! has_post_thumbnail() ) {
                    add_filter( 'altis.publication-checklist.block_on_failing', '__return_true' );
                    return new Status( Status::INCOMPLETE, __( 'Voeg uitgelichte afbeelding toe', 'smdzr' ) );
                } else {

                    $img    = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );

                    $width  = $img[1];
                    $height = $img[2];

                    $required_width  = 1100;
                    $required_height = 800;

                    $filetype = wp_check_filetype( wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0] );
    
                    if ( $width >= $required_width && $height >= $required_height ) {
                        return new Status( Status::COMPLETE, __( 'Add a featured image of at least 1200x630px', 'Lf_Mu' ) );
                    } else {
                        add_filter( 'altis.publication-checklist.block_on_failing', '__return_true' );
                        return new Status( Status::INCOMPLETE, __( 'Uitgelichte afbeelding moet minimaal', 'Lf_Mu' ) );
                    }
                 
//                    if ( has_category( 'news' ) ) {
//                        return new Status( Status::COMPLETE, __( 'Add a featured image to the post', 'Lf_Mu' ) );
//                    } else {
//
//                        if ( 'svg' == $filetype['ext'] ) {
//                            return new Status( Status::INCOMPLETE, __( 'Add a featured image that is not an SVG (SVGs only work for "News" posts)', 'Lf_Mu' ) );
//                        } else {
//                            if ( $width >= $required_width && $height >= $required_height ) {
//                                return new Status( Status::COMPLETE, __( 'Add a featured image of at least 1200x630px', 'Lf_Mu' ) );
//                            } else {
//                                return new Status( Status::INCOMPLETE, __( 'Add a featured image of at least 1200x630px', 'Lf_Mu' ) );
//                            }
//                        }
//                    }
                }

                return new Status( Status::INCOMPLETE, __( 'Add a featured image to begin checks', 'Lf_Mu' ) );
            },
        )
    );
}

function register_image_texts_check() {
	$image_block_names = [
		'core/cover',
		'core/gallery',
		'core/image',
		'core/media-text',
	];

	$check_image = function ( $image, ...$keys ) {
		$matching = array_filter( $keys, function ( $key ) use ( $image ) {
			return strlen( $image['attributes'][ $key ] ?? '' ) > 0;
		} );
		return count( $matching ) > 0;
	};

	$check_block = function ( $block ) use ( $check_image ) {
		switch ( $block['blockName'] ) {
			case 'core/cover': {
				$background_type = $block['attributes']['backgroundType'] ?? '';
				if ( $background_type !== 'image' ) {
					return true;
				}

				// As of now, the Cover block doesn't support alt texts or captions.
				return true;
			}

			case 'core/gallery': {
				$images = $block['attributes']['images'] ?? [];

				$matches = array_filter( $images, function ( $image ) use ( $check_image ) {
					return $check_image( $image, 'alt', 'caption' );
				} );
				return count( $matches ) > 0;
			}

			case 'core/image':
				return (bool) preg_match( '#<figcaption>.+?</figcaption>#i', $block['innerHTML'] );

			case 'core/media-text': {
				$mediaType = $block['attributes']['mediaType'];
				if ( $mediaType !== 'image' ) {
					return true;
				}

				// Only check the alt text as the Media & Text block doesn't support captions.
				return $check_image( $block, 'mediaAlt' );
			}

			default:
		}

		return false;
	};

	Checklist\register_prepublish_check( 'image-texts', [
		'type' => [
			'post',
			'page',
		],
		'run_check' => function ( array $post ) use ( $image_block_names, $check_block ) : Status {
			$blocks = parse_blocks( $post['post_content'] );
			$image_blocks = array_filter( $blocks, function ( $block ) use ( $image_block_names ) {
				return in_array( $block['blockName'], $image_block_names, true );
			} );
			$failing = array_filter( $image_blocks, function ( $value ) use ( $check_block ) {
				return $check_block( $value ) !== true;
			} );

			if ( count( $failing ) === 0 ) {
				return new Status( Status::COMPLETE, __( 'Add image alt text or caption', 'altis-demo' ) );
			}

			$block = array_values( $failing )[0];
			return new Status( Status::INCOMPLETE, __( 'Add image alt text or caption', 'altis-demo' ), $block );
		},
	] );
}

function register_seo_title_check() {
	Checklist\register_prepublish_check( 'seo-title', [
		'type' => [
			'post',
			'page',
		],
		'run_check' => function ( array $post, array $meta ) : Status {
			$meta_title = $meta['_meta_title'] ?? [];
			$status = ( count( $meta_title ) !== 1 || empty( $meta_title[0] ) ) ? Status::INCOMPLETE : Status::COMPLETE;

			return new Status( $status, 'Add a custom SEO title' );
		}
	] );
}

function register_social_headline_check() {
	Checklist\register_prepublish_check( 'social-headline', [
		'run_check' => function () : Status {
			return new Status( Status::COMPLETE, __( 'Adjust social headline length', 'altis-demo' ) );
		}
	] );
}

function register_video_check() {
	$video_block_names = [
		'core/video',
		'core-embed/videopress',
		'core-embed/vimeo',
		'core-embed/youtube',
	];

	Checklist\register_prepublish_check( 'video', [
		'run_check' => function ( array $post ) use ( $video_block_names ) : Status {
			$blocks = parse_blocks( $post['post_content'] );
			$video_blocks = array_filter( $blocks, function ( $block ) use ( $video_block_names ) {
				return in_array( $block['blockName'], $video_block_names, true );
			} );

			if ( count( $video_blocks ) > 0 ) {
				return new Status( Status::COMPLETE, __( 'Add a video to the post', 'altis-demo' ) );
			}

			return new Status( STATUS::INFO, __( 'Add a video to the post', 'altis-demo' ) );
		},
	] );
}

function register_tags_check() {
	Checklist\register_prepublish_check( 'tags', [
		'run_check' => function ( array $post, array $meta, array $terms ) : Status {
			if ( empty( $terms['post_tag'] ) ) {
				return new Status( Status::INCOMPLETE, __( 'Add tags to the post', 'altis-demo' ) );
			}

			return new Status( Status::COMPLETE, __( 'Add tags to the post', 'altis-demo' ) );
		}
	] );
}
