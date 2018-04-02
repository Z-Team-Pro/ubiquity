<?php

namespace Ubiquity\utils\git;

use Cz\Git\GitRepository;
use Cz\Git\GitException;

class UGitRepository extends GitRepository {

	/**
	 * Runs command.
	 *
	 * @param
	 *        	string|array
	 * @return self
	 * @throws Cz\Git\GitException
	 */
	protected function run($cmd/*, $options = NULL*/){
		$args = func_get_args ();
		$cmd = $this->_processCommand ( $args );
		exec ( $cmd . ' 2>&1', $output, $ret );

		if ($ret !== 0) {
			throw new GitException ( "Command '$cmd' failed (exit-code $ret).", $ret );
		}

		return $this;
	}

	protected function _processCommand(array $args) {
		$cmd = array ();

		$programName = array_shift ( $args );

		foreach ( $args as $arg ) {
			if (is_array ( $arg )) {
				foreach ( $arg as $key => $value ) {
					$_c = '';

					if (is_string ( $key )) {
						$_c = "$key ";
					}
					if (is_array ( $value )) {
						foreach ( $value as $v ) {
							$cmd [] = $_c . escapeshellarg ( $v );
						}
					} else {
						$cmd [] = $_c . escapeshellarg ( $value );
					}
				}
			} elseif (is_scalar ( $arg ) && ! is_bool ( $arg )) {
				$cmd [] = escapeshellarg ( $arg );
			}
		}
		return "$programName " . implode ( ' ', $cmd );
	}

	/**
	 * Returns list of untracked files in repo.
	 *
	 * @return string[]|NULL NULL => no files untracked
	 */
	public function getUntrackedFiles() {
		return $this->extractFromCommand ( 'git ls-files --others --exclude-standard', function ($value) {
			return trim ( $value );
		} );
	}

	/**
	 * Returns list of modified files in repo.
	 *
	 * @return string[]|NULL NULL => no files modified
	 */
	public function getModifiedFiles() {
		try {
			return $this->extractFromCommand ( 'git diff --name-status HEAD', function ($array) {
				$array = trim ( preg_replace ( '!\s+!', ' ', $array ) );
				return explode ( ' ', $array );
			} );
		} catch ( \Cz\Git\GitException $e ) {
			return [ ];
		}
	}

	public function getChangesInFile($filename) {
		try {
			$output = $this->extractFromCommand ( 'git diff ' . $filename );
			if (is_array ( $output ))
				return implode ( '\r\n', $output );
			return $output;
		} catch ( \Cz\Git\GitException $e ) {
			return "";
		}
	}

	public function getChangesInCommit($commitHash) {
		try {
			$output = $this->extractFromCommand ( "git show {$commitHash}" );
			if (is_array ( $output ))
				return implode ( '\r\n', $output );
			return $output;
		} catch ( \Cz\Git\GitException $e ) {
			return "";
		}
	}

	/**
	 * Returns the remote URL
	 *
	 * @return string
	 */
	public function getRemoteUrl() {
		try {
			$values = $this->extractFromCommand ( 'git config --get remote.origin.url', function ($str) {
				return trim ( $str );
			} );
			if (isset ( $values )) {
				return implode ( " ", $values );
			}
		} catch ( \Cz\Git\GitException $e ) {
			return "";
		}
		return "";
	}

	/**
	 * Ignore file(s).
	 * `git update-index --assume-unchanged <file>`
	 *
	 * @param $files string|string[]
	 * @throws Cz\Git\GitException
	 * @return self
	 */
	public function ignoreFiles($files) {
		if (! is_array ( $files )) {
			$files = func_get_args ();
		}
		$this->begin ();
		$this->run ( 'git reset', NULL, [ "--" => $files ] );
		return $this->end ();
	}

	public function getCommits() {
		$nonPushed = $this->getNonPushedCommitHash ();
		try {
			return $this->extractFromCommand ( 'git log --pretty=format:"%h___%an___%ar___%s___%H"', function ($str) use ($nonPushed) {
				$array = explode ( "___", $str );
				$pushed = true;
				if (is_array ( $nonPushed ))
					$pushed = ! in_array ( $array [0], $nonPushed );
				return new GitCommit ( $array [0], $array [1], $array [2], $array [3], $array [4], $pushed );
			} );
		} catch ( \Cz\Git\GitException $e ) {
			return [ ];
		}
		return [ ];
	}

	public function getNonPushedCommitHash() {
		try {
			return $this->extractFromCommand ( 'git log origin/master..master --pretty=format:"%h"' );
		} catch ( \Cz\Git\GitException $e ) {
			return [ ];
		}
		return [ ];
	}
}