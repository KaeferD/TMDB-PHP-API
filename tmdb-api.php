<?php
/**
 * 	TMDB API v3 PHP class - wrapper to API version 3 of 'themoviedb.org
 * 	API Documentation: http://help.themoviedb.org/kb/api/about-3
 * 	Libray Documentation: http://code.octal.es/php/tmdb-api/
 *
 * 	@pakage TMDB-PHP-API
 * 	@author adangq <adangq@gmail.com>
 * 	@copyright 2012 pixelead0
 * 	@date 2012-02-12
 * 	@link http://www.github.com/pixelead
 * 	@version 0.0.2
 * 	@license BSD http://www.opensource.org/licenses/bsd-license.php
 *
 * 	Portions of this file are based on pieces of TMDb PHP API class - API 'themoviedb.org'
 * 	@Copyright Jonas De Smet - Glamorous | https://github.com/glamorous/TMDb-PHP-API
 * 	Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 * 	@date 10.12.2010
 * 	@version 0.9.10
 * 	@author Jonas De Smet - Glamorous
 * 	@link {https://github.com/glamorous/TMDb-PHP-API}
 *
 * 	Mostly code cleaning, adaptation and documentation
 * 	@Copyright Alvaro Octal | https://github.com/Alvaroctal/TMDB-PHP-API
 * 	Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 * 	@date 09/01/2015
 * 	@version 0.0.3
 * 	@author Alvaro Octal
 * 	@link {https://github.com/Alvaroctal/TMDB-PHP-API}
 */

include("data/Movie.php");
include("data/TVShow.php");
include("data/Season.php");
include("data/Episode.php");
include("data/Person.php");
include("data/Role.php");
include("data/roles/MovieRole.php");
include("data/roles/TVShowRole.php");
include("data/Collection.php");
include("data/Company.php");
include("data/config/Configuration.php");

class TMDB{

	#@var string url of API TMDB
	const _API_URL_ = "http://api.themoviedb.org/3/";

	#@var string url of secure API TMDB
	const _SECURE_API_URL_ = "https://api.themoviedb.org/3/";

	#@var string Version of this class
	const VERSION = '0.0.3';

	#@var string API KEY
	private $_apikey;

	#@var string Default language
	private $_lang;

	#@var array of TMDB config
    private $_configuration;

	#@var boolean for testing
	private $_debug;

	#@var boolean for secure api url
	private $_secure;


	/**
	 * 	Construct Class
	 *
	 * 	@param string $apikey The API key token
	 * 	@param string $lang The languaje to work with, default is english
	 */
	public function __construct($apikey, $lang = 'en', $debug = false, $secure = true) {

		// Sets the API key
		$this->setApikey($apikey);
	
		// Setting Language
		$this->setLang($lang);

		$this->setSecure($secure);

		// Set the debug mode
		$this->_debug = $debug;

		// Load the configuration
		if (! $this->_loadConfig()){
			echo "Unable to read configuration, verify that the API key is valid";
			exit;
		}
	}

	//------------------------------------------------------------------------------
	// Api Key
	//------------------------------------------------------------------------------
         
	/** 
	 * 	Set the API key
	 *
	 * 	@param string $apikey
	 * 	@return void
	 */
	private function setApikey($apikey) {
		$this->_apikey = (string) $apikey;
	}

	/** 
	 * 	Get the API key
	 *
	 * 	@return string
	 */
	private function getApikey() {
		return $this->_apikey;
	}

	//------------------------------------------------------------------------------
	// Language
	//------------------------------------------------------------------------------

	/** 
	 *  Set the language
	 *	By default english
	 *
	 * 	@param string $lang
	 */
	public function setLang($lang = 'en') {
		$this->_lang = $lang;
	}

	/** 
	 * 	Get the language
	 *
	 * 	@return string
	 */
	public function getLang() {
		return $this->_lang;
	}

	//------------------------------------------------------------------------------
	// API URL
	//------------------------------------------------------------------------------

	/** 
	 *  Set the api url to use ssl
	 *
	 * 	@param boolean $secure
	 */
	public function setSecure($secure = 'en') {
		$this->_secure = $secure;
	}

	/**  
	 *
	 * 	@return boolean
	 */
	public function getSecure() {
		return $this->_secure;
	}

	//------------------------------------------------------------------------------
	// Config
	//------------------------------------------------------------------------------

	/**
	 * 	Loads the configuration of the API
	 *
	 * 	@return boolean
	 */
	private function _loadConfig() {
		$this->_configuration = new Configuration($this->_call('configuration', ''));

		return ! empty($this->_configuration);
	}

	/**
	 * 	Get Configuration of the API (Revisar)
	 *
	 * 	@return Configuration
	 */
	public function getConfig(){
		return $this->_configuration;
	}

	//------------------------------------------------------------------------------
	// Get Variables
	//------------------------------------------------------------------------------

	/** 
	 *	Get the URL images
	 * 	You can specify the width, by default original
	 *
	 * 	@param String $size A String like 'w185' where you specify the image width
	 * 	@return string
	 */
	public function getImageURL($size = 'original') {
		return $this->_configuration->getImageBaseURL() . $size;
	}

	/**
	 * 	Get Movie Info
	 * 	Gets part of the info of the Movie, mostly used for the lazy load
	 *
	 * 	@param int $idMovie The Movie id
	 *  @param string $option The request option
	 * 	@param string $append_request additional request
	 * 	@return array
	 *	@deprecated Will be removed in 0.0.4, do not get used to use this method
	 */
	public function getMovieInfo($idMovie, $option = '', $append_request = ''){
		$option = (empty($option)) ? '' : '/' . $option;
		$params = 'movie/' . $idMovie . $option;
		$result = $this->_call($params, $append_request);
			
		return $result;
	}

	//------------------------------------------------------------------------------
	// API Call
	//------------------------------------------------------------------------------

	/**
	 * 	Makes the call to the API and retrieves the data as a JSON
	 *
	 * 	@param string $action	API specific function name for in the URL
	 * 	@param string $appendToResponse	The extra append of the request
	 * 	@return string
	 */
	private function _call($action, $appendToResponse){
		
		if($this->getSecure()){
			$url = self::_SECURE_API_URL_;
		}else{
			$url = self::_API_URL_;
		}

		$url .= $action .'?api_key='. $this->getApikey() .'&language='. $this->getLang() .'&'.$appendToResponse;

		if ($this->_debug) {
			echo '<pre><a href="' . $url . '">check request</a></pre>';
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);

		$results = curl_exec($ch);

		curl_close($ch);

		return (array) json_decode(($results), true);
	}

	//------------------------------------------------------------------------------
	// Get Data Objects
	//------------------------------------------------------------------------------

	/**
	 * 	Get a Movie
	 *
	 * 	@param int $idMovie The Movie id
	 * 	@param string $appendToResponse The extra append of the request, by default all
	 * 	@return Movie
	 */
	public function getMovie($idMovie, $appendToResponse = 'append_to_response=trailers,images,casts,translations'){
		return new Movie($this->_call('movie/' . $idMovie, $appendToResponse));
	}

	/**
	 * 	Get a TVShow
	 *
	 * 	@param int $idTVShow The TVShow id
	 * 	@param string $appendToResponse The extra append of the request, by default all
	 * 	@return TVShow
	 */
	public function getTVShow($idTVShow, $appendToResponse = 'append_to_response=trailers,images,casts,translations,keywords'){
		return new TVShow($this->_call('tv/' . $idTVShow, $appendToResponse));
	}

	/**
	 * 	Get a Season
	 *
	 *  @param int $idTVShow The TVShow id
	 *  @param int $numSeason The Season number
	 * 	@param string $appendToResponse The extra append of the request, by default all
	 * 	@return Season
	 */
	public function getSeason($idTVShow, $numSeason, $appendToResponse = 'append_to_response=trailers,images,casts,translations'){
		return new Season($this->_call('tv/'. $idTVShow .'/season/' . $numSeason, $appendToResponse), $idTVShow);
	}

	/**
	 * 	Get a Episode
	 *
	 *  @param int $idTVShow The TVShow id
	 *  @param int $numSeason The Season number
	 *  @param int $numEpisode the Episode number
	 * 	@param string $appendToResponse The extra append of the request, by default all
	 * 	@return Episode
	 */
	public function getEpisode($idTVShow, $numSeason, $numEpisode, $appendToResponse = 'append_to_response=trailers,images,casts,translations'){
		return new Episode($this->_call('tv/'. $idTVShow .'/season/'. $numSeason .'/episode/'. $numEpisode, $appendToResponse), $idTVShow);
	}

	/**
	 * 	Get a Person
	 *
	 * 	@param int $idPerson The Person id
	 * 	@param string $appendToResponse The extra append of the request, by default all
	 * 	@return Person
	 */
	public function getPerson($idPerson, $appendToResponse = 'append_to_response=tv_credits,movie_credits'){
		return new Person($this->_call('person/' . $idPerson, $appendToResponse));
	}

	/**
	 * 	Get a Collection
	 *
	 * 	@param int $idCollection The Person id
	 * 	@param string $appendToResponse The extra append of the request, by default all
	 * 	@return Collection
	 */
	public function getCollection($idCollection, $appendToResponse = 'append_to_response=images'){
		return new Collection($this->_call('collection/' . $idCollection, $appendToResponse));
	}

	/**
	 * 	Get a Company
	 *
	 * 	@param int $idCompany The Person id
	 * 	@param string $appendToResponse The extra append of the request, by default all
	 * 	@return Company
	 */
	public function getCompany($idCompany, $appendToResponse = 'append_to_response=movies'){
		return new Company($this->_call('company/' . $idCompany, $appendToResponse));
	}

	//------------------------------------------------------------------------------
	// Searches
	//------------------------------------------------------------------------------

	/**
	 *  Search Movie
	 *
	 * 	@param string $movieTitle The title of a Movie
	 * 	@return Movie[]
	 */
	public function searchMovie($movieTitle){

		$movies = array();

		$result = $this->_call('search/movie', 'query='. urlencode($movieTitle), $this->getLang());

		foreach($result['results'] as $data){
			$movies[] = new Movie($data);
		}

		return $movies;
	}

	/**
	 *  Search TVShow
	 *
	 * 	@param string $tvShowTitle The title of a TVShow
	 * 	@return TVShow[]
	 */
	public function searchTVShow($tvShowTitle){

		$tvShows = array();

		$result = $this->_call('search/tv', 'query='. urlencode($tvShowTitle), $this->getLang());

		foreach($result['results'] as $data){
			$tvShows[] = new TVShow($data);
		}

		return $tvShows;
	}

	/**
	 *  Search Person
	 *
	 * 	@param string $personName The name of the Person
	 * 	@return Person[]
	 */
	public function searchPerson($personName){

		$persons = array();

		$result = $this->_call('search/person', 'query='. urlencode($personName), $this->getLang());

		foreach($result['results'] as $data){
			$persons[] = new Person($data);
		}

		return $persons;
	}

	/**
	 *  Search Collection
	 *
	 * 	@param string $collectionName The name of the Collection
	 * 	@return Collection[]
	 */
	public function searchCollection($collectionName){

		$collections = array();

		$result = $this->_call('search/collection', 'query='. urlencode($collectionName), $this->getLang());

		foreach($result['results'] as $data){
			$collections[] = new Collection($data);
		}

		return $collections;
	}

	/**
	 *  Search Company
	 *
	 * 	@param string $companyName The name of the Company
	 * 	@return Company[]
	 */
	public function searchCompany($companyName){

		$companies = array();

		$result = $this->_call('search/company', 'query='. urlencode($companyName), $this->getLang());

		foreach($result['results'] as $data){
			$companies[] = new Company($data);
		}

		return $companies;
	}

	//------------------------------------------------------------------------------
	//
	// Get Lists 
	//
	//------------------------------------------------------------------------------

	//------------------------------------------------------------------------------
	// Get Lists of Movies
	//------------------------------------------------------------------------------

	/**
	 * 	Get the latest Movie 
	 *
	 * 	@return Movie
	 */
	public function getLatestMovie() {
		return new Movie($this->_call('movie/latest',''));
	}

	/**
	 *  Get the upcoming Movies
	 *
	 * 	@param integer $page The page number of the results
	 * 	@return Movie[]
	 */
	public function getUpcomingMovies($page = 1) {

		$movies = array();

		$result = $this->_call('movie/upcoming', 'page='. $page);

		foreach($result['results'] as $data){
			$movies[] = new Movie($data);
		}

		return $movies;
	}

	/**
	 *  Get now playing Movies
	 *
	 * 	@param integer $page The page number of the results
	 * 	@return Movie[]
	 */
	public function getNowPlayingMovies($page = 1) {

		$movies = array();

		$result = $this->_call('movie/now-playing', 'page='. $page);

		foreach($result['results'] as $data){
			$movies[] = new Movie($data);
		}

		return $movies;
	}

	/**
	 *  Get popular Movies
	 *
	 * 	@param integer $page The page number of the results
	 * 	@return Movie[]
	 */
	public function getPopularMovies($page = 1) {

		$movies = array();

		$result = $this->_call('movie/popular', 'page='. $page);

		foreach($result['results'] as $data){
			$movies[] = new Movie($data);
		}

		return $movies;
	}

	/**
	 *  Get top rated Movies
	 *
	 * 	@param integer $page The page number of the results
	 * 	@return Movie[]
	 */
	public function getTopRatedMovies($page = 1) {

		$movies = array();

		$result = $this->_call('movie/top_rated', 'page='. $page);

		foreach($result['results'] as $data){
			$movies[] = new Movie($data);
		}

		return $movies;
	}

	//------------------------------------------------------------------------------
	// Get Lists of TVShows
	//------------------------------------------------------------------------------

	/**
	 * 	Get latest TVShow
	 *
	 * 	@return TVShow
	 */
	public function getLatestTVShow() {
		return new TVShow($this->_call('tv/latest',''));
	}

	/**
	 * 	Get popular TVShows
	 *
	 * 	@return TVShow[]
	 */
	public function getPopularTVShows($page = 1) {
		$tvShows = array();

		$result = $this->_call('tv/popular','page='. $page);

		foreach($result['results'] as $data){
			$tvShows[] = new TVShow($data);
		}

		return $tvShows;
	}

	/**
	 * 	Get on the air TVShows
	 *
	 * 	@return TVShow[]
	 */
	public function getOnTheAirTVShows($page = 1) {
		$tvShows = array();

		$result = $this->_call('tv/on_the_air','page='. $page);

		foreach($result['results'] as $data){
			$tvShows[] = new TVShow($data);
		}

		return $tvShows;
	}

	/**
	 * 	Get airing today TVShows
	 *
	 * 	@return TVShow[]
	 */
	public function getAiringTodayTVShows($page = 1) {
		$tvShows = array();

		$result = $this->_call('tv/airing_today','page='. $page);

		foreach($result['results'] as $data){
			$tvShows[] = new TVShow($data);
		}

		return $tvShows;
	}

	/**
	 * 	Get top rated TVShows
	 *
	 * 	@return TVShow[]
	 */
	public function getTopRatedTVShows($page = 1) {
		$tvShows = array();

		$result = $this->_call('tv/top_rated','page='. $page);

		foreach($result['results'] as $data){
			$tvShows[] = new TVShow($data);
		}

		return $tvShows;
	}

	//------------------------------------------------------------------------------
	// Get Lists of Persons
	//------------------------------------------------------------------------------

	/**
	 * 	Get latest Person
	 *
	 * 	@return Person
	 */
	public function getLatestPerson() {
		return new Person($this->_call('person/latest',''));
	}

	/**
	 * 	Get Popular Persons
	 *
	 * 	@return Person[]
	 */
	public function getPopularPersons($page = 1) {
		$persons = array();

		$result = $this->_call('person/popular','page='. $page);

		foreach($result['results'] as $data){
			$persons[] = new Person($data);
		}

		return $persons;
	}

	//------------------------------------------------------------------------------
	// Find
	//------------------------------------------------------------------------------

	/**
	 *	Get a Movie by an external ID (f.e.: imdb)
	 *
	 *	@return Movie[]
	 */
	public function findMovie($movieID, $externalSource = 'imdb_id'){
		$movies = array();

		$result = $this->_call('find/' . $movieID, 'external_source=' . $externalSource);

		foreach ($result['movie_results'] as $data) {
			$movies[] = new Movie($data);
		}

		return $movies;
	}

	/**
	 *	Get a Person by an external ID (f.e.: imdb)
	 *
	 *	@return Person[]
	 */
	public function findPerson($personID, $externalSource = 'imdb_id'){
		$persons = array();

		$result = $this->_call('find/' . $personID, 'external_source=' . $externalSource);

		foreach ($result['person_results'] as $data) {
			$persons[] = new Person($data);
		}

		return $persons;
	}

	/**
	 *	Get a TVShow by an external ID (f.e.: imdb)
	 *
	 *	@return TVShow[]
	 */
	public function findTVShow($tvShowID, $externalSource = 'imdb_id'){
		$tvShows = array();

		$result = $this->_call('find/' . $tvShowID, 'external_source=' . $externalSource);

		foreach ($result['tv_results'] as $data) {
			$tvShows[] = new TVShow($data);
		}

		return $tvShows;
	}

	/**
	 *	Get a Season by an external ID (f.e.: imdb)
	 *
	 *	@return Season[]
	 */
	public function findSeason($seasonID, $externalSource = 'tvdb_id'){
		$seasons = array();

		$result = $this->_call('find/' . $seasonID, 'external_source=' . $externalSource);

		foreach ($result['tv_season_results'] as $data) {
			$seasons[] = new Season($data);
		}

		return $seasons;
	}

	/**
	 *	Get a Episode by an external ID (f.e.: imdb)
	 *
	 *	@return Episode[]
	 */
	public function findEpisode($episodeID, $externalSource = 'imdb_id'){
		$episodes = array();

		$result = $this->_call('find/' . $episodeID, 'external_source=' . $externalSource);

		foreach ($result['tv_episode_results'] as $data) {
			$episodes[] = new Episode($data);
		}

		return $episodes;
	}
}
?>
