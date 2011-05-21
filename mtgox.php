<?php

include "config.inc.php";
include "curllib3.inc.php";
include "lib.inc.php";

// TODO: pobieranie z pliku .ini
date_default_timezone_set(getLocalTimezone());
$numoffers=10;

function ListOrder($c,$user,$password)
{
	$a=$c->post("https://mtgox.com/code/getOrders.php","name=$user&pass=$password");
	$j=json_decode($a);
	echo "OPEN ORDERS:\n";
	foreach ($j->orders as $order)
	{
		echo $order->oid."\t".$order->type."\t".$order->amount."\t".$order->price."\t".$order->status."\t".$order->dark."\t".$order->date."\t\n";
	}
}

$c=new CURL;

echo "ToyTrader v. 0.0.4 - command line mtgox trading tool\nDONATE for FASTER development: 1N8b1uzWA7RsfVPmA2kdGSEmsv91zTRMAX\nFor help type: H and press enter\n";

$quitflag=FALSE;
$refreshflag=TRUE;
do
{
	if ($refreshflag)
	{
		echo "== ".date('Y-m-d H:i:s')." ================================\n";
		$a=$c->post("https://mtgox.com/code/getFunds.php","name=$user&pass=$password");
		$j=json_decode($a);
		$myusd=$j->usds;
		$mybtc=$j->btcs;

		echo "USD: $myusd  BTC: $mybtc\n";

		$a=file_get_contents('https://mtgox.com/code/data/ticker.php');

		$j=json_decode($a);

		// TODO: {"ticker":{"high":8.389,"low":6.9799,"vol":34247,"buy":7.0752,"sell":7.1798,"last":7.1999}}
		echo "Last: ".$j->ticker->last."\n";

		$b=file_get_contents('https://mtgox.com/code/data/getDepth.php');

		$depth=json_decode($b);

		// sort arrays from mtgox
		$asksarr=array();
		foreach ($depth->asks as $asks)
		{
			$asksarr[]=$asks;
		}
		usort($asksarr, "cmp");

		$bidsarr=array();
		foreach ($depth->bids as $bids)
		{
			$bidsarr[]=$bids;
		}
		usort($bidsarr, "cmpr");

	
		echo "BUY OFFERS\t\t\tSELL OFFERS\n";
		for($cnt=1;$cnt<=$numoffers;$cnt++)
		{
			echo $bidsarr[$cnt][0]."\t".$bidsarr[$cnt][1]."\t\t\t".$asksarr[$cnt][0]."\t".$asksarr[$cnt][1]."\n";
		}
	}
	$refreshflag=TRUE;

	//$line = readline("Command [^M]: ");
	echo("Command [^M]: ");
	$hndl=fopen("php://stdin","r");
	$line=trim(fgets($hndl));
	fclose($hndl);
	if (trim(strtoupper($line)=='Q')) {$quitflag=TRUE;echo ".\n.\n. DONATE for FASTER development: 1N8b1uzWA7RsfVPmA2kdGSEmsv91zTRMAX\n.\n.\n";}
	if (trim(strtoupper($line)=='L')) {ListOrder($c,$user,$password);$refreshflag=FALSE;}
	$cl=explode(' ',trim($line));
	if (trim(strtoupper(@$cl[0])=='C')) // cancel
	{
		// TODO: obsługa błedów
		$a=$c->post("https://mtgox.com/code/cancelOrder.php","name=$user&pass=$password&oid=".@$cl[1]."&type=".@$cl[2]);
		echo $a."\n";
	}
	if (trim(strtoupper(@$cl[0])=='B')) // buy
	{
		// TODO: obsługa błedów
		$amount=@$cl[1];
		if (trim(strtoupper(@$cl[1]))=='ALL') $amount=$myusd/@$cl[2];
		$a=$c->post("https://mtgox.com/code/buyBTC.php","name=$user&pass=$password&amount=".$amount."&price=".@$cl[2]);
		echo $a."\n";
	}
	if (trim(strtoupper(@$cl[0])=='S')) // sell
	{
		// TODO: obsługa błedów
		$amount=@$cl[1];
		if (trim(strtoupper(@$cl[1]))=='ALL') $amount=$mybtc;
		$a=$c->post("https://mtgox.com/code/sellBTC.php","name=$user&pass=$password&amount=".$amount."&price=".@$cl[2]);
		echo $a."\n";
	}
	if (trim(strtoupper(@$cl[0])=='?')) // eval
	{
		// TODO: obsługa błedów
		@eval('echo '.@$cl[1].';');
		echo "\n";
		$refreshflag=FALSE;
	}
	if (trim(strtoupper(@$cl[0])=='SET-NUMOFFERS')) //
	{
		// TODO: obsługa błedów
		$numoffers=intval(@$cl[1]);
		$refreshflag=FALSE;
	}
	if (trim(strtoupper(@$cl[0])=='PB')) //
	{	// TODO: obsługa błedów
		echo strval(floatval(@$cl[1])-(floatval(@$cl[1])*0.013))."\n";
		$refreshflag=FALSE;
	}
	if (trim(strtoupper(@$cl[0])=='PS')) //
	{
		// TODO: obsługa błedów
		echo strval(floatval(@$cl[1])+(floatval(@$cl[1])*0.013))."\n";
		$refreshflag=FALSE;
	}
	if (trim(strtoupper(@$cl[0])=='T')) // list trades
	{
		// TODO: obsługa błedów
		$amount=@$cl[1];
		if ($amount==0) $amount=10;
		$a=$c->get("https://mtgox.com/code/data/getTrades.php");
		$j=json_decode($a);
		$cnt=count($j);
		for($i=1;$i<=$amount;$i++)
		{
			echo $j[$cnt-1]->date."\t".$j[$cnt-1]->price."\t".$j[$cnt-1]->amount."\t".$j[$cnt-1]->tid."\n";
			$cnt--;
		}
		$refreshflag=FALSE;
	}
	if (trim(strtoupper(@$cl[0])=='W')) // list trades
	{
		// TODO: obsługa błedów
		$amount=@$cl[1];
		$btca=@$cl[2];
		$a=$c->post("https://mtgox.com/code/withdraw.php","name=$user&pass=$password&group1=BTC&amount=$amount&btca=$btca");
		echo $a."\n";
	}
	if (trim(strtoupper(@$cl[0])=='H')) // list trades
	{
		// TODO: pomoc dla każdej komendy
		echo "^M (^M means Enter Key) - Refresh order list\n";
		echo "L^M - Your active orders\n";
		echo "C order_id type^M - Cancel order\n";
		echo "B amount|all price^M - Buy amount|all @ price\n";
		echo "S amount|all price^M - Sell amount|all @ price\n";
		echo "set-numoffers num^M - Numrows of orderlist (default 10)\n";
		echo "T [num]^M - Last num trades on market (default 10)\n";
		echo "W amount bitcoin_address^M - Withdraw amount to bitcoin_address\n";
		echo "PB price^M - price-1.3% of mtgox fee\n";
		echo "PS price^M - price+1.3% of mtgox fee\n";
		echo "? code ^M - eval php code i.e. ? (6.77*176.89)\n";
		echo "H^M - This Help\n";
		echo "Q^M - Quit ToyTrader\n";
		$refreshflag=FALSE;
	}

} while ($quitflag==FALSE);

?>
