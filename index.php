<?php

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
	$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $location);
	exit;
}

require_once "./PetitionDB.php";

function getIPAddress()
{
	// is IP from the share internet
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		// is IP from the proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		// is IP from the remote address
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return $ip;
}

function insertSpecialUser()
{
	// Inserts a possibly malicious user to a separate DB table
	try {
		PetitionDB::insertSpecialty($_POST["author"], $_POST["institution"], $_POST["email"], getIPAddress());
		header("Location: https://kajpami.si"); // Redirect browser to starting page
		exit; // Makes sure that code below does not get executed when we redirect.
	} catch (Exception $e) {
		$errorMessage = "Napaka v podatkovni bazi, kontaktiraj info [afna] gasperataj [pika] si!";
	}
}

// If we send a valid POST request (contains all the required data) for a signature
function insertUser()
{
	if (preg_match('/[\'<>@;]/', $_POST["author"]) || preg_match('/[\'<>;]/', $_POST["email"]) || preg_match('/[\'<>;]/', $_POST["institution"])) {
		// if one or more of the 'special characters' found in author, email or institution field
		insertSpecialUser();
	}

	// ENT_QUOTES: Will convert both double and single quotes to HTML entitites
	$postauthor = htmlspecialchars($_POST["author"], ENT_QUOTES);

	if (empty($_POST["institution"])) {
		$institution = null;
	} else {
		$institution = trim($_POST["institution"]);
		$institution = htmlspecialchars($institution, ENT_QUOTES);
	}

	if (empty($_POST["email"])) {
		$email = null;
	} else {
		$email = trim($_POST["email"];
		$email = htmlspecialchars(trim($email, ENT_QUOTES);
	}

	// notifications consent
	$consent = 0;

	if (isset($_POST["consent"])) {
		$consent = 1;
	}

	try {
		// number of existing IP's in database table of signatures
		$n_ips = 0;
		$n_ips = PetitionDB::getCountOfIPs(getIPAddress())["COUNT(*)"];

		// if there were no IP's found (COUNT() is less than 1), then insert as normal
		if ($n_ips < 1) {
			PetitionDB::insert($postauthor, $institution, $email, $consent, getIPAddress());
		}

		setcookie("signed", "1", time() + (86400 * 30), "/");
		// redirects to index with ?success=1 so that the success confirmation "modal" opens
		header("Location: index.php?success=1");
	} catch (Exception $e) {
		$errorMessage = "Napaka v podatkovni bazi, kontaktiraj info [afna] gasperataj [pika] si!";
	}
}

// $add gets set if this page was POSTed with data that we need for an insert to DB
$add = isset($_POST["author"]) && !empty($_POST["author"]) && isset($_POST["institution"]) && isset($_POST["email"]);

// if the site was POSTed, we gotta make a DB insert
if ($add) {
	function CheckCaptcha($userResponse)
	{
		// code from ReCaptcha manual
		$fields_string = '';
		$fields = array(
			'secret' => "", // TODO: set the API key that you get from Google!
			'response' => $userResponse
		);
		foreach ($fields as $key => $value)
			$fields_string .= $key . '=' . $value . '&';
		$fields_string = rtrim($fields_string, '&');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);

		$res = curl_exec($ch);
		curl_close($ch);

		return json_decode($res, true);
	}

	// Call the function CheckCaptcha
	$result = CheckCaptcha($_POST['g-recaptcha-response']);

	if ($result['success']) {
		//If the user has checked the Captcha box
		//There is no frontend validation of data.
		insertUser();
	} else {
		// If the CAPTCHA box wasn't checked
		$warningMessage = "Obrazec ste verjetno ponovno naložili oziroma ga še enkrat podpisati z istimi podatki. Verjetno je podpis že oddan - vse ok!";
	}
}

// signature was inserted at this point or the user just GETed the page.
$signed = "0";

if (isset($_COOKIE["signed"]) && $_COOKIE["signed"] == "1") {
	// if there is a signed cookie set, then $signed = 1.
	// This var will help us modify the UI (to not show signature form etc.)
	$signed = "1";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta property="og:title" content="Podpiši peticijo proti ukinitvi študentskih bonov." />
	<meta property="og:description" content="Podpiši peticijo proti ukinitvi študentskih bonov!" />

	<link rel="stylesheet" href="style.css">
	<title>Peticija proti ukinitvi študentskih bonov</title>
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="icon" type="image/x-icon" href="https://www.studentska-org.si/wp-content/themes/sos/favicon2.png">

	<!-- Google tag (gtag.js) -->
	<script async src=""></script> <!-- TODO: add src with your key! -->
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}
		gtag('js', new Date());

		gtag('config', ''); // add your key as a second argument!
		
		function onSubmit(token) {
			document.getElementById("signatureForm").submit();
		}
	</script>

	<script src="jquery-3.6.0.min.js"></script>
	<script src='https://www.google.com/recaptcha/api.js'></script>
</head>

<body class="bg-white">
	<div class="md:container mx-4 md:mx-auto md:px-10 lg:px-56">
		<?php if (isset($errorMessage)) : ?>
			<p class="w-full bg-slate-100 p-3 text-center text-red-800"><?= $errorMessage ?></p>
		<?php endif; ?>
		<?php if (isset($warningMessage)) : ?>
			<p class="w-full mt-3 bg-yellow-500 p-3 text-center text-yellow-800 rounded-lg"><?= $warningMessage ?></p>
		<?php endif; ?>

		<?php if (isset($_GET["success"])) : ?>
			<div class="px-12 py-8 mt-8 bg-gray-100 rounded-xl flex shadow-md">
				<div class="w-8/12">
					<h1 class="text-3xl font-bold text-gray-700">Hvala za vaš podpis!</h1>

					<div class="mt-5">
						<form action="/" method="get">
							<button type="submit" class="px-7 py-3 hover:bg-yellow-500 mx-auto uppercase bg-yellow-600 text-gray text-md font-semibold shadow-md rounded-lg">osveži obrazec</button>
						</form>
					</div>
				</div>
			</div>
			<script>
				setTimeout(function() {
					window.location.href = 'https://www.kajpami.si'; // the redirect goes here

				}, 3000); // 5 seconds
			</script>
		<?php endif; ?>

		<div class="md:flex flex-row mt-5">
			<div class="self-center w-full">
				<a href="https://kajpami.si/en.php" class="font-bold text-yellow-600 italic underline">Click for the English version</a>
				<h1 class="mt-4 text-4xl font-bold text-yellow-600">PETICIJA PROTI UKINITVI ŠTUDENTSKIH BONOV</h1>
			</div>
			<div class="flex-shrink-0">
				<img src="./SLO_Logotip_PNG.png" alt="logotip ŠOS" class="h-48 p-7 md:p-10 md:mx-auto">
			</div>
		</div>


		<h2 class="font-semibold text-3xl text-gray">Podpiši in jej zdravo!</h2>

		<p class="py-2 text-xl text-gray">
			Subvencionirana študentska prehrana je temeljna pravica vsake študentke in študenta, ki izhaja iz statusa študenta in ni luksuz, temveč je ključnega pomena za zdrav in uspešen študij študentov. Idejam Vlade Republike Slovenije o vzpostavitvi javnih študentskih menz in s tem ukinitvi sistema subvencionirane študentske prehrane, zato glasno nasprotujemo.
		</p>
		<p class="py-2 text-xl text-gray">
			Obstoječ sistem omogoča, da si lažje zagotovimo osnovno prehrano, ki je bistvenega pomena za naše zdravje, dobro počutje in kvaliteten študij. Vlaganje v kakovostno prehrano mladih je nujno, saj se posledice kažejo v izboljšanju telesnega in duševnega zdravja.
		</p>
		<p class="py-2 text-xl text-gray">Verjamemo, da je obstoječ sistem dober, pa vendar ima kot vsak drugi prostor za izboljšave, zato si prizadevamo za večjo dostopnost subvencionirane študentske prehrane (širitev tudi izven študijskih središč) in pestrost ponudbe. Želimo, da se vsem študentom dnevno omogoči vsaj en kvaliteten, zdrav, topel obrok, kar v osnovi tudi predstavlja koncept subvencionirane študentske prehrane.</p>
		<p class="py-2 text-xl text-gray">
			S peticijo pozivamo Vlado Republike Slovenije, da ohrani in izboljša obstoječi sistem subvencionirane študentske prehrane. Dostop do kakovostne prehrane je temeljna človekova potreba. Ker prazna vreča ne stoji pokonci, pričakujemo, da bo zdrava in raznolika prehrana dostopna vsem študentom, ne le peščici.
		</p>
		<p class="py-2 text-xl text-gray">
			Če imaš še dodatna vprašanja, lahko preveriš rubriko <a href="/faq_sl.html" class="underline">pogosto zastavljenih vprašanj</a>.
		</p>
		<p class="py-2 text-xl text-gray">
			Podpiši in jej zdravo!
		</p>

		<div id="peticija" class="px-6 md:px-12 py-8 mt-8 bg-gray-100 rounded-xl shadow-md md:flex">
			<?php if (isset($_GET["success"]) || $signed == "1") { ?>
				<div class="md:w-8/12">
					<h1 class="text-3xl font-bold text-gray-700">Hvala za vaš podpis!</h1>
					<p class="mt-3 text-gray-700">Hvala, ker prispevate podpis za boljši študentski jutri.</p>
					</p>
				</div>
			<?php } else { ?>
				<div class="md:w-8/12">
					<h1 class="text-3xl font-bold text-gray-700">Podpiši peticijo proti ukinitvi študentskih bonov.</h1>
					<p class="mt-3 text-sm text-gray-700">S podpisom se strinjate, da tvoje ime objavimo na tem spletnem mestu. Tvojega
						e-naslova (v kolikor ga podate) ne bomo izdali nikomur. Več o varstvu vaših osebnih podatkov najdete
						<a href="gdpr.html">tukaj</a>.
					</p>
				</div>
				<form action="<?= $_SERVER["PHP_SELF"] ?>" method="post" id="signatureForm" class="md:pl-3 mt-4 w-full md:w-4/12 md:mt-0">
					<input type="text" name="author" placeholder="ime in priimek" class="rounded-md w-full border-gray-200 shadow-md" required>
					<input type="text" name="institution" placeholder="institucija (neobvezno)" class="mt-2 rounded-md w-full border-gray-200 shadow-md">
					<input type="text" name="email" placeholder="e-naslov (neobvezno)" class="mt-2 rounded-md w-full border-gray-200 shadow-md">
					<div class="mt-3">
						<input type="checkbox" value="1" name="consent" id="notifications" class="appearance-none checked:bg-blue-600 checked:border-transparent rounded-md border-gray-300">
						<label for="notifications" class="ml-1 text-sm text-gray-700">V kolikor želite spremljati dogajanje na področju
							peticije, vpišite e-poštni naslov in kliknite to polje.</label>
					</div>
					<!-- add your data-sitekey! -->
					<button type="submit" value="podpiši!" data-callback="onSubmit" data-sitekey="" class="g-recaptcha mt-4 px-7 py-3 w-full hover:bg-yellow-500 bg-yellow-600 mx-auto uppercase text-gray text-md font-semibold shadow-md rounded-lg">Podpiši!</button>
				</form>
			<?php } ?>
		</div>

		<div class="px-8 md:px-12 py-8 mt-8 bg-gray-100 rounded-xl shadow-md md:flex text-gray-700">
			<div>
				<h2 class="md:w-4/12 font-bold text-3xl">Podpisnice in podpisniki</h2>
				<?php
				$n_rows = PetitionDB::getCount()["COUNT(*)"]
				?>
				<p class="mt-3">Število podpisov je že <?= $n_rows ?>!</p>
				<button class="mt-4 px-7 py-3 hover:bg-yellow-500 bg-yellow-600 mx-auto uppercase text-gray text-md font-semibold shadow-md rounded-lg" onclick="showAllSignatures()">prikaži
					vseh <?= $n_rows ?>!</button>
			</div>
			<div id="signatures" class="md:w-8/12 mt-3 md:mt-0 pl-1 h-56 overflow-hidden">
				<p>
				<!-- output of all the signatures -->
					<?php
					$i = 0;
					foreach (PetitionDB::getAll() as $signature) {
						if ($i < $n_rows - 1) { ?>
							<?= $signature["author"] ?><?= isset($signature["institution"]) ? (" (" . $signature['institution'] . ")") : "" ?>,
						<?php } else { ?>
							<?= $signature["author"] ?><?= isset($signature["institution"]) ? (" (" . $signature['institution'] . ")") : "" ?>
						<?php } ?>
					<?php
						$i = $i + 1;
					} ?>
				</p>
			</div>
		</div>
	</div>
</body>
<script>
	function showAllSignatures() {
		let signatures = $("#signatures");
		signatures.toggleClass("overflow-hidden");
		signatures.toggleClass("h-56");
	}

	function showAllInstitutions() {
		let institutions = $("#allinstitutions");
		let btn = $("#toggleInstitutions");
		let btnTxt = btn.html();

		if (institutions.hasClass("hidden")) {
			institutions.fadeIn()
			institutions.toggleClass("hidden");
			btn.html("Skrij institucije")
		} else {
			institutions.fadeOut();
			institutions.toggleClass("hidden");
			btn.html("Prikaži vse institucije ...")
		} 
	}
</script>

</html>