(() => {
    const translations = new Map([
        ['Language', 'Wika'],
        ['Light mode', 'Maliwanag na mode'],
        ['Dark mode', 'Madilim na mode'],
        ['Switch to light mode', 'Lumipat sa maliwanag na mode'],
        ['Switch to dark mode', 'Lumipat sa madilim na mode'],
        ['Log in | PUPick AI', 'Mag-log in | PUPick AI'],
        ['Create account | PUPick AI', 'Gumawa ng account | PUPick AI'],
        ['Dashboard | PUPick AI', 'Dashboard | PUPick AI'],
        ['Browse items | PUPick AI', 'Tingnan ang mga gamit | PUPick AI'],
        ['My reports | PUPick AI', 'Mga report ko | PUPick AI'],
        ['Notifications | PUPick AI', 'Mga abiso | PUPick AI'],
        ['MENU', 'MENU'],
        ['HELP', 'TULONG'],
        ['Dashboard', 'Pangkalahatang-ideya'],
        ['Report lost item', 'I-report ang nawawalang gamit'],
        ['Report found item', 'I-report ang nakitang gamit'],
        ['Browse items', 'Tingnan ang mga gamit'],
        ['My reports', 'Mga report ko'],
        ['Notifications', 'Mga abiso'],
        ['Help chat', 'Chat para sa tulong'],
        ['Admin panel', 'Admin panel'],
        ['Manage items', 'Pamahalaan ang mga gamit'],
        ['Manage Items', 'Pamahalaan ang mga gamit'],
        ['Review recent lost and found reports, update their status, or correct report details. The category is the item label shown to students. Open = active report; matched = item reserved or connected; returned = handoff complete.', 'Suriin ang mga bagong report, baguhin ang katayuan, o itama ang detalye. Ang kategorya ang label ng gamit na makikita ng mga estudyante. Open = aktibo ang report; matched = nakareserba o may katugma; returned = naibalik na ang gamit.'],
        ['Manage claims', 'Pamahalaan ang mga claim'],
        ['Manage Claims', 'Pamahalaan ang mga claim'],
        ['Overview', 'Buod'],
        ['Log out', 'Mag-log out'],
        ['Log Out', 'Mag-log out'],
        ['Sign in as', 'Mag-sign in bilang'],
        ['Student', 'Estudyante'],
        ['Administrator', 'Administrator'],
        ['Administrator (full access)', 'Administrator (buong access)'],
        ['Welcome back', 'Maligayang pagbabalik'],
        ['Log in to manage your lost-and-found reports.', 'Mag-log in upang pamahalaan ang iyong mga report ng nawawala at natagpuang gamit.'],
        ['Account created. You can now log in.', 'Nagawa na ang account. Maaari ka nang mag-log in.'],
        ['Administrator account created. Sign in using the administrator account type.', 'Nagawa na ang administrator account. Mag-sign in gamit ang uri ng administrator account.'],
        ['Choose whether you are signing in as a student or administrator.', 'Piliin kung magla-log in ka bilang estudyante o administrator.'],
        ['Enter your email and password.', 'Ilagay ang iyong email at password.'],
        ['This account does not match the selected account type.', 'Hindi tugma ang account na ito sa napiling uri ng account.'],
        ['Incorrect email or password.', 'Mali ang email o password.'],
        ['Email address', 'Email address'],
        ['Password', 'Password'],
        ['Log in', 'Mag-log in'],
        ['New to PUPick AI?', 'Bago ka sa PUPick AI?'],
        ['Create an account', 'Gumawa ng account'],
        ['Create your account', 'Gumawa ng iyong account'],
        ['Create a student or administrator account. Administrator access is granted immediately.', 'Gumawa ng student o administrator account. Agad na ibinibigay ang administrator access.'],
        ['Account type', 'Uri ng account'],
        ['Only select Administrator if you are authorized. This option grants full admin access without approval.', 'Piliin lamang ang Administrator kung awtorisado ka. Nagbibigay ito ng buong admin access nang walang approval.'],
        ['Full name', 'Buong pangalan'],
        ['Student number', 'Numero ng estudyante'],
        ['(required for students)', '(kinakailangan para sa mga estudyante)'],
        ['Confirm password', 'Kumpirmahin ang password'],
        ['Create account', 'Gumawa ng account'],
        ['Already have an account?', 'May account ka na ba?'],
        ['Please complete all required fields.', 'Pakikumpleto ang lahat ng kinakailangang field.'],
        ['Choose Student or Administrator access.', 'Piliin ang access bilang estudyante o administrator.'],
        ['Enter your student number.', 'Ilagay ang iyong student number.'],
        ['Enter a valid email address.', 'Maglagay ng wastong email address.'],
        ['Password must be at least 8 characters.', 'Dapat hindi bababa sa 8 character ang password.'],
        ['Passwords do not match.', 'Hindi magkatugma ang mga password.'],
        ['That email or student number is already registered.', 'Nakarehistro na ang email o student number na iyan.'],
        ['PUP LOST AND FOUND', 'PUP NAWAWALA AT NATAGPUAN'],
        ['PUPICK AI / OVERVIEW', 'PUPICK AI / BUOD'],
        ['Signed in as', 'Naka-sign in bilang'],
        ['WELCOME TO PUPICK AI', 'MALIGAYANG PAGDATING SA PUPICK AI'],
        ['Lost something? Found something?', 'May nawala o may nakita ka ba?'],
        ['Help reconnect PUP students with their belongings.', 'Tulungan ang mga estudyante ng PUP na mahanap muli ang kanilang mga gamit.'],
        ['View possible matches', 'Tingnan ang posibleng magkatugma'],
        ['Community lost reports', 'Mga report ng nawawalang gamit'],
        ['Open and closed reports', 'Mga bukas at saradong report'],
        ['Community found reports', 'Mga report ng natagpuang gamit'],
        ['Items reported found', 'Mga gamit na ini-report na natagpuan'],
        ['View your reports', 'Tingnan ang iyong mga report'],
        ['My claims', 'Mga claim ko'],
        ['Claims submitted by you', 'Mga claim na ipinasa mo'],
        ['Unread notifications', 'Mga hindi pa nababasang abiso'],
        ['Updates for your account', 'Mga update para sa iyong account'],
        ['Recently reported lost items', 'Mga bagong report ng nawawalang gamit'],
        ['Latest reports from the community', 'Pinakabagong mga report mula sa komunidad'],
        ['Recently reported found items', 'Mga bagong report ng natagpuang gamit'],
        ['Items that may belong to someone', 'Mga gamit na maaaring pag-aari ng iba'],
        ['Item', 'Gamit'],
        ['Category', 'Kategorya'],
        ['Category *', 'Kategorya *'],
        ['Choose a suggestion or type your own', 'Pumili sa mga mungkahi o mag-type ng sarili mong kategorya'],
        ['No suitable category? Type your own and continue.', 'Walang angkop na kategorya? Mag-type ng sarili mong kategorya at magpatuloy.'],
        ['Color (optional)', 'Kulay (opsyonal)'],
        ['(optional)', '(opsyonal)'],
        ['For example: black', 'Halimbawa: itim'],
        ['For example: Main building, library', 'Halimbawa: pangunahing gusali, aklatan'],
        ['For example: library, canteen', 'Halimbawa: aklatan, kantina'],
        ['Where did you last see it? *', 'Saan mo ito huling nakita? *'],
        ['Where did you find it? *', 'Saan mo ito nakita? *'],
        ['A building, room, or nearby landmark is helpful.', 'Makakatulong ang pangalan ng gusali, silid, o kalapit na lugar.'],
        ['What does it look like? *', 'Ano ang itsura nito? *'],
        ['Add details such as brand, size, stickers, or other features.', 'Magdagdag ng detalye gaya ng brand, laki, sticker, o iba pang katangian.'],
        ['Do not include passwords or private account details.', 'Huwag maglagay ng password o pribadong detalye ng account.'],
        ['Add details such as brand, size, or other visible features. Do not reveal a private serial number or security detail.', 'Magdagdag ng detalye gaya ng brand, laki, o ibang nakikitang katangian. Huwag ibunyag ang pribadong serial number o security detail.'],
        ['Keep unique ownership details private so the owner can verify them.', 'Ilihim ang natatanging detalye para magamit ito ng may-ari sa pagpapatunay.'],
        ['Tell us what you lost', 'Ikuwento sa amin ang nawala sa iyo'],
        ['Tell us what you found', 'Ikuwento sa amin ang nakita mo'],
        ['Choose a category, then add a few details so the right owner can recognize the item. Fields marked * are required.', 'Pumili ng kategorya at magdagdag ng ilang detalye para makilala ito ng may-ari. Kinakailangan ang mga field na may *.'],
        ['Choose a category, then add a few details so we can help return it to its owner. Fields marked * are required.', 'Pumili ng kategorya at magdagdag ng ilang detalye para maibalik ito sa may-ari. Kinakailangan ang mga field na may *.'],
        ['Submit lost-item report', 'Ipasa ang report ng nawawalang gamit'],
        ['Submit found-item report', 'Ipasa ang report ng natagpuang gamit'],
        ['Please complete the required fields to submit your report.', 'Pakikumpleto ang mga kinakailangang field upang maipasa ang report.'],
        ['Category must be 80 characters or fewer.', 'Hindi dapat lumampas sa 80 character ang kategorya.'],
        ['Description must be 1,000 characters or fewer.', 'Hindi dapat lumampas sa 1,000 character ang paglalarawan.'],
        ['Location or color is too long. Please shorten it and try again.', 'Masyadong mahaba ang lokasyon o kulay. Paikliin ito at subukang muli.'],
        ['Please choose a valid date that is not in the future.', 'Pumili ng wastong petsa na hindi sa hinaharap.'],
        ['STEP 1 OF 1 · LOST ITEM', 'HAKBANG 1 SA 1 · NAWAWALANG GAMIT'],
        ['STEP 1 OF 1 · FOUND ITEM', 'HAKBANG 1 SA 1 · NATAGPUANG GAMIT'],
        ['Location', 'Lokasyon'],
        ['Date', 'Petsa'],
        ['Date lost', 'Petsa ng pagkawala'],
        ['Date lost *', 'Petsa ng pagkawala *'],
        ['Date found', 'Petsa ng pagkakatagpo'],
        ['Date found *', 'Petsa ng pagkakatagpo *'],
        ['Status', 'Katayuan'],
        ['No lost items reported yet.', 'Wala pang nai-report na nawawalang gamit.'],
        ['No found items reported yet.', 'Wala pang nai-report na natagpuang gamit.'],
        ['PUPick AI · Helping belongings find their way home.', 'PUPick AI · Tinutulungan ang mga gamit na makabalik sa may-ari.'],
        ['COMMUNITY ITEMS', 'MGA GAMIT SA KOMUNIDAD'],
        ['Search by keyword, report type, status, or the date the item was lost or found.', 'Maghanap gamit ang salita, uri o katayuan ng report, o petsa kung kailan nawala o natagpuan ang gamit.'],
        ['All items', 'Lahat ng gamit'],
        ['Lost items', 'Mga nawawalang gamit'],
        ['Found items', 'Mga natagpuang gamit'],
        ['Report type', 'Uri ng report'],
        ['Available only', 'Mga available lamang'],
        ['Matched', 'May katugma'],
        ['Returned', 'Naibalik'],
        ['All statuses', 'Lahat ng katayuan'],
        ['Search', 'Maghanap'],
        ['Category, details, color, or location', 'Kategorya, detalye, kulay, o lokasyon'],
        ['Date from', 'Petsa mula'],
        ['Date to', 'Petsa hanggang'],
        ['Apply filters', 'Ilapat ang mga filter'],
        ['Reset filters', 'I-reset ang mga filter'],
        ['reports', 'report'],
        ['No lost items match these filters.', 'Walang nawawalang gamit na tumutugma sa mga filter na ito.'],
        ['No found items match these filters.', 'Walang natagpuang gamit na tumutugma sa mga filter na ito.'],
        ['Enter a valid date to filter reports.', 'Maglagay ng wastong petsa upang i-filter ang mga report.'],
        ['The start date must be on or before the end date.', 'Dapat ang petsa ng pagsisimula ay katumbas o mas maaga kaysa petsa ng pagtatapos.'],
        ['Reported by', 'Ini-report ni'],
        ['Claim this item', 'I-claim ang gamit na ito'],
        ['No ', 'Walang '],
        [' items found. Try another filter or report an item.', ' na gamit. Subukan ang ibang filter o mag-report ng gamit.'],
        ['YOUR ACTIVITY', 'IYONG AKTIBIDAD'],
        ['My reports and claims', 'Mga report at claim ko'],
        ['Your ', 'Ang iyong '],
        [' submission was saved.', ' ay naisumite.'],
        ['My lost-item reports', 'Mga report ko ng nawawalang gamit'],
        ['Items you reported missing', 'Mga gamit na ini-report mong nawawala'],
        ['My found-item reports', 'Mga report ko ng natagpuang gamit'],
        ['Items you reported finding', 'Mga gamit na ini-report mong nakita'],
        ['My claims', 'Mga claim ko'],
        ['Claims you submitted for found items', 'Mga claim mo para sa mga natagpuang gamit'],
        ['Message', 'Mensahe'],
        ['Submitted', 'Ipinasa'],
        ['No lost reports yet.', 'Wala ka pang report ng nawawalang gamit.'],
        ['No found reports yet.', 'Wala ka pang report ng natagpuang gamit.'],
        ['No claims submitted yet.', 'Wala ka pang ipinapasang claim.'],
        ['UPDATES', 'MGA UPDATE'],
        ['Mark all as read', 'Markahang nabasa lahat'],
        ["You don't have any notifications yet.", 'Wala ka pang mga abiso.'],
        ['REPORT AN ITEM', 'MAG-REPORT NG GAMIT'],
        ['Report a lost item', 'Mag-report ng nawawalang gamit'],
        ['Report a found item', 'Mag-report ng natagpuang gamit'],
        ['Item details', 'Mga detalye ng gamit'],
        ['Found item details', 'Mga detalye ng natagpuang gamit'],
        ['Describe the item and where you last saw it.', 'Ilarawan ang gamit at kung saan mo ito huling nakita.'],
        ['Tell the community what you found and where.', 'Ipaalam sa komunidad kung ano ang nakita mo at kung saan.'],
        ['Choose category', 'Pumili ng kategorya'],
        ['Electronics', 'Elektroniko'],
        ['Wallet / ID', 'Pitaka / ID'],
        ['Bag', 'Bag'],
        ['Clothing', 'Damit'],
        ['Books / School supplies', 'Libro / Gamit sa paaralan'],
        ['Keys', 'Susi'],
        ['Accessories', 'Mga aksesorya'],
        ['Other', 'Iba pa'],
        ['Color', 'Kulay'],
        ['Documents', 'Mga dokumento'],
        ['Date found *', 'Petsa ng pagkakatagpo *'],
        ['Where was it found? *', 'Saan ito nakita? *'],
        ['Last known location *', 'Huling lokasyon *'],
        ['Description *', 'Paglalarawan *'],
        ['Submit found report', 'Ipasa ang report ng natagpuang gamit'],
        ['Submit lost report', 'Ipasa ang report ng nawawalang gamit'],
        ['Please fill in all required fields.', 'Pakipunan ang lahat ng kinakailangang field.'],
        ['e.g. black', 'hal. itim'],
        ['e.g. library, canteen', 'hal. aklatan, kantina'],
        ['e.g. Main building, library', 'hal. pangunahing gusali, aklatan'],
        ['Describe its appearance without revealing private identifying details', 'Ilarawan ang itsura nito nang hindi inilalantad ang pribadong detalye'],
        ['Distinctive features that can help identify your item', 'Mga natatanging katangiang makatutulong makilala ang iyong gamit'],
        ['← Dashboard', '← Dashboard'],
        ['ITEM CLAIM', 'PAG-CLAIM NG GAMIT'],
        ['Already Returned', 'Naibalik na'],
        ['Already Matched', 'May kumpirmadong katugma na'],
        ['This item has already been returned and cannot be claimed again.', 'Naibalik na ang gamit na ito at hindi na ito maaaring i-claim muli.'],
        ['This item already has a confirmed match and is unavailable for new claims.', 'May kumpirmadong katugma na ang gamit na ito at hindi na ito maaaring i-claim.'],
        ['This item is currently unavailable for claims.', 'Hindi kasalukuyang maaaring i-claim ang gamit na ito.'],
        ['Provide details that help verify ownership. Avoid sharing passwords or sensitive information.', 'Magbigay ng mga detalyeng makatutulong patunayan na sa iyo ito. Huwag magbahagi ng password o sensitibong impormasyon.'],
        ['Why do you believe this item is yours?', 'Bakit sa tingin mo ay sa iyo ang gamit na ito?'],
        ['Submit Claim', 'Ipasa ang claim'],
        ['Supporting photo', 'Larawang pandagdag'],
        ['(optional, JPG/PNG/WEBP, up to 5 MB)', '(opsyonal, JPG/PNG/WEBP, hanggang 5 MB)'],
        ['Explain how you can identify or verify the item. You may also attach a photo that supports your claim. It will only be visible to you, the finder, and administrators. Do not upload IDs, passwords, or other sensitive personal information.', 'Ipaliwanag kung paano mo makikilala o mapapatunayang sa iyo ang gamit. Maaari ka ring maglakip ng larawang susuporta sa claim mo. Ikaw, ang nakakita, at mga administrator lamang ang makakakita nito. Huwag mag-upload ng ID, password, o iba pang sensitibong personal na impormasyon.'],
        ['Use a photo that helps show ownership. The photo supports review but does not prove ownership on its own.', 'Gumamit ng larawang makatutulong magpakita ng pagmamay-ari. Pantulong lamang ito sa pagsusuri at hindi sapat na patunay nang mag-isa.'],
        ['A clear photo can help identify the item. Avoid showing personal information.', 'Makakatulong makilala ang gamit sa malinaw na larawan. Iwasang maisama ang personal na impormasyon.'],
        ['Photograph only the found item. Hide unique details that an owner should use to verify it.', 'Kunan lamang ng larawan ang natagpuang gamit. Huwag isama ang natatanging detalyeng dapat gamitin ng may-ari sa pagpapatunay.'],
        ['Photo', 'Larawan'],
        ['View photo', 'Tingnan ang larawan'],
        ['Photo ', 'Larawan '],
        ['Please keep your message to 2,000 characters or fewer.', 'Hindi dapat lumampas sa 2,000 character ang mensahe.'],
        ['Cancel', 'Kanselahin'],
        ['Back to found items', 'Bumalik sa mga natagpuang gamit'],
        ['Invalid found item.', 'Hindi wastong natagpuang gamit.'],
        ['Found item not found.', 'Hindi makita ang natagpuang gamit.'],
        ['You cannot claim an item you reported finding.', 'Hindi mo maaaring i-claim ang gamit na ini-report mong nakita.'],
        ['This item has already been returned to its owner.', 'Naibalik na ang gamit na ito sa may-ari.'],
        ['You have already submitted a claim for this item. It is awaiting admin review.', 'Nakapagpasa ka na ng claim para sa gamit na ito. Naghihintay ito ng pagsusuri ng admin.'],
        ['Your claim for this item has already been approved. Please check your notifications for updates.', 'Naaprubahan na ang claim mo para sa gamit na ito. Tingnan ang iyong mga abiso para sa update.'],
        ['Please explain why you believe this item belongs to you.', 'Ipaliwanag kung bakit sa tingin mo ay sa iyo ang gamit na ito.'],
        ['This item is no longer available for claims.', 'Hindi na maaaring i-claim ang gamit na ito.'],
        ['You already have an active claim for this item.', 'May aktibo ka nang claim para sa gamit na ito.'],
        ['Your claim could not be submitted. Please try again.', 'Hindi naipasa ang claim mo. Pakisubukan muli.'],
        ['PUPick Help Chat', 'Chat para sa Tulong ng PUPick'],
        ['PUPICK HELP', 'TULONG NG PUPICK'],
        ['Chat with PUPick', 'Makipag-chat sa PUPick'],
        ['PUPick Help Assistant', 'Katulong ng PUPick'],
        ['FAQ assistant · Offline', 'Katulong sa mga FAQ · Offline'],
        ['Clear chat', 'Burahin ang chat'],
        ['Try asking', 'Subukan itanong'],
        ['Your question', 'Iyong tanong'],
        ['Send', 'Ipadala'],
        ['Type in a supported language...', 'Mag-type sa sinusuportahang wika...'],
        ['How do I search for an item?', 'Paano ako maghahanap ng gamit?'],
        ['Find a reported item', 'Maghanap ng nai-report na gamit'],
        ['Guided item search', 'Gabay na paghahanap ng gamit'],
        ['Lost', 'Nawawala'],
        ['Found', 'Nakita'],
        ['Either', 'Alinman'],
        ['Search reports', 'Maghanap sa mga report'],
        ['What kind of report are you looking for?', 'Anong uri ng report ang hinahanap mo?'],
        ['What item or details should I search for? For example: blue wallet, library.', 'Anong gamit o detalye ang hahanapin ko? Halimbawa: asul na pitaka, aklatan.'],
        ['Here is the report search. Review the results; they do not confirm ownership.', 'Narito ang paghahanap ng report. Suriin ang mga resulta; hindi nito pinatutunayan kung kanino ang gamit.'],
        ['Was my wallet reported?', 'May nai-report ba na pitaka ko?'],
        ['What if there are no results?', 'Ano ang gagawin kapag walang resulta?'],
        ['How do I review a claim?', 'Paano ako magsusuri ng claim?'],
        ['How do I export a report?', 'Paano ako mag-e-export ng report?'],
        ['Browse all reports', 'Tingnan ang lahat ng report'],
        ['Report a lost item', 'Mag-report ng nawawalang gamit'],
        ['Report a found item', 'Mag-report ng natagpuang gamit'],
        ['Browse found items', 'Tingnan ang mga natagpuang gamit'],
        ['View my reports and claims', 'Tingnan ang mga report at claim ko'],
        ['View notifications', 'Tingnan ang mga abiso'],
        ['Compare possible matches', 'Ihambing ang mga posibleng tugma'],
        ['Open notifications', 'Buksan ang mga abiso'],
        ['Go to sign in', 'Pumunta sa pag-sign in'],
        ['Review claims', 'Suriin ang mga claim'],
        ['Download PDF report', 'I-download ang PDF report'],
        ['Attach a photo', 'Maglakip ng larawan'],
        ['Open my reports', 'Buksan ang mga report ko'],
        ['ADMINISTRATION', 'PAMAMAHALA'],
        ['Admin Dashboard', 'Dashboard ng Admin'],
        ['PUPICK AI · CONTROL CENTER', 'PUPICK AI · SENTRO NG PAMAMAHALA'],
        ['Monitor reports, review claims, and track possible item matches.', 'Subaybayan ang mga report, suriin ang mga claim, at tingnan ang posibleng magkatugmang gamit.'],
        ['Download Excel CSV', 'I-download ang Excel CSV'],
        ['Download Excel workbook (.xlsx)', 'I-download ang Excel workbook (.xlsx)'],
        ['Download PDF', 'I-download ang PDF'],
        ['pending', 'naghihintay'],
        ['Applicant', 'Aplikante'],
        ['Student Number', 'Numero ng estudyante'],
        ['Requested', 'Hiniling'],
        ['Action', 'Aksyon'],
        ['Approve', 'Aprubahan'],
        ['Reject', 'Tanggihan'],
        ['Save', 'I-save'],
        ['Latest AI Matches', 'Pinakabagong AI match'],
        ['Potential connections between lost and found reports', 'Mga posibleng magkatugmang report ng nawawala at natagpuang gamit'],
        ['No matches recorded yet.', 'Wala pang naitalang magkatugma.'],
        ['Manage Claims', 'Pamahalaan ang mga claim'],
        ['1. Read the claimant’s message. 2. Approve to reserve, or reject if details do not match. 3. Mark returned only after the item is handed back.', '1. Basahin ang mensahe ng nag-claim. 2. Aprubahan para ireserba, o tanggihan kung hindi tugma ang detalye. 3. Markahang naibalik lamang kapag naibigay na ang gamit.'],
        ['Review each claimant’s message and photo, if provided. Approve to reserve, reject if details do not match, and mark returned only after handoff.', 'Suriin ang mensahe at larawan ng nag-claim, kung mayroon. Aprubahan upang ireserba, tanggihan kung hindi tugma ang detalye, at markahang naibalik lamang kapag naiabot na ang gamit.'],
        ['Claimant', 'Nag-claim'],
        ['Claim message', 'Mensahe ng nag-claim'],
        ['Submitted', 'Ipinasa'],
        ['View supporting photo', 'Tingnan ang kalakip na larawan'],
        ['Review decision', 'Pasya sa pagsusuri'],
        ['No claims submitted yet.', 'Wala pang naipapasang claim.'],
        ['Approve & reserve', 'Aprubahan at ireserba'],
        ['Save decision', 'I-save ang pasya'],
        ['Confirm item returned', 'Kumpirmahing naibalik na'],
        ['Mark returned', 'Markahang naibalik'],
        ['No further action', 'Wala nang kailangang gawin'],
        ['No item reports found yet.', 'Wala pang nakitang report ng gamit.'],
        ['No item reports found', 'Walang nakitang report ng gamit'],
        ['Manage Items | PUPick AI', 'Pamahalaan ang mga gamit | PUPick AI'],
        ['Search items', 'Maghanap ng gamit'],
        ['Filter by item type', 'I-filter ayon sa uri ng gamit'],
        ['Item details', 'Detalye ng gamit'],
        ['View description and color', 'Tingnan ang paglalarawan at kulay'],
        ['Color:', 'Kulay:'],
        ['Edit report details', 'I-edit ang detalye ng report'],
        ['Delete report', 'Burahin ang report'],
        ['reports shown', 'report ang ipinapakita'],
        ['Apply filters', 'Ilapat ang mga filter'],
        ['Reset filters', 'I-reset ang mga filter'],
        ['Attach a photo', 'Maglakip ng larawan'],
        ['Attach a photo to check reported items', 'Maglakip ng larawan upang tingnan kung may naiulat na gamit'],
        ['You can also attach a photo to compare it with photos on reported items.', 'Maaari ka ring maglakip ng larawan upang ihambing ito sa mga larawan ng naiulat na gamit.'],
        ['Ask in English, Filipino/Taglish, Spanish, French, German, Portuguese, Indonesian, Chinese, or Japanese. You can also attach a photo to compare it with photos on reported items.', 'Magtanong sa English, Filipino/Taglish, Spanish, French, German, Portuguese, Indonesian, Chinese, o Japanese. Maaari ka ring maglakip ng larawan upang ihambing ito sa mga larawan ng naiulat na gamit.'],
        ['Checks up to the latest 50 lost and 50 found reports that include photos.', 'Sinusuri ang hanggang 50 pinakabagong report ng nawawala at 50 report ng natagpuang gamit na may larawan.'],
        ['Checking report photos…', 'Sinusuri ang mga larawan ng report…'],
        ['Photo attached for report search.', 'Naglakip ng larawan upang maghanap sa mga report.'],
        ['Possible visual matches:', 'Mga posibleng magkahawig na larawan:'],
        ['No close visual match was found. The item may still have been reported.', 'Walang nakitang malapit na pagkakahawig. Posibleng naiulat pa rin ang gamit.'],
        ['Photo comparison complete.', 'Tapos na ang paghahambing ng mga larawan.'],
        ['Report photos could not be compared. Try Browse items to search reports by category or description.', 'Hindi maihambing ang mga larawan ng report. Tingnan ang Browse items at maghanap ayon sa kategorya o paglalarawan.'],
        ['Selected photo preview', 'Preview ng napiling larawan'],
        ['Visual similarity is only a clue, not object recognition or proof. Photos and viewing angles can differ; check the report details before claiming an item.', 'Palatandaan lamang ang pagkakahawig ng larawan, hindi ito pagkilala sa bagay o patunay. Maaaring magkaiba ang kuha at anggulo; suriin muna ang detalye bago mag-claim.'],
        ['There are currently no lost or found reports with photos to compare. Try Browse items to check reports without photos.', 'Walang report ng nawawala o natagpuang gamit na may larawang maihahambing sa ngayon. Tingnan ang Browse items para sa mga report na walang larawan.'],
        ['Use a JPG, PNG, or WEBP photo.', 'Gumamit ng JPG, PNG, o WEBP na larawan.'],
        ['Choose a photo smaller than 5 MB.', 'Pumili ng larawang mas maliit sa 5 MB.'],
        ['Photo matching compares visual appearance with existing report photos only. It is approximate, may miss the same item photographed differently, and does not check reports without photos. The selected photo is processed in your browser and is not saved or sent to an external service. Do not include passwords or sensitive information.', 'Inihahambing lamang ng photo matching ang itsura sa mga larawan ng kasalukuyang report. Tantiya lamang ito; maaaring hindi makilala ang parehong gamit kung iba ang kuha, at hindi nito nasusuri ang mga report na walang larawan. Pinoproseso ang larawan sa browser mo at hindi ito sine-save o ipinapadala sa external service. Huwag magsama ng password o sensitibong impormasyon.'],
        ['Report', 'Report'],
        ['Type', 'Uri'],
        ['Description', 'Paglalarawan'],
        ['Manage', 'Pamahalaan'],
        ['Reset', 'I-reset'],
        ['All Items', 'Lahat ng report'],
        ['Lost Items', 'Mga nawawalang gamit'],
        ['Found Items', 'Mga natagpuang gamit'],
        ['No items found.', 'Walang nahanap na report.'],
        ['Save Changes', 'I-save ang mga pagbabago'],
        ['Change Status', 'Baguhin ang katayuan'],
        ['Edit Item', 'I-edit ang report'],
        ['Search category, details, location, or report ID...', 'Maghanap ayon sa kategorya, detalye, lokasyon, o report ID...'],
        ['All types', 'Lahat ng uri'],
        ['All statuses', 'Lahat ng katayuan'],
        ['Filter by status', 'I-filter ayon sa katayuan'],
        ['Edit', 'I-edit'],
        ['Delete', 'Burahin'],
        ['Update', 'I-update'],
        ['No items match your filters.', 'Walang gamit na tumutugma sa mga filter mo.'],
        ['Possible matches', 'Mga posibleng magkatugma'],
        ['Compare lost and found reports with similar details. Review the information carefully before contacting a finder or submitting a claim.', 'Paghambingin ang mga report ng nawawala at natagpuang gamit na magkahawig ang detalye. Suriing mabuti ang impormasyon bago makipag-ugnayan o magpasa ng claim.'],
        ['← Back to dashboard', '← Bumalik sa dashboard'],
        ['Similarity guide', 'Gabay sa pagkakatulad'],
        ['High Similarity', 'Mataas ang pagkakatulad'],
        ['Moderate Similarity', 'Katamtaman ang pagkakatulad'],
        ['Low Similarity', 'Mababa ang pagkakatulad'],
        ['Similarity estimate', 'Tinatayang pagkakatulad'],
        ['Lost report · #', 'Report ng nawawala · #'],
        ['Found report · #', 'Report ng natagpuan · #'],
        ['Last seen:', 'Huling nakita:'],
        ['No possible matches yet.', 'Wala pang posibleng magkatugma.'],
        ['Add or update an open lost or found report with clear item details, then check again.', 'Magdagdag o mag-update ng bukas na report na may malinaw na detalye, saka tingnan muli.'],
        ['AI Matches | PUPick', 'AI Matches | PUPick'],
        ['Access denied. Admin account required.', 'Tinanggihan ang access. Kailangan ng admin account.'],
        ['Access denied. Administrators only.', 'Tinanggihan ang access. Para lamang ito sa mga administrator.'],
        ['Invalid item type.', 'Hindi wastong uri ng gamit.'],
        ['Invalid item ID.', 'Hindi wastong ID ng gamit.'],
        ['Invalid status selected.', 'Hindi wastong katayuan ang pinili.'],
        ['Enter a category using 1 to 80 characters.', 'Maglagay ng kategoryang may 1 hanggang 80 character.'],
        ['Your session expired. Refresh the page and try again.', 'Nag-expire ang session mo. I-refresh ang pahina at subukang muli.'],
        ['Report status updated.', 'Na-update ang katayuan ng report.'],
        ['Report details saved.', 'Na-save ang detalye ng report.'],
        ['Report deleted.', 'Nabura ang report.'],
        ['Add or edit report details', 'Magdagdag o mag-edit ng detalye ng report'],
        ['Save status', 'I-save ang katayuan'],
        ['Please enter a valid date.', 'Maglagay ng wastong petsa.'],
        ['Invalid action.', 'Hindi wastong aksyon.'],
        ['The action could not be completed. Please check the item and database, then try again.', 'Hindi nakumpleto ang aksyon. Suriin ang gamit at database, saka subukan muli.'],
        ['Unable to load items. Please check your database table and column names.', 'Hindi ma-load ang mga gamit. Suriin ang mga pangalan ng table at column sa database.'],
        ['open', 'bukas'],
        ['lost', 'nawawala'],
        ['found', 'natagpuan'],
        ['returned', 'naibalik'],
        ['approved', 'naaprubahan'],
        ['rejected', 'tinanggihan'],
        ['completed', 'kumpleto']
    ].map(([english, tagalog]) => [english.toLocaleLowerCase(), tagalog]));

    const storageKey = 'pupick-language';
    const textSources = new WeakMap();
    const attributeSources = new WeakMap();
    const orderedTranslations = Array.from(translations.entries())
        .sort(([first], [second]) => second.length - first.length);
    const root = document.documentElement;
    let language = 'en';

    try {
        const storedLanguage = localStorage.getItem(storageKey);
        if (storedLanguage === 'en' || storedLanguage === 'tl') {
            language = storedLanguage;
        }
    } catch (error) {
        console.warn('PUPick could not read the saved language preference.', error);
    }

    function keyFor(value) {
        return value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();
    }

    function localize(source) {
        if (language !== 'tl') {
            return source;
        }

        const leadingSpace = source.match(/^\s*/)[0];
        const trailingSpace = source.match(/\s*$/)[0];
        const exactMatch = translations.get(keyFor(source));
        if (exactMatch) {
            return leadingSpace + exactMatch + trailingSpace;
        }

        const core = source.trim();
        const lowerCore = core.toLocaleLowerCase();
        for (const [english, tagalog] of orderedTranslations) {
            if (!lowerCore.endsWith(english)) {
                continue;
            }

            const prefix = core.slice(0, core.length - english.length);
            if (!/[a-z0-9]/i.test(prefix)) {
                return leadingSpace + prefix + tagalog + trailingSpace;
            }
        }

        return source;
    }

    function isIgnored(node) {
        const element = node.parentElement;
        return !element
            || element.closest('.language-switcher')
            || element.closest('script, style, noscript, textarea');
    }

    function translateTextNode(node) {
        if (isIgnored(node) || !node.nodeValue.trim()) {
            return;
        }

        if (!textSources.has(node)) {
            textSources.set(node, node.nodeValue);
        }

        const source = textSources.get(node);
        const translated = localize(source);

        if (node.nodeValue !== translated) {
            node.nodeValue = translated;
        }
    }

    function translateAttributes(element) {
        if (!(element instanceof Element) || element.closest('.language-switcher')) {
            return;
        }

        ['placeholder', 'title', 'aria-label', 'alt', 'value', 'data-label'].forEach((attribute) => {
            if (
                attribute === 'value'
                && (
                    element instanceof HTMLInputElement
                    || element instanceof HTMLButtonElement
                    || element instanceof HTMLSelectElement
                    || element instanceof HTMLOptionElement
                    || element instanceof HTMLTextAreaElement
                )
            ) {
                return;
            }
            if (!element.hasAttribute(attribute)) {
                return;
            }

            let sources = attributeSources.get(element);
            if (!sources) {
                sources = new Map();
                attributeSources.set(element, sources);
            }
            if (!sources.has(attribute)) {
                sources.set(attribute, element.getAttribute(attribute));
            }

            const source = sources.get(attribute);
            const translated = language === 'tl'
                ? translations.get(keyFor(source)) || source
                : source;
            if (element.getAttribute(attribute) !== translated) {
                element.setAttribute(attribute, translated);
            }
        });
    }

    function translateTree(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            translateTextNode(node);
            return;
        }
        if (node.nodeType !== Node.ELEMENT_NODE && node !== document) {
            return;
        }

        if (node instanceof Element) {
            translateAttributes(node);
        }
        const walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT);
        let textNode;
        while ((textNode = walker.nextNode())) {
            translateTextNode(textNode);
        }
        if (node instanceof Element) {
            node.querySelectorAll('*').forEach(translateAttributes);
        }
    }

    function applyLanguage() {
        root.lang = language === 'tl' ? 'tl' : 'en';
        translateTree(document.head);
        translateTree(document.body);
        selector.value = language;
        label.textContent = language === 'tl' ? 'Wika' : 'Language';
        selector.setAttribute('aria-label', label.textContent);
    }

    const switcher = document.createElement('div');
    switcher.className = 'language-switcher';
    const label = document.createElement('label');
    label.className = 'language-switcher-label';
    const selector = document.createElement('select');
    selector.id = 'pupick-language';
    selector.setAttribute('aria-label', 'Language');
    selector.innerHTML = '<option value="en">English</option><option value="tl">Tagalog</option>';
    label.htmlFor = selector.id;
    switcher.append(label, selector);

    selector.addEventListener('change', () => {
        language = selector.value;
        try {
            localStorage.setItem(storageKey, language);
        } catch (error) {
            console.warn('PUPick could not save the language preference.', error);
        }
        applyLanguage();
    });

    function start() {
        const controls = document.createElement('div');
        controls.className = 'preference-controls';
        controls.appendChild(switcher);

        const themeButton = document.querySelector('.theme-toggle');
        if (themeButton) {
            controls.appendChild(themeButton);
        }

        const isAuthPage = document.body.classList.contains('auth-page');
        const host = isAuthPage
            ? null
            : document.querySelector('.topbar, .admin-top, .items-heading');
        if (host) {
            host.appendChild(controls);
            document.body.classList.add('preference-controls-inline');
        } else {
            document.body.appendChild(controls);
        }

        applyLanguage();

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'characterData') {
                    translateTextNode(mutation.target);
                } else {
                    mutation.addedNodes.forEach(translateTree);
                }
            });
        });
        observer.observe(document.body, { childList: true, characterData: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
