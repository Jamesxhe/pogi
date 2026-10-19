(() => {
    const chat = document.getElementById('pupickChat');
    const log = document.getElementById('chatLog');
    const form = document.getElementById('chatForm');
    const input = document.getElementById('chatQuestion');
    const clearButton = document.getElementById('clearChat');
    const chatStatus = document.getElementById('chatStatus');
    const imageInput = document.getElementById('chatImage');
    const imageStatus = document.getElementById('chatImageStatus');

    if (!chat || !log || !form || !input || !clearButton || !chatStatus || !imageInput || !imageStatus) {
        console.error('PUPick chat could not start because a required chat element is missing.');
        return;
    }

    const isAdmin = chat.dataset.admin === 'true';
    const aiConfigured = chat.dataset.aiConfigured === 'true';
    const welcomeMessage = 'Hi! Ask me about PUPick or any other topic. Pumili ng tanong sa ibaba o i-type ang kailangan mong malaman.';
    const offlineGeneralMessages = {
        en: 'The online AI is unavailable right now, so I can only answer PUPick help questions. Please try again later.',
        fil: 'Hindi available ngayon ang online AI, kaya mga tanong lang tungkol sa PUPick ang masasagot ko. Subukan ulit mamaya.',
        es: 'La IA en línea no está disponible ahora, así que solo puedo responder preguntas sobre PUPick. Inténtalo más tarde.',
        fr: 'L’IA en ligne n’est pas disponible pour le moment ; je peux seulement répondre aux questions sur PUPick. Réessayez plus tard.',
        de: 'Die Online-KI ist gerade nicht verfügbar. Ich kann deshalb nur Fragen zu PUPick beantworten. Bitte versuchen Sie es später erneut.',
        pt: 'A IA online não está disponível agora, então só posso responder a perguntas sobre o PUPick. Tente novamente mais tarde.',
        id: 'AI online sedang tidak tersedia, jadi saya hanya dapat menjawab pertanyaan tentang PUPick. Silakan coba lagi nanti.',
        zh: '在线 AI 暂时不可用，因此我目前只能回答有关 PUPick 的问题。请稍后再试。',
        ja: 'オンラインAIは現在利用できないため、PUPickに関する質問のみ回答できます。しばらくしてからもう一度お試しください。'
    };
    const chatHistory = [];
    const activeImageUrls = new Set();
    let imageSearchSequence = 0;
    let guidedSearchStep = '';
    let guidedSearchType = 'all';
    let guidedSearchLanguage = 'en';
    let chatRequestSequence = 0;
    const languages = {
        en: {
            markers: ['how', 'where', 'what', 'please', 'my item', 'my', 'i lost'],
            greeting: 'Hello! I can help with reports, item search, claims, notifications, and possible matches. What do you need?',
            report: 'To report an item, choose “Report lost item” or “Report found item”. Select a category (or type your own), add the date and location, and describe visible details. A photo is optional. Keep one private identifying detail out of the public description so the owner can use it to verify a claim. You can review your submission in “My reports”.',
            search: 'Open “Browse items”. Search by category, color, description, or location. Set the report type to Lost or Found, choose a status, or add a date range. Select “Reset filters” if the results are empty.',
            claim: 'In “Browse items”, open a found report marked available and select “Claim this item”. Explain specific details that only the owner would know; you may attach a supporting photo. The finder or an administrator reviews the claim. A photo alone does not prove ownership. Never send passwords, IDs, or sensitive account details.',
            status: 'Open “My reports” to see your reports and claim status. Check “Notifications” for claim decisions and other updates.',
            matches: 'Open “Possible matches” to compare similar lost and found reports. Compare category, description, color, date, and location. The similarity score is an estimate, not proof of ownership; confirm private details through a safe handoff.',
            item_lookup: 'I can help you check the reports. Search by the item’s category or a few identifying words; results are not proof that an item is yours.',
            privacy: 'Your selected search photo is compared in your browser and is not saved or sent to an external service. Report photos are visible according to the site’s report privacy. Never upload passwords, IDs, or sensitive personal details.',
            no_match: 'No results does not necessarily mean the item was not reported. Try a broader category or a different keyword, remove filters, or attach a photo to compare recent reports that include photos.',
            edit_report: 'Open “My reports” and find your submission. If the report cannot be edited there, contact the PUPick administrator and include the report details; do not post private ownership information publicly.',
            photo: 'You can attach a JPG, PNG, or WEBP photo below. PUPick compares it with recent report photos in your browser; this is approximate visual similarity, not object recognition or proof of ownership.',
            search_type_prompt: 'What kind of report are you looking for?',
            search_keywords_prompt: 'What item or details should I search for? For example: blue wallet, library.',
            search_ready: 'Here is the report search. Review the results; they do not confirm ownership.',
            notifications: 'Open “Notifications” to read account updates and claim decisions. Use “Mark all as read” to clear unread indicators.',
            account: 'Sign in with the email and password used to register. If you cannot access your account, contact the PUPick administrator; password reset is not available yet. Never send your password in chat.',
            help: 'For account-specific help or ownership verification, contact the PUPick administrator. Do not send passwords or sensitive information in chat.',
            review: 'On the Admin Dashboard, open “Manage Claims” and review the claimant’s message and optional photo against the found report. Approve only when the ownership details are convincing; this reserves the item. Reject claims that do not match. Mark an approved claim returned only after the item is physically handed over.',
            export: 'On the Admin Dashboard, choose “Download Excel workbook (.xlsx)” for a spreadsheet or “Download PDF” for a printable report. Handle exported claimant and report information as confidential.',
            unclear: 'I am not sure what you mean yet. I can help with reports, searching, claims, claim status, notifications, possible matches, account access, or admin tools. Try asking about one of those in a supported language.'
        },
        fil: {
            markers: ['paano', 'saan', 'ano', 'aking', 'nawala', 'nawawala', 'napulot', 'nahanap', 'tulong', 'mag-claim', 'magreport', 'maghanap', 'hanapin', 'status ko', 'may', 'ba', 'report ko'],
            greeting: 'Kumusta! Matutulungan kita sa pag-report, paghahanap ng item, pag-claim, notifications, at possible matches. Ano ang kailangan mo?',
            report: 'Para mag-report, piliin ang “Report lost item” o “Report found item”. Pumili o mag-type ng category, ilagay ang petsa at lokasyon, at ilarawan ang nakikitang detalye. Opsyonal ang larawan. Magtago ng isang pribadong detalyeng magagamit ng may-ari sa pag-verify ng claim. Makikita ang report sa “My reports”.',
            search: 'Buksan ang “Browse items”. Maghanap ayon sa category, kulay, paglalarawan, o lokasyon. Pumili ng Lost o Found, status, at saklaw ng petsa. Piliin ang “Reset filters” kung walang lumabas na resulta.',
            claim: 'Sa “Browse items”, buksan ang found report na available at piliin ang “Claim this item”. Ipaliwanag ang natatanging detalyeng alam lang ng may-ari; maaari ring maglakip ng suportang larawan. Susuriin ito ng nakakita o administrator. Hindi sapat na patunay ang larawan lang. Huwag magpadala ng password, ID, o sensitibong detalye ng account.',
            status: 'Buksan ang “My reports” para makita ang reports at status ng claim mo. Tingnan din ang “Notifications” para sa desisyon at mga update.',
            matches: 'Buksan ang “Possible matches” para ikumpara ang magkahawig na lost at found reports. Suriin ang category, paglalarawan, kulay, petsa, at lokasyon. Tantya lamang ang score at hindi ito patunay ng pagmamay-ari; i-verify nang ligtas ang pribadong detalye.',
            item_lookup: 'Matutulungan kitang tingnan ang mga report. Maghanap gamit ang kategorya o ilang salitang naglalarawan sa gamit; hindi patunay ng pagmamay-ari ang resulta.',
            privacy: 'Sa browser mo ikinukumpara ang napiling larawan at hindi ito sine-save o ipinapadala sa panlabas na serbisyo. Huwag mag-upload ng password, ID, o sensitibong personal na detalye.',
            no_match: 'Hindi laging ibig sabihin na walang report kapag walang resulta. Subukan ang mas malawak na kategorya o ibang keyword, alisin ang mga filter, o maglakip ng larawan para ikumpara sa mga kamakailang report na may larawan.',
            edit_report: 'Buksan ang “My reports” at hanapin ang ipinasa mong report. Kung hindi ito mae-edit doon, kontakin ang PUPick administrator at ibigay ang detalye ng report; huwag ilagay sa publiko ang pribadong patunay ng pagmamay-ari.',
            photo: 'Maaaring maglakip ng JPG, PNG, o WEBP sa ibaba. Ikinukumpara ito ng PUPick sa mga kamakailang larawan ng report gamit ang browser mo. Tantiya lamang ito at hindi patunay ng pagmamay-ari.',
            search_type_prompt: 'Anong uri ng report ang hinahanap mo?',
            search_keywords_prompt: 'Anong gamit o detalye ang hahanapin ko? Halimbawa: asul na pitaka, aklatan.',
            search_ready: 'Narito ang paghahanap ng report. Suriin ang mga resulta; hindi nito pinatutunayan kung kanino ang gamit.',
            notifications: 'Buksan ang “Notifications” para basahin ang updates at desisyon sa claim. Gamitin ang “Mark all as read” para alisin ang unread indicators.',
            account: 'Mag-sign in gamit ang email at password na ginamit sa registration. Kung hindi makapasok, kontakin ang PUPick administrator; wala pang password reset. Huwag ipadala ang password sa chat.',
            help: 'Para sa tulong tungkol sa account o pag-verify ng pagmamay-ari, kontakin ang PUPick administrator. Huwag ipadala rito ang password o sensitibong impormasyon.',
            review: 'Sa Admin Dashboard, buksan ang “Manage Claims” at suriin ang mensahe at opsyonal na larawan katabi ng detalye ng found report. Aprubahan lang kung kapani-paniwala ang detalye ng pagmamay-ari; mare-reserba ang gamit. Tanggihan kung hindi tugma. Markahang returned lamang kapag naiabot na talaga ang gamit.',
            export: 'Sa Admin Dashboard, piliin ang “Download Excel workbook (.xlsx)” para sa spreadsheet o “Download PDF” para sa printable report. Panatilihing kumpidensyal ang impormasyon ng mga claimant at report.',
            unclear: 'Hindi ko pa matukoy ang tanong. Kaya kong tumulong sa reports, paghahanap, pag-claim, status, notifications, possible matches, account, at admin tools. Subukan sa Filipino o English.'
        },
        es: {
            markers: ['como', 'donde', 'que', 'perdi', 'perdido', 'encontre', 'reclamar', 'reportar', 'estado', 'ayuda', 'mi objeto', 'busco', 'hola'],
            greeting: '¡Hola! Puedo ayudarte con reportes, búsqueda de objetos, reclamaciones, notificaciones y posibles coincidencias. ¿Qué necesitas?',
            report: 'Si perdiste algo, selecciona “Report lost item”; si encontraste algo, selecciona “Report found item”. Añade detalles claros y revisa el reporte en “My reports”.',
            search: 'Abre “Browse items”. Busca por nombre, categoría, color, descripción o ubicación y filtra por tipo o estado.',
            claim: 'En “Browse items”, elige un objeto encontrado disponible que haya publicado otra persona y selecciona “Claim this item”. Describe un detalle que verifique que te pertenece. Nunca compartas tu contraseña.',
            status: 'Abre “My reports” para consultar tus reportes y el estado de la reclamación. Revisa “Notifications” para ver decisiones y novedades.',
            matches: 'Abre “Possible matches” para comparar reportes similares. La puntuación es una estimación y no demuestra quién es el propietario.',
            notifications: 'Abre “Notifications” para leer novedades y decisiones. Usa “Mark all as read” para marcar todo como leído.',
            account: 'Inicia sesión con el correo y la contraseña de registro. Si no puedes entrar, contacta al administrador; todavía no hay recuperación de contraseña. No compartas tu contraseña.',
            help: 'Para ayuda con tu cuenta o verificar la propiedad, contacta al administrador de PUPick. No envíes contraseñas ni información sensible.',
            review: 'En el panel de administración, abre “Manage claims” y revisa primero las solicitudes pendientes. Comprueba los detalles y aprueba solo si hay motivos suficientes.',
            export: 'En el panel de administración, usa “Download Excel CSV” para una hoja de cálculo o “Download PDF” para un informe imprimible.',
            unclear: 'No entendí la pregunta. Puedo ayudar con reportes, búsquedas, reclamaciones, estados, notificaciones, coincidencias o la cuenta. Prueba en español, inglés o filipino.'
        },
        fr: {
            markers: ['comment', 'ou', 'quoi', 'perdu', 'trouve', 'reclamation', 'statut', 'aide', 'mon objet', 'bonjour'],
            greeting: 'Bonjour ! Je peux vous aider pour les signalements, la recherche, les réclamations, les notifications et les correspondances. Que vous faut-il ?',
            report: 'Si vous avez perdu un objet, choisissez « Report lost item » ; si vous en avez trouvé un, choisissez « Report found item ». Ajoutez des détails précis et consultez « My reports ».',
            search: 'Ouvrez « Browse items ». Recherchez par nom, catégorie, couleur, description ou lieu, puis filtrez par type ou statut.',
            claim: 'Dans « Browse items », choisissez un objet trouvé disponible publié par une autre personne, puis « Claim this item ». Décrivez un détail permettant de vérifier qu’il vous appartient. Ne partagez jamais votre mot de passe.',
            status: 'Ouvrez « My reports » pour consulter vos signalements et le statut de votre réclamation. Consultez « Notifications » pour les décisions.',
            matches: 'Ouvrez « Possible matches » pour comparer les signalements similaires. Le score est une estimation et ne prouve pas la propriété.',
            notifications: 'Ouvrez « Notifications » pour lire les actualités et décisions. Utilisez « Mark all as read » pour tout marquer comme lu.',
            account: 'Connectez-vous avec l’adresse e-mail et le mot de passe utilisés à l’inscription. Contactez l’administrateur en cas de problème ; la réinitialisation du mot de passe n’est pas encore disponible.',
            help: 'Pour une aide liée au compte ou vérifier la propriété, contactez l’administrateur PUPick. N’envoyez pas de mot de passe ni d’information sensible.',
            review: 'Dans le tableau de bord administrateur, ouvrez « Manage claims » et examinez les demandes en attente. Vérifiez les détails et ne validez qu’avec des éléments suffisants.',
            export: 'Dans le tableau de bord administrateur, utilisez « Download Excel CSV » pour un tableur ou « Download PDF » pour un rapport imprimable.',
            unclear: 'Je n’ai pas compris. Je peux aider pour les signalements, recherches, réclamations, statuts, notifications ou correspondances. Essayez en français, anglais ou filipino.'
        },
        de: {
            markers: ['wie', 'wo', 'was', 'verloren', 'gefunden', 'anspruch', 'status', 'hilfe', 'mein gegenstand', 'hallo'],
            greeting: 'Hallo! Ich helfe bei Meldungen, Suche, Ansprüchen, Benachrichtigungen und möglichen Treffern. Was möchten Sie wissen?',
            report: 'Wählen Sie „Report lost item“, wenn Sie etwas verloren haben, oder „Report found item“, wenn Sie etwas gefunden haben. Ergänzen Sie genaue Angaben und prüfen Sie den Eintrag unter „My reports“.',
            search: 'Öffnen Sie „Browse items“. Suchen Sie nach Name, Kategorie, Farbe, Beschreibung oder Ort und filtern Sie nach Typ oder Status.',
            claim: 'Wählen Sie in „Browse items“ einen verfügbaren Fund einer anderen Person und dann „Claim this item“. Beschreiben Sie ein Merkmal zum Eigentumsnachweis. Teilen Sie niemals Ihr Passwort.',
            status: 'Öffnen Sie „My reports“ für Ihre Meldungen und den Anspruchsstatus. Unter „Notifications“ finden Sie Entscheidungen und Neuigkeiten.',
            matches: 'Öffnen Sie „Possible matches“, um ähnliche Meldungen zu vergleichen. Der Wert ist nur eine Schätzung und kein Eigentumsnachweis.',
            notifications: 'Öffnen Sie „Notifications“ für Aktualisierungen und Entscheidungen. Mit „Mark all as read“ markieren Sie alles als gelesen.',
            account: 'Melden Sie sich mit Ihrer Registrierungs-E-Mail und Ihrem Passwort an. Bei Problemen kontaktieren Sie den Administrator; eine Passwortzurücksetzung gibt es noch nicht.',
            help: 'Bei Fragen zum Konto oder Eigentumsnachweis kontaktieren Sie den PUPick-Administrator. Senden Sie keine Passwörter oder vertraulichen Daten.',
            review: 'Öffnen Sie im Admin-Dashboard „Manage claims“ und prüfen Sie zuerst offene Anfragen. Genehmigen Sie nur bei ausreichenden Nachweisen.',
            export: 'Im Admin-Dashboard erstellt „Download Excel CSV“ eine Tabelle und „Download PDF“ einen druckbaren Bericht.',
            unclear: 'Ich habe die Frage nicht verstanden. Ich helfe bei Meldungen, Suche, Ansprüchen, Status, Benachrichtigungen und Treffern. Versuchen Sie es auf Deutsch, Englisch oder Filipino.'
        },
        pt: {
            markers: ['como', 'onde', 'perdi', 'encontro', 'encontrei', 'encontrar', 'carteira', 'reivindicar', 'estado', 'ajuda', 'meu item', 'notificacao', 'ola'],
            greeting: 'Olá! Posso ajudar com registros, busca de itens, reivindicações, notificações e possíveis correspondências. O que você precisa?',
            report: 'Se perdeu algo, escolha “Report lost item”; se encontrou algo, escolha “Report found item”. Adicione detalhes claros e consulte depois em “My reports”.',
            search: 'Abra “Browse items”. Pesquise por nome, categoria, cor, descrição ou local e filtre pelo tipo ou status.',
            claim: 'Em “Browse items”, escolha um item encontrado disponível publicado por outra pessoa e selecione “Claim this item”. Descreva um detalhe que comprove a propriedade. Nunca compartilhe sua senha.',
            status: 'Abra “My reports” para ver seus registros e o status da reivindicação. Consulte “Notifications” para decisões e atualizações.',
            matches: 'Abra “Possible matches” para comparar registros semelhantes. A pontuação é uma estimativa e não comprova a propriedade.',
            notifications: 'Abra “Notifications” para ler atualizações e decisões. Use “Mark all as read” para marcar tudo como lido.',
            account: 'Entre com o e-mail e a senha usados no cadastro. Se não conseguir acessar, contate o administrador; ainda não há redefinição de senha.',
            help: 'Para ajuda com a conta ou verificação de propriedade, contate o administrador do PUPick. Não envie senhas nem dados confidenciais.',
            review: 'No painel administrativo, abra “Manage claims” e analise primeiro as solicitações pendentes. Aprove apenas quando houver evidências suficientes.',
            export: 'No painel administrativo, use “Download Excel CSV” para uma planilha ou “Download PDF” para um relatório imprimível.',
            unclear: 'Não entendi. Posso ajudar com registros, buscas, reivindicações, status, notificações e correspondências. Tente em português, inglês ou filipino.'
        },
        id: {
            markers: ['bagaimana', 'dimana', 'di mana', 'kehilangan', 'menemukan', 'klaim', 'status', 'tolong', 'barang saya', 'halo'],
            greeting: 'Halo! Saya bisa membantu laporan, pencarian barang, klaim, notifikasi, dan kemungkinan kecocokan. Apa yang Anda perlukan?',
            report: 'Jika kehilangan barang, pilih “Report lost item”; jika menemukan barang, pilih “Report found item”. Isi detail yang jelas lalu kirim. Lihat laporan Anda di “My reports”.',
            search: 'Buka “Browse items”. Cari berdasarkan nama, kategori, warna, deskripsi, atau lokasi, lalu filter menurut jenis atau status.',
            claim: 'Di “Browse items”, pilih barang temuan yang tersedia dan dilaporkan orang lain, lalu pilih “Claim this item”. Jelaskan ciri untuk membuktikan kepemilikan. Jangan pernah membagikan kata sandi.',
            status: 'Buka “My reports” untuk melihat laporan dan status klaim. Periksa “Notifications” untuk keputusan dan pembaruan.',
            matches: 'Buka “Possible matches” untuk membandingkan laporan serupa. Skor hanya perkiraan dan bukan bukti kepemilikan.',
            notifications: 'Buka “Notifications” untuk membaca pembaruan dan keputusan. Gunakan “Mark all as read” untuk menandai semuanya telah dibaca.',
            account: 'Masuk dengan email dan kata sandi saat mendaftar. Jika tidak bisa masuk, hubungi administrator; fitur reset kata sandi belum tersedia.',
            help: 'Untuk bantuan akun atau verifikasi kepemilikan, hubungi administrator PUPick. Jangan kirim kata sandi atau informasi sensitif.',
            review: 'Di Admin Dashboard, buka “Manage claims” dan periksa permintaan tertunda terlebih dahulu. Setujui hanya jika bukti cukup.',
            export: 'Di Admin Dashboard, pilih “Download Excel CSV” untuk spreadsheet atau “Download PDF” untuk laporan siap cetak.',
            unclear: 'Saya belum memahami pertanyaannya. Saya bisa membantu laporan, pencarian, klaim, status, notifikasi, atau kecocokan. Coba bahasa Indonesia, Inggris, atau Filipino.'
        },
        zh: {
            markers: ['怎么', '如何', '哪里', '什么', '丢失', '找到', '认领', '状态', '帮助', '我的物品', '通知', '你好'],
            greeting: '你好！我可以帮助你提交物品报告、搜索物品、认领、查看通知和可能的匹配。你需要什么帮助？',
            report: '丢失物品请选择“Report lost item”；捡到物品请选择“Report found item”。填写清晰信息并提交，可在“My reports”查看。',
            search: '打开“Browse items”，可按名称、类别、颜色、描述或地点搜索，也可按类型或状态筛选。',
            claim: '在“Browse items”中选择他人发布且可认领的失物，然后点击“Claim this item”。描述可验证所有权的特征。请勿分享密码。',
            status: '打开“My reports”查看报告和认领状态；在“Notifications”查看决定和更新。',
            matches: '打开“Possible matches”比较相似报告。匹配分数只是估算，不能证明物品归属。',
            notifications: '打开“Notifications”查看更新和认领决定。使用“Mark all as read”将通知标记为已读。',
            account: '请使用注册时的邮箱和密码登录。如无法登录，请联系管理员；目前尚无密码重置功能。不要在聊天中发送密码。',
            help: '账户或所有权验证问题请联系 PUPick 管理员。请勿发送密码或敏感信息。',
            review: '在管理员面板打开“Manage claims”，优先审核待处理申请。只有证据充分时才批准。',
            export: '在管理员面板选择“Download Excel CSV”下载表格，或选择“Download PDF”下载可打印报告。',
            unclear: '我还不确定你的问题。可以询问报告、搜索、认领、状态、通知或匹配。也可以用中文、英语或菲律宾语提问。'
        },
        ja: {
            markers: ['どう', 'どこ', '何', 'なくした', '見つけた', '請求', '状態', 'ヘルプ', '通知', '私の物', 'こんにちは'],
            greeting: 'こんにちは。紛失・発見の報告、検索、申請、通知、候補の照合について案内できます。何を知りたいですか？',
            report: '紛失した場合は「Report lost item」、拾った場合は「Report found item」を選び、詳細を入力して送信してください。「My reports」で確認できます。',
            search: '「Browse items」を開き、名前、カテゴリ、色、説明、場所で検索できます。種類や状態で絞り込むこともできます。',
            claim: '「Browse items」で他の人が登録した利用可能な拾得物を選び、「Claim this item」を選択してください。本人確認に役立つ特徴を説明してください。パスワードは共有しないでください。',
            status: '「My reports」で報告と申請状況を確認できます。決定や更新は「Notifications」を確認してください。',
            matches: '「Possible matches」で似た報告を確認できます。スコアは推定値であり、所有者を証明するものではありません。',
            notifications: '「Notifications」で更新や申請結果を確認できます。「Mark all as read」で既読にできます。',
            account: '登録したメールアドレスとパスワードでログインしてください。問題がある場合は管理者に連絡してください。現在パスワード再設定機能はありません。',
            help: 'アカウントや所有者確認の相談はPUPick管理者へ。パスワードや機密情報を送らないでください。',
            review: '管理者ダッシュボードの「Manage claims」を開き、保留中の申請を確認してください。十分な根拠がある場合のみ承認してください。',
            export: '管理者ダッシュボードで「Download Excel CSV」を選ぶと表計算用、「Download PDF」を選ぶと印刷用のレポートを取得できます。',
            unclear: '質問を特定できませんでした。報告、検索、申請状況、通知、候補照合について質問できます。日本語、英語、フィリピン語でお試しください。'
        }
    };
    const phrases = {
        greeting: ['hello', 'hi', 'hey', 'kumusta', 'magandang umaga', 'magandang hapon', 'magandang gabi', 'hola', 'bonjour', 'hallo', 'olá', 'ola', 'halo', '你好', 'こんにちは'],
        item_lookup: ['was my item reported', 'has my item been reported', 'is there a report for', 'is my item in the system', 'has anyone found', 'did someone report', 'did anyone find', 'have you seen', 'any report for', 'my item reported', 'can you find my', 'check if my', 'may report ba', 'nai-report ba', 'na-report ba', 'may nag-report ba', 'may nakakita ba', 'nahanap ba ang', 'na-report na ba', 'may ganito bang item', 'may ganitong gamit', 'may nag-ulat ba', 'may nakapag-ulat ba', 'may nakita bang', 'busco mi', 'alguien encontro', 'a-t-il ete signale', 'wurde mein', 'alguem encontrou', 'apakah barang saya', '有人报告', '有没有人捡到', '届出されていますか'],
        report: ['report lost', 'report found', 'report item', 'lost item', 'found item', 'submit report', 'i lost', 'lost my', 'lost something', 'i found', 'found a', 'found my', 'nawala sa akin', 'may nawala', 'nawala', 'nawalang', 'nawawala', 'napulot', 'may nakita', 'mag-report', 'i-report', 'magreport', 'perdi', 'perdido', 'encontre', 'encontrado', 'reportar', 'perdu', 'objet perdu', 'signaler', 'trouve', 'verloren', 'gefunden', 'melden', 'perdeu', 'encontrou', 'perdido', 'encontrado', 'perder', 'kehilangan', 'menemukan', 'lapor barang', '丢失', '捡到', 'なくした', '拾った'],
        search: ['search', 'browse', 'filter', 'find item', 'find a reported item', 'look for', 'how do i search', 'search by date', 'date range', 'hanap', 'hanapin', 'maghanap', 'saan makikita', 'i-search', 'buscar', 'buscar un', 'busco', 'busco un', 'busqueda', 'rechercher', 'rechercher un', 'comment rechercher', 'recherche', 'suchen', 'suche', 'wie suche', 'pesquisar', 'procurar', 'encontrar', 'como encontrar', 'cari', 'cari barang', 'mencari', 'bagaimana mencari', '怎么搜索', '搜索', '搜寻', '検索', '探す'],
        claim: ['claim item', 'claim a found item', 'how do i claim', 'how to claim', 'claim found', 'claim this', 'claim', 'ownership', 'kunin', 'mag-claim', 'i-claim', 'pag-aari', 'akin ba', 'reclamar', 'reclamacion', 'reivindicar', 'reclamer', 'beanspruchen', 'anspruch', 'mengklaim', 'mengambil', '认领', '申领', '持ち主', '申請'],
        status: ['claim status', 'my reports', 'check claim', 'track', 'pending', 'kamusta claim', 'nasaan claim', 'status ko', 'subaybayan', 'estado', 'statut', 'anspruchsstatus', 'status klaim', '申请状态', '申請状況'],
        matches: ['possible match', 'matches', 'similar', 'matching', 'magkatugma', 'magkahawig', 'coincidencia', 'correspondance', 'treffer', 'correspondencia', 'kecocokan', '匹配', '照合'],
        notifications: ['notification', 'notifications', 'alert', 'abiso', 'mark all as read', 'notificacion', 'benachrichtigung', 'notifikasi', '通知'],
        account: ['password', 'login', 'log in', 'sign in', 'account', 'forgot password', 'nakalimutan password', 'di makapasok', 'contrasena', 'mot de passe', 'passwort', 'senha', 'kata sandi', '密码', 'パスワード', 'ログイン'],
        help: ['contact', 'help', 'support', 'tulong', 'kontakin', 'ayuda', 'aide', 'hilfe', 'ajuda', 'bantuan', '帮助', 'ヘルプ'],
        privacy: ['private', 'privacy', 'safe to upload', 'personal information', 'personal data', 'is my photo private', 'who can see my photo', 'password', 'sensitive', 'confidential', 'pribado', 'privacy', 'ligtas ba', 'sensitibo', 'personal na impormasyon', 'password ko', 'sino makakakita ng larawan', 'seguro', 'privado', 'sensible', 'privat', 'persönliche daten', 'privacidade', 'dados pessoais', 'rahasia', 'pribadi', '隐私', '密码安全', '個人情報'],
        no_match: ['no results', 'nothing found', 'no match', 'not showing', 'not listed', 'cannot find', 'cant find', 'wala akong makita', 'walang resulta', 'walang lumabas', 'walang nahanap', 'hindi makita', 'no aparece', 'no encuentro', 'aucun resultat', 'nicht gefunden', 'nao encontrei', 'tidak ditemukan', '没有结果', '見つからない'],
        edit_report: ['edit report', 'change my report', 'update my report', 'delete report', 'remove my report', 'wrong details', 'maling detalye', 'baguhin ang report', 'i-edit ang report', 'burahin ang report', 'mali ang nailagay', 'modifier mon rapport', 'bericht andern', 'editar meu registro', 'ubah laporan', '修改报告', '報告を編集'],
        photo: ['photo match', 'upload photo', 'attach photo', 'send a picture', 'picture search', 'photo search', 'larawan', 'picture', 'photo', 'image', 'mag-upload ng larawan', 'maglakip ng larawan', 'send pic', 'foto', 'imagen', '写真', '照片'],
        review: ['review claim', 'approve claim', 'reject claim', 'manage claims', 'mark returned', 'i-review', 'aprubahan claim', 'tanggihan claim', 'revisar reclamacion', 'examiner reclamation', 'anspruch prufen', 'tinjau klaim', '审核认领', '申請を確認'],
        export: ['export', 'excel', 'csv', 'pdf', 'download report', 'i-export', 'mag-export', 'descargar', 'telecharger', 'herunterladen', 'baixar', 'unduh', '导出', 'ダウンロード']
    };
    const normalize = (value) => value.toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    const detectLanguage = (text) => {
        let best = 'en';
        let bestScore = 0;
        const tokens = new Set(text.split(/[^a-z]+/).filter(Boolean));
        for (const [code, data] of Object.entries(languages)) {
            const score = data.markers.reduce((sum, marker) => {
                const hasNonLatin = /[^\u0000-\u007f]/.test(marker);
                const found = hasNonLatin || marker.includes(' ')
                    ? text.includes(marker)
                    : tokens.has(marker);
                return sum + (found ? 1 : 0);
            }, 0);
            if (score > bestScore) {
                best = code;
                bestScore = score;
            }
        }
        if (bestScore === 0 && /[ñ¿¡]/.test(text)) return 'es';
        return best;
    };
    const classify = (text) => {
        if (/\b(was|is|has|have|did|does)\b.{0,50}\b(reported|listed|found|seen|in the system)\b/u.test(text)) {
            return { intent: 'item_lookup', score: 1 };
        }
        let bestIntent = 'unclear';
        let bestScore = 0;
        for (const [intent, options] of Object.entries(phrases)) {
            if (!isAdmin && (intent === 'review' || intent === 'export')) continue;
            const score = options.reduce((best, phrase) => {
                const normalizedPhrase = normalize(phrase);
                const hasNonLatin = /[^\u0000-\u007f]/.test(normalizedPhrase);
                const matched = hasNonLatin
                    ? text.includes(normalizedPhrase)
                    : normalizedPhrase.includes(' ')
                        ? text.includes(normalizedPhrase)
                        : new RegExp(`\\b${normalizedPhrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'u').test(text);
                if (!matched) return best;
                const directSearchRequest = intent === 'search'
                    && ['search', 'browse', 'filter', 'look for', 'find item', 'find a reported item', 'hanap', 'hanapin', 'maghanap', 'saan makikita'].includes(normalizedPhrase);
                const directClaimRequest = intent === 'claim'
                    && ['claim item', 'claim a found item', 'how do i claim', 'how to claim'].includes(normalizedPhrase);
                return Math.max(best, normalizedPhrase.length + (normalizedPhrase.includes(' ') ? 8 : 0) + (directSearchRequest ? 12 : 0) + (directClaimRequest ? 16 : 0));
            }, 0);
            if (score > bestScore) {
                bestIntent = intent;
                bestScore = score;
            }
        }
        return { intent: bestIntent, score: bestScore };
    };
    const addMessage = (text, sender) => {
        const message = document.createElement('div');
        message.className = `chat-message ${sender}`;
        message.textContent = text;
        log.appendChild(message);
        log.scrollTop = log.scrollHeight;
        return message;
    };
    const requestAiAnswer = async (question) => {
        const response = await fetch('chatbot_ai.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ message: question, history: chatHistory.slice(-8) })
        });
        let data = {};
        try { data = await response.json(); } catch (error) { /* fall back to the local FAQ */ }
        if (!response.ok) throw new Error(data.reason || 'offline');
        if (data.available !== true || typeof data.answer !== 'string' || !data.answer.trim()) {
            throw new Error(data.reason || 'offline');
        }
        return data.answer.trim();
    };
    const rememberExchange = (question, response) => {
        chatHistory.push(
            { role: 'user', content: question },
            { role: 'assistant', content: response }
        );
        if (chatHistory.length > 8) chatHistory.splice(0, chatHistory.length - 8);
    };
    const answer = (question) => {
        const text = normalize(question);
        const language = languages[detectLanguage(text)];
        const result = classify(text);
        const fallbackIntents = {
            item_lookup: 'search',
            privacy: 'help',
            no_match: 'search',
            edit_report: 'status',
            photo: 'matches'
        };
        const intent = result.score ? result.intent : 'unclear';
        const languageCode = detectLanguage(text);
        return {
            intent,
            language: languageCode,
            text: language[intent] || languages[languageCode][fallbackIntents[intent]] || language.unclear
        };
    };

    const searchQueryFromQuestion = (question) => {
        const ignoredWords = new Set([
            'a', 'about', 'any', 'are', 'been', 'can', 'check', 'could', 'do', 'does', 'find',
            'for', 'found', 'has', 'have', 'help', 'how', 'i', 'in', 'is', 'item', 'items', 'lost',
            'looking', 'look', 'me', 'my', 'of', 'on', 'or', 'please', 'reported', 'report',
            'if', 'nothing', 'no', 'results', 'came', 'up', 'search', 'see', 'show', 'someone', 'the', 'there', 'to', 'was', 'were', 'what',
            'where', 'with', 'you', 'your', 'ba', 'bang', 'baang', 'ako', 'akin', 'ang', 'at',
            'ay', 'ba', 'gamit', 'hanap', 'hanapin', 'ito', 'ko', 'maghanap', 'may', 'mo', 'na', 'nai', 'nahanap',
            'nawala', 'nawawala', 'nawalang', 'nawawalang', 'napulot', 'nakita', 'ng', 'ni', 'pa', 'po', 'paano', 'hanapin', 'tungkol', 'report', 'saan', 'sa', 'sino', 'yung', 'yon'
        ]);
        const words = question.toLocaleLowerCase().normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .match(/[\p{L}\p{N}]+/gu) || [];
        return words
            .filter((word) => word.length > 1 && !ignoredWords.has(word))
            .slice(0, 6)
            .join(' ');
    };

    const actionsForReply = (reply, question) => {
        const { intent } = reply;
        const query = searchQueryFromQuestion(question);
        const browseUrl = (type = 'all', search = '') => {
            const params = new URLSearchParams({ kind: type, status: 'all' });
            if (search) params.set('q', search);
            return `items.php?${params.toString()}`;
        };

        switch (intent) {
            case 'item_lookup':
                return [
                    { label: query ? 'Search reports' : 'Browse all reports', href: browseUrl('all', query) },
                    { label: 'Guided item search', action: 'start-search' }
                ];
            case 'search':
            case 'no_match': {
                const text = normalize(question);
                const type = /\b(lost|nawala|nawalang|nawawala|nawawalang)\b/u.test(text)
                    ? 'lost'
                    : /\b(found|napulot|nakita)\b/u.test(text)
                        ? 'found'
                        : 'all';
                return [
                    { label: query ? 'Search reports' : 'Browse all reports', href: browseUrl(type, query) },
                    { label: 'Guided item search', action: 'start-search' }
                ];
            }
            case 'report': {
                const text = normalize(question);
                const lost = /\b(lost|missing|nawala|nawalang|perdi|perdu|verloren|kehilangan)\b/u.test(text)
                    || text.includes('丢失') || text.includes('なくした');
                const found = /\b(found|find|napulot|nakita|encontre|trouve|gefunden|menemukan)\b/u.test(text)
                    || text.includes('捡到') || text.includes('拾った');
                if (lost && !found) return [{ label: 'Report a lost item', href: 'report_lost.php' }];
                if (found && !lost) return [{ label: 'Report a found item', href: 'report_found.php' }];
                return [
                    { label: 'Report lost item', href: 'report_lost.php' },
                    { label: 'Report found item', href: 'report_found.php' }
                ];
            }
            case 'claim':
                return [{ label: 'Browse found items', href: browseUrl('found') }];
            case 'status':
                return [
                    { label: 'View my reports and claims', href: 'my_reports.php' },
                    { label: 'View notifications', href: 'notifications.php' }
                ];
            case 'edit_report':
                return [{ label: 'Open my reports', href: 'my_reports.php' }];
            case 'matches':
                return [{ label: 'Compare possible matches', href: 'ai_matches.php' }];
            case 'notifications':
                return [{ label: 'Open notifications', href: 'notifications.php' }];
            case 'account':
                return [{ label: 'Go to sign in', href: 'login.php' }];
            case 'review':
                return isAdmin ? [{ label: 'Review claims', href: 'admin.php#claims' }] : [];
            case 'export':
                return isAdmin ? [{ label: 'Download PDF report', href: 'admin_export.php?format=pdf' }] : [];
            case 'photo':
                return [{ label: 'Attach a photo', action: 'attach-photo' }];
            default:
                return [];
        }
    };

    const addReply = (reply, question) => {
        const message = document.createElement('div');
        message.className = 'chat-message bot';
        const text = document.createElement('p');
        text.textContent = reply.text;
        message.appendChild(text);

        const actions = actionsForReply(reply, question);
        if (actions.length) {
            const actionList = document.createElement('div');
            actionList.className = 'chat-reply-actions';
            actions.forEach((action) => {
                if (action.action === 'start-search') {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = action.label;
                    button.addEventListener('click', () => startGuidedSearch(reply.language));
                    actionList.appendChild(button);
                    return;
                }
                if (action.action === 'attach-photo') {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = action.label;
                    button.addEventListener('click', () => imageInput.click());
                    actionList.appendChild(button);
                    return;
                }
                const link = document.createElement('a');
                link.href = action.href;
                link.textContent = action.label;
                actionList.appendChild(link);
            });
            message.appendChild(actionList);
        }

        log.appendChild(message);
        log.scrollTop = log.scrollHeight;
        return message;
    };

    const addChoicePrompt = (prompt, choices) => {
        const message = document.createElement('div');
        message.className = 'chat-message bot';
        const text = document.createElement('p');
        text.textContent = prompt;
        message.appendChild(text);
        const actionList = document.createElement('div');
        actionList.className = 'chat-reply-actions';
        choices.forEach(({ label, value }) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            button.addEventListener('click', () => {
                addMessage(label, 'user');
                guidedSearchType = value;
                guidedSearchStep = 'keywords';
                addMessage(languages[guidedSearchLanguage].search_keywords_prompt || languages.en.search_keywords_prompt, 'bot');
                input.focus();
            });
            actionList.appendChild(button);
        });
        message.appendChild(actionList);
        log.appendChild(message);
        log.scrollTop = log.scrollHeight;
    };

    function startGuidedSearch(languageCode = 'en') {
        guidedSearchLanguage = languages[languageCode] ? languageCode : 'en';
        guidedSearchStep = 'type';
        addChoicePrompt(
            languages[guidedSearchLanguage].search_type_prompt || languages.en.search_type_prompt,
            [
                { label: 'Lost', value: 'lost' },
                { label: 'Found', value: 'found' },
                { label: 'Either', value: 'all' }
            ]
        );
    }

    const imageFingerprint = async (source) => {
        const bitmap = await createImageBitmap(source);
        try {
            if (
                bitmap.width < 1
                || bitmap.height < 1
                || bitmap.width > 8000
                || bitmap.height > 8000
                || bitmap.width * bitmap.height > 40000000
            ) {
                throw new Error('Choose a photo with dimensions no larger than 8,000 × 8,000 pixels.');
            }

            const canvas = document.createElement('canvas');
            canvas.width = 32;
            canvas.height = 32;
            const context = canvas.getContext('2d', { willReadFrequently: true });
            if (!context) {
                throw new Error('Your browser could not prepare the photo for comparison.');
            }
            context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            const { data } = context.getImageData(0, 0, canvas.width, canvas.height);

            const grayscale = [];
            const histogram = new Array(12).fill(0);
            for (let pixel = 0; pixel < canvas.width * canvas.height; pixel += 1) {
                const offset = pixel * 4;
                const red = data[offset];
                const green = data[offset + 1];
                const blue = data[offset + 2];
                grayscale.push((red * 299 + green * 587 + blue * 114) / 1000);
                histogram[Math.floor(red / 64)] += 1;
                histogram[4 + Math.floor(green / 64)] += 1;
                histogram[8 + Math.floor(blue / 64)] += 1;
            }

            const differenceHash = [];
            for (let row = 0; row < 8; row += 1) {
                for (let column = 0; column < 8; column += 1) {
                    const left = grayscale[row * 32 + column * 4];
                    const right = grayscale[row * 32 + column * 4 + 1];
                    differenceHash.push(left > right);
                }
            }

            const channelSize = canvas.width * canvas.height;
            for (let channel = 0; channel < 3; channel += 1) {
                for (let bin = 0; bin < 4; bin += 1) {
                    const index = channel * 4 + bin;
                    histogram[index] /= channelSize;
                }
            }
            return { differenceHash, histogram };
        } finally {
            bitmap.close();
        }
    };

    const compareFingerprints = (first, second) => {
        let differentBits = 0;
        for (let index = 0; index < first.differenceHash.length; index += 1) {
            if (first.differenceHash[index] !== second.differenceHash[index]) {
                differentBits += 1;
            }
        }

        let histogramIntersection = 0;
        for (let index = 0; index < first.histogram.length; index += 1) {
            histogramIntersection += Math.min(first.histogram[index], second.histogram[index]);
        }
        histogramIntersection /= 3;

        const hashSimilarity = 1 - differentBits / first.differenceHash.length;
        return Math.round((hashSimilarity * 0.65 + histogramIntersection * 0.35) * 100);
    };

    const loadReportFingerprint = async (report) => {
        const image = new Image();
        image.decoding = 'async';
        image.src = report.image;
        await new Promise((resolve, reject) => {
            const timeout = window.setTimeout(() => reject(new Error('Report photo load timed out.')), 10000);
            image.decode().then(
                () => {
                    window.clearTimeout(timeout);
                    resolve();
                },
                (error) => {
                    window.clearTimeout(timeout);
                    reject(error);
                }
            );
        });
        return imageFingerprint(image);
    };

    const addImageMessage = (file, objectUrl) => {
        const message = document.createElement('div');
        message.className = 'chat-message user';
        message.textContent = 'Photo attached for report search.';
        const preview = document.createElement('img');
        preview.className = 'chat-image-preview';
        preview.src = objectUrl;
        preview.alt = 'Selected photo preview';
        preview.addEventListener('load', () => {
            URL.revokeObjectURL(objectUrl);
            activeImageUrls.delete(objectUrl);
        }, { once: true });
        message.appendChild(preview);
        log.appendChild(message);
        log.scrollTop = log.scrollHeight;
    };

    const addImageResults = (matches, reportsWithPhotos, comparedCount) => {
        const message = document.createElement('div');
        message.className = 'chat-message bot';
        const lead = document.createElement('p');
        lead.textContent = matches.length
            ? 'Possible visual matches:'
            : 'No close visual match was found. The item may still have been reported.';
        message.appendChild(lead);

        if (matches.length) {
            const results = document.createElement('div');
            results.className = 'chat-image-results';
            matches.forEach(({ report, score }) => {
                const link = document.createElement('a');
                link.className = 'chat-image-result';
                const query = new URLSearchParams({
                    kind: report.type,
                    status: 'all',
                    q: report.category
                });
                link.href = `items.php?${query.toString()}`;

                const image = document.createElement('img');
                image.src = report.image;
                image.alt = `${report.category} report photo`;
                image.loading = 'lazy';

                const details = document.createElement('span');
                const category = document.createElement('strong');
                category.textContent = `${report.type === 'lost' ? 'Lost' : 'Found'} · ${report.category}`;
                const info = document.createElement('small');
                info.textContent = `${report.date || 'Date not listed'} · ${report.status} · Visual similarity ${score}%`;
                details.append(category, info);
                link.append(image, details);
                results.appendChild(link);
            });
            message.appendChild(results);
        }

        const disclaimer = document.createElement('p');
        disclaimer.className = 'chat-image-disclaimer';
        disclaimer.textContent = reportsWithPhotos && comparedCount > 0
            ? 'Visual similarity is only a clue, not object recognition or proof. Photos and viewing angles can differ; check the report details before claiming an item.'
            : reportsWithPhotos
                ? 'Report photos could not be compared. Try Browse items to search reports by category or description.'
                : 'There are currently no lost or found reports with photos to compare. Try Browse items to check reports without photos.';
        message.appendChild(disclaimer);
        log.appendChild(message);
        log.scrollTop = log.scrollHeight;
    };

    const checkImageAgainstReports = async (file, sequence) => {
        const objectUrl = URL.createObjectURL(file);
        activeImageUrls.add(objectUrl);
        addImageMessage(file, objectUrl);
        imageStatus.textContent = 'Checking report photos…';

        try {
            const selectedFingerprint = await imageFingerprint(file);
            const response = await fetch('chatbot_item_images.php', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.error || 'Could not load report photos. Please try again later.');
            }
            if (!Array.isArray(data.reports)) {
                throw new Error(data.error || 'Could not load report photos. Please try again later.');
            }

            const matches = [];
            let comparedCount = 0;
            let nextIndex = 0;
            const worker = async () => {
                while (nextIndex < data.reports.length) {
                    const report = data.reports[nextIndex];
                    nextIndex += 1;
                    try {
                        const fingerprint = await loadReportFingerprint(report);
                        const score = compareFingerprints(selectedFingerprint, fingerprint);
                        matches.push({ report, score });
                        comparedCount += 1;
                    } catch (error) {
                        console.warn('PUPick could not compare a report photo.', error);
                    }
                }
            };
            await Promise.all(Array.from({ length: Math.min(5, data.reports.length) }, worker));
            if (sequence !== imageSearchSequence) return;

            if (data.reports.length > 0 && comparedCount === 0) {
                throw new Error('Report photos could not be compared. Try Browse items to search reports by category or description.');
            }

            matches.sort((first, second) => second.score - first.score);
            const possibleMatches = matches.filter((match) => match.score >= 58).slice(0, 5);
            addImageResults(possibleMatches, data.reports.length, comparedCount);
            imageStatus.textContent = 'Photo comparison complete.';
        } catch (error) {
            if (sequence !== imageSearchSequence) return;
            console.error('PUPick chatbot photo search failed.', error);
            const message = error instanceof Error ? error.message : 'Could not compare this photo.';
            addMessage(message, 'bot');
            imageStatus.textContent = message;
        }
    };

    imageInput.addEventListener('change', () => {
        const file = imageInput.files && imageInput.files[0];
        imageInput.value = '';
        if (!file) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            imageStatus.textContent = 'Use a JPG, PNG, or WEBP photo.';
            addMessage('Use a JPG, PNG, or WEBP photo.', 'bot');
            return;
        }
        if (file.size < 1 || file.size > 5 * 1024 * 1024) {
            imageStatus.textContent = 'Choose a photo smaller than 5 MB.';
            addMessage('Choose a photo smaller than 5 MB.', 'bot');
            return;
        }

        imageSearchSequence += 1;
        checkImageAgainstReports(file, imageSearchSequence);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const question = input.value.trim();
        if (!question) return;
        addMessage(question, 'user');
        if (guidedSearchStep === 'keywords') {
            const query = searchQueryFromQuestion(question);
            if (!query) {
                addMessage(
                    languages[guidedSearchLanguage].search_keywords_prompt || languages.en.search_keywords_prompt,
                    'bot'
                );
                input.value = '';
                input.focus();
                return;
            }
            const params = new URLSearchParams({ kind: guidedSearchType, status: 'all', q: query });
            const reply = document.createElement('div');
            reply.className = 'chat-message bot';
            const text = document.createElement('p');
            text.textContent = languages[guidedSearchLanguage].search_ready || languages.en.search_ready;
            const actionList = document.createElement('div');
            actionList.className = 'chat-reply-actions';
            const link = document.createElement('a');
            link.href = `items.php?${params.toString()}`;
            link.textContent = 'Search reports';
            actionList.appendChild(link);
            reply.append(text, actionList);
            log.appendChild(reply);
            log.scrollTop = log.scrollHeight;
            guidedSearchStep = '';
            input.value = '';
            input.focus();
            return;
        }
        input.value = '';
        input.focus();
        const requestId = ++chatRequestSequence;
        const fallbackReply = answer(question);
        if (!aiConfigured) {
            chatStatus.textContent = 'FAQ assistant · Offline';
            chatStatus.classList.add('offline');
            addReply(fallbackReply, question);
            rememberExchange(question, fallbackReply.text);
            return;
        }

        const pendingMessage = addMessage('Thinking…', 'bot');
        const sendButton = form.querySelector('button[type="submit"]');
        if (sendButton) sendButton.disabled = true;
        try {
            const aiText = await requestAiAnswer(question);
            if (requestId !== chatRequestSequence) return;
            chatStatus.textContent = 'AI assistant · Online';
            chatStatus.classList.remove('offline');
            addReply({ ...fallbackReply, text: aiText }, question);
            rememberExchange(question, aiText);
        } catch (error) {
            if (requestId !== chatRequestSequence) return;
            console.warn('PUPick AI is unavailable; using the offline FAQ reply.');
            const offlineStatus = {
                quota: 'AI quota needed · FAQ offline',
                busy: 'AI busy · FAQ offline',
                key: 'Check API key · FAQ offline',
                offline: 'AI offline · FAQ active'
            };
            chatStatus.textContent = offlineStatus[error.message] || offlineStatus.offline;
            chatStatus.classList.add('offline');
            const offlineReply = fallbackReply.intent === 'unclear'
                ? { ...fallbackReply, text: offlineGeneralMessages[fallbackReply.language] || offlineGeneralMessages.en }
                : fallbackReply;
            addReply(offlineReply, question);
            rememberExchange(question, offlineReply.text);
        } finally {
            pendingMessage.remove();
            if (sendButton) sendButton.disabled = false;
        }
    });
    clearButton.addEventListener('click', () => {
        chatRequestSequence += 1;
        imageSearchSequence += 1;
        guidedSearchStep = '';
        chatHistory.length = 0;
        activeImageUrls.forEach((url) => URL.revokeObjectURL(url));
        activeImageUrls.clear();
        log.replaceChildren();
        addMessage(welcomeMessage, 'bot');
        imageStatus.textContent = 'Checks up to the latest 50 lost and 50 found reports that include photos.';
        input.focus();
    });
    document.querySelectorAll('.chat-suggestions button').forEach((button) => {
        button.addEventListener('click', () => {
            input.value = button.textContent;
            form.requestSubmit();
        });
    });
})();
