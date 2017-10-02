<?php

/*
 * Tests for Template.php, called from expandFns.php.
 */

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase') &&
    class_exists('\PHPUnit_Framework_TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
}

// Initialize bot configuration
if (!defined('VERBOSE')) define('VERBOSE', TRUE);
$SLOW_MODE = TRUE;
 
class TemplateTest extends PHPUnit\Framework\TestCase {

  protected function setUp() {
  }

  protected function tearDown() {
  }
  
  protected function process_citation($text) {
    $template = new Template();
    $template->parse_text($text);
    $template->process();
    return $template;
  }
  
  protected function process_page($text) {
    $page = new Page();
    $page->parse_text($text);
    $page->expand_text();
    return $page;
  }

  public function testParameterWithNoParameters() {
    $text = "{{Cite web | text without equals sign  }}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals($text, $expanded_citation->parsed_text());
    $text = "{{  No pipe  }}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals($text, $expanded_citation->parsed_text());
  }
  
  public function testUseUnusedData() {
    $text = "{{Cite web | http://google.com | title  I am a title | auhtor = Other, A. N. | issue- 9 | vol. 22 pp. 5-6 }}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('cite web',          $expanded_citation->wikiname());
    $this->assertEquals('http://google.com', $expanded_citation->get('url'));
    $this->assertEquals('I am a title',      $expanded_citation->get('title')); 
    $this->assertEquals('Other, A. N.',      $expanded_citation->get('author'));
    $this->assertEquals('9'           ,      $expanded_citation->get('issue'));
    $this->assertEquals('22'          ,      $expanded_citation->get('volume'));
    $this->assertEquals('5–6'         ,      $expanded_citation->get('pages'));
  }
  
  public function testJstorExpansion() {
    $text = "{{Cite web | www.jstor.org/stable/pdfplus/1701972.pdf?&acceptTC=true}}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('cite journal', $expanded_citation->wikiname());
    $this->assertEquals('1701972'     , $expanded_citation->get('jstor'));
  }
  
  public function testPmidExpansion() {
    $text = "{{Cite web | http://www.ncbi.nlm.nih.gov/pubmed/1941451?dopt=AbstractPlus}}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('cite journal', $expanded_citation->wikiname());
    $this->assertEquals('1941451', $expanded_citation->get('pmid'));
  }
  
  public function testPMCExpansion() {
    $text = "{{Cite web | http://www.ncbi.nlm.nih.gov/pmc/articles/PMC154623/}}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('cite journal', $expanded_citation->wikiname());
    $this->assertEquals('154623', $expanded_citation->get('pmc'));
  }
  
  public function testArxivExpansion() {
    $text = "{{Cite web | http://uk.arxiv.org/abs/0806.0013}}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('cite journal', $expanded_citation->wikiname());
    $this->assertEquals('0806.0013', $expanded_citation->get('arxiv'));
    
    $text = "{{Cite arxiv | eprint = 0806.0013 | class=forgetit|publisher=uk.arxiv}}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('cite journal', $expanded_citation->wikiname());
    $this->assertEquals('0806.0013', $expanded_citation->get('arxiv'));
    $this->assertNull($expanded_citation->get('class'));
    $this->assertNull($expanded_citation->get('eprint'));
    $this->assertNull($expanded_citation->get('publisher'));
  }
  
  public function testAmazonExpansion() {
    $text = "{{Cite web | http://www.amazon.com/On-Origin-Phyla-James-Valentine/dp/0226845494 | accessdate=2012-04-20}}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('cite book', $expanded_citation->wikiname());
    $this->assertEquals('0226845494', $expanded_citation->get('asin'));
  }
  
  public function testDoiExpansion() {
    $text = "{{Cite web | http://onlinelibrary.wiley.com/doi/10.1111/j.1475-4983.2012.01203.x/abstract}}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('cite journal', $expanded_citation->wikiname());
    $this->assertEquals('10.1111/j.1475-4983.2012.01203.x', $expanded_citation->get('doi'));
  }
  
  public function testGarbageRemovalAndSpacing() {
    // Also tests handling of upper-case parameters
    $text = "{{Cite web | pages=10-11| Edition = 3rd ed. |journal=My Journal| issn=1234-4321 | publisher=Unwarranted |issue=0|accessdate=2013-01-01|quotes=no}}";
    $expanded_citation = $this->process_citation($text);
    // ISSN should be retained when journal is originally present
    $this->assertEquals('{{Cite journal| pages=10–11| edition = 3rd |journal=My Journal| issn=1234-4321 }}', $expanded_citation->parsed_text());
    
    $text = "{{Cite web | Journal=My Journal| issn=1234-4321 | publisher=Unwarranted }}";
    $expanded_citation = $this->process_citation($text);
    // ISSN is removed when journal is added.  Is this the desired behaviour? ##TODO!
    $this->assertEquals('{{Cite journal| journal=My Journal}}', $expanded_citation->parsed_text());
  }
  
  public function testJournalCapitalization() {
    $expanded = $this->process_citation("{{Cite journal|pmid=9858585}}");
    $this->assertEquals('Molecular and Cellular Biology', $expanded->get('journal'));
  }
  
  public function testPageDuplication() {
     global $SLOW_MODE;
     $SLOW_MODE = FALSE; // Otherwise we'll find a bibcode
     $text = '{{cite journal| p=546 |doi=10.1103/PhysRev.57.546|title=Nuclear Fission of Separated Uranium Isotopes |journal=Physical Review |volume=57 |issue=6 |year=1940 |last1=Nier |first1=Alfred O. |last2=Booth |first2=E. T. |last3=Dunning |first3=J. R. |last4=Grosse |first4=A. V. }}';
     $expanded = $this->process_citation($text);
     $this->assertEquals($text, $expanded->parsed_text());
   }
  
  public function testUnknownJournal() {
    $text = '{{cite journal|bibcode= 1975STIN...7615344H |title= Development of a transmission error model and an error control model  |volume= 76 |author1= Hammond |first1= J. L. |last2= Brown |first2= J. E. |last3= Liu |first3= S. S. S. |year= 1975}}';
    $expanded = $this->process_citation($text);
    $this->assertEquals($text, $expanded->parsed_text());
  }

  public function testCiteArxivRecognition() {
    $text = '{{Cite web | eprint=1203.0149}}';
    $expanded = $this->process_citation($text);
    $this->assertEquals('Cite arxiv', $expanded->name());
  }
  
  public function testBrokenDoiDetection() {
    $text = '{{cite journal|doi=10.3265/Nefrologia.pre2010.May.10269|title=Acute renal failure due to multiple stings by Africanized bees. Report on 43 cases}}';
    $expanded = $this->process_citation($text);
    var_dump($expanded->get('doi-brokendate'));
    $this->assertNull($expanded->get('doi-broken-date'));
    
    $text = '{{cite journal|doi=10.3265/Nefrologia.NOTAREALDOI.broken|title=Acute renal failure due to multiple stings by Africanized bees. Report on 43 cases}}';
    $expanded = $this->process_citation($text);
    $this->assertNotNull($expanded->get('doi-broken-date'));
  }
  
  public function testOpenAccessLookup() {
    $text = '{{cite journal|doi=10.1038/nature12373}}';
    $expanded = $this->process_citation($text);
    $this->assertEquals('http://www.ncbi.nlm.nih.gov/pmc/articles/PMC4221854', $expanded->get('url'));
    $this->assertEquals('Accepted manuscript', $expanded->get('format'));
  }
  
  /* Don't run test until I check the consensus on how such citations should be handled
  public function testEtAlHandlingAndSpaceRetention() {
    $text = "{{Cite book | authors=Smith, A; Jones, B; Western, C., et al.}}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('{{Cite book | last1=Smith| first1=A|last2 = Jones|first2 = B|last3 = Western|first3 = C.|author4 = and others|displayauthors = 3}}', $expanded_citation->parsed_text()); 
  }
  */
  
  public function testCommentHandling() {
    $text = "{{cite book|pages=3333 <!-- yes --> }} {{cite book <!-- no --> | pages=3<nowiki>-</nowiki>6}}";
    $expanded_page = $this->process_page($text);
    $this->assertEquals($text, $expanded_page->parsed_text());
  }
  
  public function testSiciExtraction() {
    $text = "{{cite journal|url=http://fake.url/0097-3157(2002)152[0215:HPOVBM]2.0.CO;2}}";
    $expanded = $this->process_citation($text);
    $this->assertEquals('0097-3157', $expanded->get('issn'));
    $this->assertEquals('2002', $expanded->get('year'));
    $this->assertEquals('152', $expanded->get('volume'));
    $this->assertEquals('215', $expanded->get('pages'));
    $expanded = NULL;
    
    // Now check that parameters are NOT extracted when certain parameters exist
    $text = "{{cite journal|date=2002|journal=SET|url=http:/1/fake.url/0097-3157(2002)152[0215:HPOVBM]2.0.CO;2}}";
    $expanded = $this->process_citation($text);
    $this->assertNull($expanded->get('issn'));
    if (is_null($expanded->get('date'))) {
      $this->assertEquals('2002', $expanded->get('year'));
    } else {
      $this->assertEquals('2002', $expanded->get('date'));
      $this->assertNull($expanded->get('year'));
    }
    $this->assertEquals('152', $expanded->get('volume'));
    $this->assertEquals('215', $expanded->get('pages'));
  }
  
  public function testMisspeltParameters() {
    $text = "{{Cite journal | ahtour=S.-X. HU, M.-Y. ZHU, F.-C. ZHAO, and M. STEINER|tutle=A crown group priapulid from the early Cambrian Guanshan Lagerstätte,|jrounal=Geol. Mag.|pp. 1–5|year= 2017.}}";
    $expanded = $this->process_citation($text);
    $this->assertNotNull($expanded->get('author')); ## Check: the parameter might be broken down into last1, first1 etc
    $this->assertNotNull($expanded->get('title'));
    $this->assertNotNull($expanded->get('journal'));
    $this->assertNotNull($expanded->get('pages'));
    $this->assertNotNull($expanded->get('year'));
    
    $text = "{{Cite journal | ahtour=S.-X. HU, M.-Y. ZHU, F.-C. ZHAO, and M. STEINER|tutel=A crown group priapulid from the early Cambrian Guanshan Lagerstätte,|jrounal=Geol. Mag.|pp. 1–5|year= 2017.}}";
    $expanded = $this->process_citation($text);
    $this->assertNotNull($expanded->get('author')); ## Check: the parameter might be broken down into last1, first1 etc
    $this->assertNotNull($expanded->get('tutel'));
    $this->assertNotNull($expanded->get('journal'));
    $this->assertNotNull($expanded->get('pages'));
    $this->assertNotNull($expanded->get('year'));
  
    // test attempt to add a parameter listed in COMMON_MISTAKES
    $album_link = 'http://album.com';
    $expanded->add_if_new('albumlink', $album_link);
    $this->assertEquals($album_link, $expanded->get('titlelink'));    
     
    // Double-check pages expansion
    $text = "{{Cite journal|pp. 1-5}}";
    $expanded = $this->process_citation($text);
    $this->assertEquals('1–5',$expanded->get('pages'));
  }
  
  public function testGoogleBooksExpansion() {
    $text = "{{Cite web | http://books.google.co.uk/books/about/Wonderful_Life.html?id=SjpSkzjIzfsC&redir_esc=y}}";
    $expanded_citation = $this->process_citation($text);
    $this->assertEquals('cite book', $expanded_citation->wikiname());
    $this->assertEquals('https://books.google.com/?id=SjpSkzjIzfsC', $expanded_citation->get('url'));
    $this->assertEquals('Wonderful Life: The Burgess Shale and the Nature of History',
      $expanded_citation->get('title'));
    $this->assertEquals('9780393307009', $expanded_citation->get('isbn')   );
    $this->assertEquals('Gould'        , $expanded_citation->get('last1'));
    $this->assertEquals('Stephen Jay'  , $expanded_citation->get('first1') );
    $this->assertEquals('1990-09-17'   , $expanded_citation->get('date'));
  }
  
  
  public function testErrantAuthor() {
    $text = '{{cite journal|url=http://books.google.com/books?id=p-IDAAAAMBAJ&lpg=PA195&dq=Popular%20Science%201930%20plane%20%22Popular%20Mechanics%22&pg=PA194#v=onepage&q&f=true |title=The Passing of the Carrier Pigeon|journal=Popular Mechanics |date=February 1930|pages= 340}}';
    $expanded = $this->process_citation($text);
    $this->assertEquals($text, $expanded->parsed_text());
  }
  
  public function testLongAuthorLists() {
    $text = '{{cite web | https://arxiv.org/PS_cache/arxiv/pdf/1003/1003.3124v2.pdf}}';
    $expanded = $this->process_citation($text);
    $this->assertEquals('The ATLAS Collaboration', $expanded->first_author());
    $this->assertEquals('hep-ex', $expanded->get('class'));
    
    // Same paper, but CrossRef records full list of authors instead of collaboration name
    $text = '{{cite web | 10.1016/j.physletb.2010.03.064}}';
    $expanded = $this->process_citation($text);
    $this->assertEquals('29', $expanded->get('displayauthors'));
    $this->assertEquals('Aielli', $expanded->get('last30'));
    $this->assertEquals("Charged-particle multiplicities in pp interactions at <math>"
      . '\sqrt{s}=900\text{ GeV}' .
      "</math> measured with the ATLAS detector at the LHC", $expanded->get('title'));
    $this->assertNull($expanded->get('last31'));
  }
  

  public function testInPress() {
    $text = '{{Cite journal|pmid=9858585|date =in press}}';
    $expanded = $this->process_citation($text);
    $this->assertNull($expanded->get('date'));
    $this->assertEquals('1999',$expanded->get('year'));

    $text = '{{cite journal|pmid=9858585|year=in press}}';
    $expanded = $this->process_citation($text);
    $this->assertNull($expanded->get('date'));
    $this->assertEquals('1999',$expanded->get('year'));
  }
  
 public function testISODates() {
      $text = '{{cite book |author=Me |title=Title |year=2007-08-01 }}';
      $expanded = $this->process_citation($text);
      $this->assertEquals('2007-08-01', $expanded->get('date'));
      $this->assertNull($expanded->get('year'));
  }
  
  public function testRIS() {
      $text = '{{Cite journal  | TY - JOUR
AU - Shannon, Claude E.
PY - 1948/07//
TI - A Mathematical Theory of Communication
T2 - Bell System Technical Journal
SP - 379
EP - 423
VL - 27
ER -  }}';
     $expanded = $this->process_citation($text);
     $this->assertEquals('A Mathematical Theory of Communication', $expanded->get('title'));
     $this->assertEquals('1948-07', $expanded->get('date'));
     $this->assertEquals('Bell System Technical Journal', $expanded->get('journal'));
     $this->assertEquals('Shannon, Claude E', $expanded->first_author());
     $this->assertEquals('Shannon', $expanded->get('last1'));
     $this->assertEquals('Claude E', $expanded->get('first1'));
     $this->assertEquals('379–423', $expanded->get('pages'));
     $this->assertEquals('27', $expanded->get('volume'));   
  }
    

  public function testEndNote() {
      $book = '{{Cite book |
%0 Book
%A Geoffrey Chaucer
%D 1957
%T The Works of Geoffrey Chaucer
%E F.
%I Houghton
%C Boston
%N 2nd
      }}'; // Not quite clear how %E and %N should be handled here. Needs an assertion.
      $article = '{{Cite journal |
%0 Journal Article
%A Herbert H. Clark
%D 1982
%T Hearers and Speech Acts
%B Language
%V 58
%P 332-373
      }}'; // Not sure how %B should be handled; needs an assertion.
      $thesis = '{{Citation | 
%0 Thesis
%A Cantucci, Elena
%T Permian strata in South-East Asia
%D 1990
%I University of California, Berkeley
%R 10.1038/ntheses.01928
%9 Dissertation}}';
       $expanded = $this->process_citation($book);
       $this->assertEquals('Chaucer, Geoffrey', $expanded->first_author());
       $this->assertEquals('The Works of Geoffrey Chaucer', $expanded->get('title'));
       $this->assertEquals('1957', $expanded->get('date'));
       $this->assertEquals('Houghton', $expanded->get('publisher'));
       $this->assertEquals('Boston', $expanded->get('location'));
       
       $expanded = $this->process_citation($article);
       $this->assertEquals('Clark, Herbert H', $expanded->first_author());
       $this->assertEquals('1982', $expanded->get('date'));
       $this->assertEquals('Hearers and Speech Acts', $expanded->get('title'));
       $this->assertEquals('58', $expanded->get('volume'));
       $this->assertEquals('332–373', $expanded->get('pages'));
       
       
       $expanded = $this->process_citation($thesis);
       $this->assertEquals('Cantucci, Elena', $expanded->first_author());
       $this->assertEquals('Permian strata in South-East Asia', $expanded->get('title'));
       $this->assertEquals('1990', $expanded->get('date'));
       $this->assertEquals('University of California, Berkeley', $expanded->get('publisher'));
       $this->assertEquals('10.1038/ntheses.01928', $expanded->get('doi'));  
   }
   
   
  public function testEtAl() {
      $text = '{{cite book |auths=Alfred A Albertstein, Bertie B Benchmark, Charlie C. Chapman et al. }}';
      $expanded = $this->process_citation($text);
      $this->assertEquals('Albertstein, Alfred A', $expanded->first_author());
      $this->assertEquals('Charlie C', $expanded->get('first3'));
      $this->assertEquals('etal', $expanded->get('displayauthors'));
  }
       
  public function testWebsite2Url() {
      $text = '{{cite book |website=ttp://example.org }}';
      $expanded = $this->process_citation($text);
      $this->assertEquals('http://example.org', $expanded->get('url'));
      
      $text = '{{cite book |website=example.org }}';
      $expanded = $this->process_citation($text);
      $this->assertEquals('http://example.org', $expanded->get('url'));
      
      $text = '{{cite book |website=ttp://jstor.org/pdf/123456 | jstor=123456 }}';
      $expanded = $this->process_citation($text);
      $this->assertNull($expanded->get('url'));
      
      $text = '{{cite book |website=ABC}}';
      $expanded = $this->process_citation($text);
      $this->assertNull($expanded->get('url'));
      $this->assertEquals('ABC', $expanded->get('website'));
      
      $text = '{{cite book |website=ABC XYZ}}';
      $expanded = $this->process_citation($text);
      $this->assertNull($expanded->get('url'));
      $this->assertEquals('ABC XYZ', $expanded->get('website'));
      
      $text = '{{cite book |website=http://ABC/ I have Spaces in Me}}';
      $expanded = $this->process_citation($text);
      $this->assertNull($expanded->get('url'));
      $this->assertEquals('http://ABC/ I have Spaces in Me', $expanded->get('website'));
  }
  
  public function testLinefeeds(){
       $text = '{{cite arXiv|eprint=hep-th/0303241}}';
       $expanded = $this->process_citation($text);
       $this->assertEquals('Pascual Jordan, his contributions to quantum mechanics and his legacy in contemporary local quantum physics',$expanded->get('title'));
   }

  /* TODO 
  Test adding a paper with > 4 editors; this should trigger displayeditors
  Test finding a DOI and using it to expand a paper [See testLongAuthorLists - Arxiv example?]
  Test adding a doi-is-broken modifier to a broken DOI.
  */    
}
