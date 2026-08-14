<?php
/**
 * Custom-combination limits and keyboard-token normalization.
 *
 * @package NetmilkStudio\Sesamo
 */

namespace NetmilkStudio\Sesamo;

defined( 'ABSPATH' ) || exit;

final class Combinations {
	public const MAX_CUSTOM = 20;
	public const MIN_KEYS   = 2;
	public const MAX_KEYS   = 64;

	/**
	 * Normalize an array or whitespace-separated string of KeyboardEvent.key tokens.
	 *
	 * Any invalid token rejects the complete sequence. Silently removing a token
	 * could turn malformed input into a different, unexpectedly valid shortcut.
	 *
	 * @param mixed $candidate Candidate sequence.
	 * @return string[]
	 */
	public static function normalize_sequence( $candidate ): array {
		if ( is_string( $candidate ) ) {
			if ( strlen( $candidate ) > 2048 ) {
				return array();
			}
			$candidate = trim( $candidate );
			$candidate = '' === $candidate ? array() : preg_split( '/\s+/u', $candidate );
		}

		if ( ! is_array( $candidate ) || count( $candidate ) < self::MIN_KEYS || count( $candidate ) > self::MAX_KEYS ) {
			return array();
		}

		$sequence = array();
		foreach ( $candidate as $token ) {
			if ( ! is_string( $token ) ) {
				return array();
			}

			$normalized = self::normalize_token( $token );
			if ( '' === $normalized ) {
				return array();
			}

			$sequence[] = $normalized;
		}

		return $sequence;
	}

	/** Normalize one supported KeyboardEvent.key token or return an empty string. */
	public static function normalize_token( string $token ): string {
		if ( strlen( $token ) > 128 ) {
			return '';
		}
		$token = trim( $token );
		if ( '' === $token ) {
			return '';
		}

		$named_keys = self::named_keys();
		$lookup     = array_change_key_case( array_flip( $named_keys ), CASE_LOWER );
		$lower      = strtolower( $token );
		if ( isset( $lookup[ $lower ] ) ) {
			return $named_keys[ $lookup[ $lower ] ];
		}

		// Exactly one printable, non-separator Unicode code point.
		if ( 1 !== preg_match( '/^[^\p{C}\p{Z}]$/u', $token ) ) {
			return '';
		}

		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $token, 'UTF-8' ) : strtolower( $token );
	}

	/** Return a stable collision key for a normalized sequence. */
	public static function sequence_key( array $sequence ): string {
		return hash( 'sha256', implode( "\x1F", $sequence ) );
	}

	/** Return whether two sequences would compete in the rolling matcher. */
	public static function conflict( array $first, array $second ): bool {
		return self::is_edge( $first, $second ) || self::is_edge( $second, $first );
	}

	/** Return whether a sequence conflicts with any already-active sequence. */
	public static function conflicts_with_any( array $sequence, array $active_sequences ): bool {
		foreach ( $active_sequences as $active ) {
			if ( is_array( $active ) && self::conflict( $sequence, $active ) ) {
				return true;
			}
		}
		return false;
	}

	/** Return named keys accepted by the custom-sequence recorder. */
	public static function named_keys(): array {
		return array(
			'ArrowUp',
			'ArrowDown',
			'ArrowLeft',
			'ArrowRight',
			'Enter',
			'Escape',
			'Space',
			'Tab',
			'Backspace',
			'Delete',
			'Home',
			'End',
			'PageUp',
			'PageDown',
			'Insert',
		);
	}

	/** Return whether the shorter sequence is a prefix or suffix of the longer. */
	private static function is_edge( array $shorter, array $longer ): bool {
		$short_length = count( $shorter );
		$long_length  = count( $longer );
		if ( 0 === $short_length || $short_length > $long_length ) {
			return false;
		}

		return $shorter === array_slice( $longer, 0, $short_length )
			|| $shorter === array_slice( $longer, $long_length - $short_length );
	}
}
