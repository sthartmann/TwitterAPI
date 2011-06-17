<?php
/**
 * User: steffen
 * Date: 06.06.11
 * Time: 07:41
 */

// -- CONFIG ---
$sHashtagFile = 'hashtag.twitter';      // hashtag file
$sBadwordFile = 'badwords.twitter';     // badword file
$iCount = 50;                           // count of tweets

$bUseFilter = true;                     // activate badword filter

$sDataSeperator = "@#@";                // seperator for differnt tweets in data file
$sIDSeperator = "@*@";                  // seperator for tweet id

// --- SYS CONFIG --- (no changes required)
$sLastidFile = 'data/lastid.twitter';
$sDataFile = 'data/data.twitter';


// --- No changes needed below ---

// include twitter search class
require_once('TwitterSearch.php');
$search = new TwitterSearch();

// ... read the input files
$aHashtag = file($sHashtagFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$aBadwords = file($sBadwordFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);



// --- FILE INPUT
// read file and create array
$aDataFile = file($sDataFile, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
$aCurrentData = explode("@#@",$aDataFile[0]);

// create twitter like array
foreach($aCurrentData as $i => $value) {

    if ($value != "") {
        // ... use tweet id as key
        $aValues = explode("@*@", $value);
        $aCurrentFile[$aValues[0]] = $aValues[1];
    }

}



// --- TWITTER MAGIC
// ... query the twitter search for all hashtags
for ($i = 0; $i < count($aHashtag); $i++) {

    $search->with($aHashtag[$i]);
    $results = $search->rpp($iCount)->results();

    if (count($results) != 0) {

        // ... create output array
        for ($j = 0; $j < count($results); $j++) {

            $aResult[] = $results[$j];

        }

    }

}

// ... applying badword filter and find last id
for ($i = 0; $i < count($aResult); $i++) {

    // ... only process "new" tweets
    if ($aResult[$i]->id_str > $iLastid) {

        $bFilter = true;

        if ($bUseFilter) {

            for ($j = 0; $j < count($aBadwords) && $bFilter === true; $j++) {

                // ... check if already blacklisted
                if ($bFilter) {
                    if(stripos($aResult[$i]->text, $aBadwords[$j]) !== false) {
                       $bFilter = false;
                    }
                }

            }

        }

        if ($bFilter) {
            $aResultSet[$aResult[$i]->id_str] = $aResult[$i]->from_user . ": " . $aResult[$i]->text;
            //echo $aResult[$i]->from_user . ": " . $aResult[$i]->text."\n";
        }
        
    }

}


// --- FILE OUTPUT

$aFinalResult = $aResultSet + $aCurrentFile;
krsort($aFinalResult);

// ... build data string
$i = 0;
foreach($aFinalResult as $tweetID => $tweetText) {

    if ($i < $iCount) {
        $sResult.= $tweetID."@*@".$tweetText."@#@";
    }

}

// ... write to file
if (is_writable($sDataFile)) {

    if (!$fileHandle = fopen($sDataFile, "w")) {
        echo "Error opening data file!";
    }

    if(!fwrite($fileHandle, $sResult)) {
        echo "Error writing to data file!";
    }

} else {
    echo "Data file is not writable!";
}




 
