<div class="container-fluid mx-auto mt-5 pt-2">
    <div class="accordion" id="accordionExample">
        <h2 class="mt-2 mb-2 text-info">購物及售貨安排</h2>
        <?php
        $accordionItems = [
            [
                'id' => 'One',
                'title' => '1. 包裹看起來好像已經損壞了',
                'content' => '請注意，如果您認為包裹有問題： 請儘快與我們聯繫，提供一張清晰的照片，顯示損壞的部位以及運輸標籤。我們的店鋪將聯繫快遞公司進行理賠。在問題得到解決之前，請勿丟棄運輸標籤。',
                'show' => true
            ],
            [
                'id' => 'Two',
                'title' => '2. 訂單中的商品/部件出現損壞',
                'content' => '
                    <div class="accordion-body">
                        請在收貨後的7天內與我們聯繫，並提供以下資訊：
                    </div>
                    <ul>
                        <li>詳細說明問題</li><br>
                        <li>損壞部件的清晰近照</li><br>
                        <li>損壞部件和運輸標籤的清晰照片</li><br>
                        <li>保留商品盒，以防需要換貨</li><br>
                    </ul>
                    <div class="accordion-body">
                        您需要保留運輸盒和運輸文件作為證明，以便我們確認您是從我們的商店購買的商品，才能提供協助。同時，您需要保留商品盒，包括所有內容（選件/說明書/塑料膠泡殼和其他緩衝材料），直到我們確認能提供哪些協助。我們會盡快回覆您的要求並提供解決方案。請理解，我們可能需要幾天時間與管理層協商才能回覆您的要求。<br>
                        <h3>**請注意：</h3>
                    </div>
                    <ul>
                        <li>如果收到的照片不包括運輸標籤，我們將無法為您提供解決方案。</li><br>
                        <li>由於我們同時要保證退款不被濫用，所有有損壞之產品只限換貨。</li><br>
                        <li>我們無法為產品外包裝的損壞負責。</li><br>
                    </ul>',
                'show' => false
            ],
            [
                'id' => 'Three',
                'title' => '3. 我在你們網站上看到的圖片和我收到的物品不一樣！',
                'content' => '因為我們經常在正式產品發布前列出商品，所以我們網站上展示的大部分照片都是由製造商提供的原型或樣品。原型和實際投入市場的產品之間可能存在一些不一致之處。此外，有些產品在工廠需要手工上色，這也可能導致個體之間的差異。這些不被視為製造商的瑕疵。<br>
                    偶爾，您可能會注意到您的物品上的油漆或零件存在一些小瑕疵。這些是由大量生產過程中自然產生的不一致性引起的，不被視為瑕疵。',
                'show' => false
            ],
            [
                'id' => 'Four',
                'title' => '4. 產品退換申請的期限及次數',
                'content' => '所有產品的退貨或換貨的申請期限為收貨日計起7天。如果要申請退換，請在收貨日計起7天向客戶服務提出申請。在申請提出後需要在14天內完成退換安排。每件產品只限更換產品1次。',
                'show' => false
            ],
            [
                'id' => 'Five',
                'title' => '5. 產品退換需要符合什麼要求',
                'content' => '退換的產品必需是於GouDa.購買，所有需要退換的產品必需是全新未有開封及不影響二次銷售，產品必需包括原本的包裝、說明書以及所有配件。所有已開封的退換申請都不會接受。',
                'show' => false
            ],
            [
                'id' => 'Six',
                'title' => '6. 有關產品品質（QC）問題的退換安排',
                'content' => '當產品出現產品品質(QC)問題，能否退換是視乎產品代理有沒有提供有關QC問題的退換服務。<br>
                    如果代理有提供QC的退換服務，可以按代理指定的換貨程序換貨，由於每個代理都有不同做法，不能在此詳列，如需要了解個另品牌的換貨程序，可以向客戶服務員查詢。<br>
                    如果代理沒有提供QC的退換服務，我們只能保証產品是未開封的全新產品(*所有隨機產品的指定款是已開包裝的全新產品)，無法處理或接受產品品質(QC)問題提出的退換申請。沒有提供QC的退換服務的品牌包括但不限於：Be@rbrick、Popmart、所有日版產品等等。<br>',
                'show' => false
            ],
            [
                'id' => 'Seven',
                'title' => '7. 有關退款安排',
                'content' => '所有退款方式都是預設按付款方式退回，銀行轉帳或轉數快付款則退到閃電錢包。另外，如果付款已過180天或銀行拒絕退款申請，我們也會將退款退到閃電錢包。如有需要，可以致電聯絡我們安排提取閃電錢包的退款。<br>
                    如果退款由客人提出或產生，信用卡及電子錢包退款需要收取HKD$5手續費，費用會在退款中扣除。<br>
                    退款的參考時間為一般信用卡/銀行轉帳 3-5工作天，電子錢包1-3工作天，閃電錢包即時。<br>',
                'show' => false
            ],
        ];

        foreach ($accordionItems as $item) {
            $collapseClass = $item['show'] ? 'show' : '';
            $buttonClass = $item['show'] ? '' : 'collapsed';
            $expanded = $item['show'] ? 'true' : 'false';
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?= $item['id'] ?>">
                    <button class="accordion-button <?= $buttonClass ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $item['id'] ?>" aria-expanded="<?= $expanded ?>" aria-controls="collapse<?= $item['id'] ?>">
                        <?= $item['title'] ?>
                    </button>
                </h2>
                <div id="collapse<?= $item['id'] ?>" class="accordion-collapse collapse <?= $collapseClass ?>" aria-labelledby="heading<?= $item['id'] ?>" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <?= $item['content'] ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

